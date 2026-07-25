<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\rollout\CommerceCertificationEvidence;
use local_subscriptions\commerce\rollout\CommercePreprodCertificationReport;
use local_subscriptions\commerce\rollout\CommerceRolloutCertification;
[$options] = cli_get_params(['passed'=>'','strict'=>false],['h'=>'help']);
$passed=array_values(array_filter(array_map('trim',explode(',',(string)$options['passed']))));
$required=array_keys((new CommerceRolloutCertification())->checklist());
$evidence=array_map(static fn(string $key):CommerceCertificationEvidence=>new CommerceCertificationEvidence($key,in_array($key,$passed,true)), $required);
$report=new CommercePreprodCertificationReport($evidence); $missing=$report->missing($required);
cli_writeln('== I10E PRE-PROD certification ==');
foreach($required as $key) cli_writeln(sprintf('  [%s] %s',in_array($key,$missing,true)?' ':'x',$key));
if(!empty($options['strict'])&&!$report->is_ready($required)) cli_error('PRE-PROD certification incomplete: '.implode(', ',$missing));
cli_writeln($report->is_ready($required)?'[OK] Commerce Native rollout READY FOR PROD.':'[WARN] Certification incomplete; missing '.count($missing).' item(s).');
