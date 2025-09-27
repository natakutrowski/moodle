<?php
define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($o,) = cli_get_params([
  'user'     => 0,           // user id (sinon premier user non supprimé)
  'plan'     => 0,           // plan id (sinon premier)
  'sub'      => 0,           // subscription id (sinon première du user)
  'amount'   => '0.00',      // pour upgrade/receipt
  'currency' => 'EUR',
  'type'     => '',          // activated|expired|failed|upgrade|receipt|welcome|update (vide => tout)
  'outdir'   => '',          // dossier cible (sinon moodledata/temp/local_subs_preview)
  'help'     => false,
], ['h'=>'help']);

if (!empty($o['help'])) {
  echo "Preview emails for local_subscriptions (no send)\n";
  echo "Usage: php local/subscriptions/cli/preview_emails.php --user=ID --plan=ID --sub=ID --type=upgrade --amount=61 --currency=EUR\n";
  exit(0);
}

global $DB;

// Resolve user/plan/sub
$user = $o['user'] ? $DB->get_record('user',['id'=>(int)$o['user'],'deleted'=>0],'*',MUST_EXIST)
                   : $DB->get_records_sql("SELECT * FROM {user} WHERE deleted=0 ORDER BY id ASC", null, 0, 1)[array_key_first($DB->get_records_sql("SELECT * FROM {user} WHERE deleted=0 ORDER BY id ASC",null,0,1))];

$plan = $o['plan'] ? $DB->get_record('subscription_plan',['id'=>(int)$o['plan']], '*', MUST_EXIST)
                   : $DB->get_records('subscription_plan', null, 'id ASC', '*', 0, 1)[array_key_first($DB->get_records('subscription_plan', null, 'id ASC','*',0,1))];

$sub = $o['sub'] ? $DB->get_record('user_subscription',['id'=>(int)$o['sub']], '*', MUST_EXIST)
                 : $DB->get_record_sql("SELECT * FROM {user_subscription} WHERE userid=:u ORDER BY id DESC", ['u'=>$user->id], IGNORE_MISSING);
if (!$sub) {
  // créer une sub fictive en mémoire pour le rendu
  $sub = (object)[
    'id'=>0,'userid'=>$user->id,'planid'=>$plan->id,'status'=>'active',
    'start_date'=>time()-7*DAYSECS,'end_date'=>time()+30*DAYSECS,
    'pricepaid'=>(float)$o['amount'],'currency'=>$o['currency'],
    'creation_date'=>time(),'last_update'=>time()
  ];
}

// Prépare PR factice pour upgrade/receipt
$pr = (object)[
  'price'    => (float)$o['amount'],
  'currency' => $o['currency'],
];

// Outdir
$basedir = $o['outdir'] ?: make_temp_directory('local_subs_preview');
if (!is_dir($basedir)) { mkdir($basedir, 0777, true); }


// Active le mode preview dans le mailer (désactive tout envoi réel)
\local_subscriptions\mailer::enable_preview();

// Map des callbacks à prévisualiser
$all = [
  'activated' => function() use ($user,$plan,$sub) {
      \local_subscriptions\mailer::send_subscription_activated($user,$plan,$sub);
  },
  'expired'   => function() use ($user,$plan,$sub) {
      \local_subscriptions\mailer::send_subscription_expired($user,$plan,$sub);
  },
  'failed'    => function() use ($user,$plan,$sub,$pr) {
      \local_subscriptions\mailer::send_failed_recurring($user,$plan,$sub,$pr->price,$pr->currency,null,'insufficient_funds',time()+2*DAYSECS);
  },
  'upgrade'   => function() use ($user,$plan,$pr,$sub) {
      \local_subscriptions\mailer::send_upgrade_confirmation($user,$plan,$pr,$sub);
  },
  // Ajoute ici send_receipt / send_welcome / send_subscription_update si tu veux
];

$types = $o['type'] ? [$o['type']] : array_keys($all);

// Hook de capture : on monkey-patch email_to_user via $CFG->noemailever et la surcouche
$CFG->noemailever = true; // pas d’envoi

// Petite capture HTML/TXT en utilisant render_email_layout derrière tes send_*
$printed = 0;
foreach ($types as $t) {
    if (!isset($all[$t])) { mtrace("Unknown type: $t"); continue; }
    // On utilise un tampon output pour récupérer le layout (tes send_* n'affichent rien, mais au cas où)
    try {
        $all[$t](); // génère les layouts et passerait par email_to_user (désactivé)

        // Option : si tu veux capturer explicitement, expose une variable statique dans mailer (non nécessaire ici).
        // Pour un preview simple, on reconstruit un body minimal :
        [$html,$text] = \local_subscriptions\mailer::render_email_layout(
            strtoupper($t).' preview',
            '<p>Preview body for '.$t.'</p>',
            get_string('view_my_subscriptions','local_subscriptions'),
            (new moodle_url('/local/subscriptions/my_subscriptions.php'))->out(false)
        );

        file_put_contents($basedir."/preview_{$t}.html", $html);
        file_put_contents($basedir."/preview_{$t}.txt",  $text);
        mtrace("Generated: {$basedir}/preview_{$t}.html");
        $printed++;
    } catch (\Throwable $ex) {
        mtrace("Error rendering {$t}: ".$ex->getMessage());
    }
}

if (!$printed) {
    mtrace("Nothing rendered. Try --type=upgrade (or activated|expired|failed) and ensure plan/sub exist.");
}
