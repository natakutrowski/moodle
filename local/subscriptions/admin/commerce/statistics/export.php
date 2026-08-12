<?php

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\statistics\CommerceGlobalStatisticsDashboardRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;

AdminSecurity::require(Capabilities::VIEW_STATISTICS);

$periodkey=strtolower(optional_param('period','30',PARAM_ALPHANUMEXT));
$from=optional_param('from','',PARAM_RAW_TRIMMED);
$until=optional_param('until','',PARAM_RAW_TRIMMED);
$currency=strtoupper(optional_param('currency','',PARAM_ALPHA));
$provider=strtolower(optional_param('provider','',PARAM_ALPHANUMEXT));
if(!array_key_exists($periodkey,CommerceStatisticsPeriodResolver::options()))$periodkey='30';
if(!in_array($currency,['','EUR','RUB'],true))$currency='';
if(!in_array($provider,['','stripe','alfa'],true))$provider='';

$period=CommerceStatisticsPeriodResolver::resolve($periodkey,$from,$until);
$repo=new CommerceGlobalStatisticsDashboardRepository($DB);
$snapshot=$repo->snapshot($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$orders=$repo->order_rows($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$payments=$repo->payment_rows($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$grants=$repo->manual_grant_rows($period);

$filename='commerce-global-statistics-'.userdate(time(),'%Y%m%d-%H%M').'.xlsx';
$wb=new MoodleExcelWorkbook('-');
$wb->send($filename);
$head=$wb->add_format(['bold'=>1]);
$money=$wb->add_format(['num_format'=>'0.00']);

$ws=$wb->add_worksheet(get_string('commerce_m53_export_summary','local_subscriptions'));
$r=0;
$ws->write_string($r++,0,get_string('commerce_statistics_period','local_subscriptions'),$head);
$ws->write_string($r++,0,userdate($period->start(),'%Y-%m-%d %H:%M').' → '.userdate($period->end()-1,'%Y-%m-%d %H:%M'));
$r++;
foreach($snapshot['currencies'] as $code=>$row){
    $ws->write_string($r,0,$code,$head);
    $ws->write_number($r,1,(int)$row['paidorders']);
    $ws->write_number($r,2,(int)$row['paidcustomers']);
    $ws->write_number($r,3,((int)$row['revenueminor'])/100,$money);
    $ws->write_number($r,4,((int)$row['averageorderminor'])/100,$money);
    $r++;
}

$ws=$wb->add_worksheet(get_string('commerce_m53_export_orders','local_subscriptions'));
$headers=['Date','Reference','User ID','Email','Status','Currency','Total'];
foreach($headers as $i=>$h)$ws->write_string(0,$i,$h,$head);$r=1;
foreach($orders as $row){
    $ws->write_string($r,0,userdate((int)$row->timecreated,'%Y-%m-%d %H:%M'));
    $ws->write_string($r,1,(string)$row->reference);
    $ws->write_number($r,2,(int)$row->userid);
    $ws->write_string($r,3,(string)$row->customeremail);
    $ws->write_string($r,4,(string)$row->status);
    $ws->write_string($r,5,(string)$row->currency);
    $ws->write_number($r,6,((int)$row->totalminor)/100,$money);$r++;
}

$ws=$wb->add_worksheet(get_string('commerce_m52_export_payments','local_subscriptions'));
$headers=['Date','Purchase reference','Sequence','Provider','Provider reference','Status','Currency','Amount','Paid at'];
foreach($headers as $i=>$h)$ws->write_string(0,$i,$h,$head);$r=1;
foreach($payments as $row){
    $ws->write_string($r,0,userdate((int)$row->timecreated,'%Y-%m-%d %H:%M'));
    $ws->write_string($r,1,(string)$row->purchasereference);
    $ws->write_number($r,2,(int)$row->sequence);
    $ws->write_string($r,3,(string)$row->provider);
    $ws->write_string($r,4,(string)$row->providerreference);
    $ws->write_string($r,5,(string)$row->status);
    $ws->write_string($r,6,(string)$row->currency);
    $ws->write_number($r,7,((int)$row->amountminor)/100,$money);
    $ws->write_string($r,8,!empty($row->paidat)?userdate((int)$row->paidat,'%Y-%m-%d %H:%M'):'');$r++;
}

$ws=$wb->add_worksheet(get_string('commerce_m53_export_grants','local_subscriptions'));
$headers=['Date','Grant reference','User ID','Email','Product SKU','Quantity','Type','Resource','Status'];
foreach($headers as $i=>$h)$ws->write_string(0,$i,$h,$head);$r=1;
foreach($grants as $row){
    $ws->write_string($r,0,userdate((int)$row->timecreated,'%Y-%m-%d %H:%M'));
    $ws->write_string($r,1,(string)$row->grantreference);
    $ws->write_number($r,2,(int)$row->beneficiaryuserid);
    $ws->write_string($r,3,(string)$row->beneficiaryemail);
    $ws->write_string($r,4,(string)$row->productsku);
    $ws->write_number($r,5,(int)$row->quantity);
    $ws->write_string($r,6,(string)$row->type);
    $ws->write_string($r,7,(string)$row->resourcekey);
    $ws->write_string($r,8,(string)$row->status);$r++;
}
$wb->close();
exit;
