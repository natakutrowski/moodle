<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
[$options] = cli_get_params(['help'=>false,'limit'=>100],['h'=>'help']);
if ($options['help']) { echo "Audit de l'outbox Commerce\n--limit=N\n"; exit(0); }
$repository=new CommerceMailQueueRepository(); $result=$repository->search([],0,max(1,(int)$options['limit']));
$counts=[]; foreach($result['records'] as $record){$counts[$record->status]=($counts[$record->status]??0)+1;}
echo "Commerce mail audit\nTotal: {$result['total']}\n"; foreach($counts as $status=>$count){echo "$status: $count\n";}
foreach($result['records'] as $r){if($r->status==='failed'||($r->status==='processing'&&$r->timeprocessing && (int)$r->timeprocessing<time()-1800)){echo "#{$r->id} {$r->mailtype} {$r->status} {$r->recipientemail} {$r->lasterror}\n";}}
