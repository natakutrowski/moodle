<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
[$options] = cli_get_params(['help'=>false,'mailid'=>0],['h'=>'help']);
if ($options['help'] || !(int)$options['mailid']) { echo "Preview certification sans envoi\n--mailid=ID\n"; exit($options['help']?0:1); }
$preview=(new CommerceMailAdminService())->preview((int)$options['mailid']);
$checks=['subject'=>trim($preview['subject'])!=='','html'=>str_contains($preview['html'],'<html'),'text'=>trim($preview['text'])!==''];
foreach($checks as $name=>$ok){echo ($ok?'[OK] ':'[FAIL] ').$name."\n";} exit(in_array(false,$checks,true)?1:0);
