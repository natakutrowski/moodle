<?php
// local/subscriptions/cli/mail/preview_all_emails.php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($o,) = cli_get_params([
  'user'     => 0,          // user id (sinon premier user non supprimé)
  'plan'     => 0,          // plan id (sinon premier)
  'sub'      => 0,          // subscription id (sinon dernière de l'user ou factice)
  'amount'   => '61.00',    // pour upgrade/receipt/failed
  'currency' => 'EUR',
  'types'    => '',         // ex: "upgrade,failed" (vide => tous)
  'outdir'   => '',         // dossier cible (par défaut moodledata/temp/local_subs_preview)
  'help'     => false,
], ['h'=>'help']);

if (!empty($o['help'])) {
  echo "Preview all local_subscriptions emails (no send).\n";
  echo "Usage: php local/subscriptions/cli/mail/preview_all_emails.php --user=ID --plan=ID --sub=ID --types=upgrade,failed --amount=61 --currency=EUR\n";
  exit(0);
}

global $DB;

// -------- Resolve user / plan / sub / PR factice
$user = $o['user']
  ? $DB->get_record('user', ['id'=>(int)$o['user'],'deleted'=>0], '*', MUST_EXIST)
  : $DB->get_records_sql("SELECT * FROM {user} WHERE deleted=0 ORDER BY id ASC", null, 0, 1);

if (is_array($user)) { $user = reset($user); }

$plan = $o['plan']
  ? $DB->get_record('subscription_plan', ['id'=>(int)$o['plan']], '*', MUST_EXIST)
  : $DB->get_records('subscription_plan', null, 'id ASC', '*', 0, 1);

if (is_array($plan)) { $plan = reset($plan); }

$sub = null;
if ($o['sub']) {
  $sub = $DB->get_record('user_subscription', ['id'=>(int)$o['sub']], '*', MUST_EXIST);
} else {
  $sub = $DB->get_record_sql("SELECT * FROM {user_subscription} WHERE userid=:u ORDER BY id DESC", ['u'=>$user->id], IGNORE_MISSING);
}

// si pas de sub → en fabriquer une factice cohérente
if (!$sub) {
  $now = time();
  $sub = (object)[
    'id'=>0,'userid'=>$user->id,'planid'=>$plan->id,'status'=>'active',
    'start_date'=>$now - 7*DAYSECS,'end_date'=>$now + 30*DAYSECS,
    'pricepaid'=>(float)$o['amount'],'currency'=>$o['currency'],
    'transactionid'=>'',
    'payment_provider'=>'stripe',
    'creation_date'=>$now,'last_update'=>$now,
  ];
}

$pr = (object)[
  'price'    => (float)$o['amount'], // PR.price = major units (EUR)
  'currency' => $o['currency'],
];

// -------- Préparer l’outdir
$basedir = $o['outdir'] ?: make_temp_directory('local_subs_preview');
if (!is_dir($basedir)) { mkdir($basedir, 0777, true); }

// -------- Build scenarios
$all = [
  // libellé => callable qui appelle une méthode send_* et remplit mailer::$last
  'activated' => function() use ($user,$plan,$sub) {
      \local_subscriptions\mailer::send_subscription_activated($user,$plan,$sub);
  },
  'expired'   => function() use ($user,$plan,$sub) {
      \local_subscriptions\mailer::send_subscription_expired($user,$plan,$sub);
  },
  'failed'    => function() use ($user,$plan,$sub,$pr) {
      // code d’échec & retry simulés
      \local_subscriptions\mailer::send_failed_recurring($user,$plan,$sub, $pr->price, $pr->currency, null, 'insufficient_funds', time()+2*DAYSECS);
  },
  'upgrade'   => function() use ($user,$plan,$pr,$sub) {
      \local_subscriptions\mailer::send_upgrade_confirmation($user,$plan,$pr,$sub);
  },
  'receipt'   => function() use ($user,$plan,$pr,$sub) {
      // si tu as send_receipt($user,$plan,$pr,$sub)
      if (method_exists('\local_subscriptions\mailer','send_receipt')) {
          \local_subscriptions\mailer::send_receipt($user,$plan,$pr,$sub);
      }
  },
  'welcome'   => function() use ($user,$plan,$pr) {
      if (method_exists('\local_subscriptions\mailer','send_welcome')) {
          \local_subscriptions\mailer::send_welcome($user, 'TempPass123', $plan, $pr);
      }
  },
  'update'    => function() use ($user,$plan,$pr,$sub) {
      if (method_exists('\local_subscriptions\mailer','send_subscription_update')) {
          \local_subscriptions\mailer::send_subscription_update($user,$plan,$pr,$sub);
      }
  },
  'recurring_started' => function() use ($user,$plan,$pr) {
      if (method_exists('\local_subscriptions\mailer','send_recurring_started')) {
          \local_subscriptions\mailer::send_recurring_started($user,$plan,$pr);
      }
  },
];

// Filtre des types si demandé
$types = $o['types'] ? array_map('trim', explode(',', $o['types'])) : array_keys($all);

// -------- Exécution preview
$generated = 0;
foreach ($types as $t) {
    if (empty($all[$t])) { mtrace("Unknown type: $t"); continue; }

    // Active la capture
    \local_subscriptions\mailer::enable_preview();
    // Appelle la méthode
    $all[$t]();

    $render = \local_subscriptions\mailer::get_last_render();
    if (!$render) { mtrace("No render captured for $t (maybe the method doesn't exist)."); continue; }

    $subject = $render['subject'] ?? strtoupper($t).' preview';
    $html = $render['html'] ?? '';
    $text = $render['text'] ?? '';

    file_put_contents($basedir."/preview_{$t}.html", $html);
    file_put_contents($basedir."/preview_{$t}.txt",  $text);
    mtrace("Generated: {$basedir}/preview_{$t}.html (subject: {$subject})");

    $generated++;
}

if (!$generated) {
    mtrace("Nothing rendered. Try --types=upgrade or ensure your mailer has the corresponding send_* methods.");
}
