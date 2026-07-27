<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($o,) = cli_get_params([
    'useremail' => null,
    'target'    => null,   // planid cible
    'currency'  => 'EUR',
    'help'      => false,
], ['h'=>'help']);

if (!empty($o['help']) || empty($o['useremail']) || empty($o['target'])) {
    echo "Usage: php local/subscriptions/cli/commerce/operations/quote_upgrade.php --useremail=EMAIL --target=PLANID [--currency=EUR]\n";
    exit(0);
}

$user = $DB->get_record('user', ['email'=>$o['useremail'], 'deleted'=>0], '*', IGNORE_MISSING);
if (!$user) cli_error("User not found: {$o['useremail']}");

$tgt  = $DB->get_record('subscription_plan', ['id'=>(int)$o['target']], '*', IGNORE_MISSING);
if (!$tgt) cli_error("Target plan not found: {$o['target']}");

// t0 = début de pile pour le scope cible (si tu as déjà find_scope_first_start, utilise-le)
$advisor = '\local_subscriptions\domain\SubscriptionAdvisor';
$scopeid = $advisor::get_scope_id_for_plan($tgt->id);
if (!$scopeid) cli_error("No scope for target plan.");

$t0 = $advisor::find_scope_first_start($user->id, (int)$scopeid); // tu l'as déjà dans ta classe
if (!$t0) cli_error("No chain start (t0) found for that scope.");

$now = time();
$D2  = $advisor::duration_to_seconds($tgt->duration_key ?? '1year');
$P2  = $DB->get_field('subscription_plan_price','price',['planid'=>$tgt->id,'currency'=>$o['currency']]);

// Plan courant = sub active la plus récente dans le scope
$sub = $DB->get_record_sql("
  SELECT s.* FROM {user_subscription} s
  JOIN {subscription_plan} p ON p.id = s.planid
 WHERE s.userid = :u AND s.status='active' AND p.accessscopeid=:sc
 ORDER BY s.end_date DESC, s.id DESC LIMIT 1", ['u'=>$user->id,'sc'=>$scopeid]);
if (!$sub) cli_error("No active sub in same scope.");

$cur = $DB->get_record('subscription_plan', ['id'=>$sub->planid], '*', MUST_EXIST);
$D1  = $advisor::duration_to_seconds($cur->duration_key ?? '1year');
$P1  = $DB->get_field('subscription_plan_price','price',['planid'=>$cur->id,'currency'=>$o['currency']]);

$t   = max(0, min($D2, $now - (int)$t0));
$base  = round( ($P2 * ($D2 - $t) / $D2) + ($P1 * ($t / $D1)), 2 );
$spent = $advisor::sum_window_spent_in_currency($user->id, (int)$scopeid, (int)$t0, (int)$t0 + $D2, $o['currency']);
$amount= round($base - $spent, 2);

// Pile à remplacer (active/queued seulement)
$toRep = $advisor::list_scope_overlaps($user->id, (int)$scopeid, (int)$t0, (int)$t0 + $D2, ['active','queued']);
$ids   = array_map(fn($s)=>$s->id, $toRep);

echo "User: {$user->firstname} {$user->lastname} <{$user->email}>\n";
echo "t0=".userdate($t0)."  now=".userdate($now)."\n";
echo "Current plan={$cur->name}  P1={$P1} D1={$D1}s   Target={$tgt->name}  P2={$P2} D2={$D2}s\n";
echo "t(consumed since t0) = {$t}s\n";
echo "base = P2*(D2-t)/D2 + P1*(t/D1) = {$base}\n";
echo "spent in window [t0 ; t0+D2) = {$spent}\n";
echo "=> amount = ".number_format($amount,2)." {$o['currency']}\n";
echo "replace ids: ".(empty($ids)?'(none)':implode(', ',$ids))."\n";
