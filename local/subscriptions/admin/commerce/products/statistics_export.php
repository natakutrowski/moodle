<?php
require_once(__DIR__.'/../../../../../config.php');
require_once($CFG->libdir.'/excellib.class.php');
use local_subscriptions\admin\AdminSecurity;use local_subscriptions\admin\Capabilities;use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;use local_subscriptions\commerce\statistics\product\CommerceProductStatisticsDashboardRepository;
AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku=required_param('sku',PARAM_RAW_TRIMMED);$periodkey=optional_param('statsperiod','30',PARAM_ALPHANUMEXT);$from=optional_param('statsfrom','',PARAM_RAW_TRIMMED);$until=optional_param('statsuntil','',PARAM_RAW_TRIMMED);$currency=strtoupper(optional_param('statscurrency','',PARAM_ALPHA));
$catalog=new CommerceCatalogReadRepository($DB);$details=$catalog->find_by_sku($sku)??$catalog->find_by_purchase_reference($sku);if($details===null)throw new moodle_exception('commerce_catalog_product_not_found','local_subscriptions');$product=$details->get_summary();$refs=[$product->get_sku()];foreach($details->get_legacy_references() as $r){if($r['table']==='subscription_plan'){$refs[]='subscription-plan:'.(int)$r['id'];}elseif($r['table']==='subscription_digital_product'){$refs[]='digital-product:'.(int)$r['id'];$lr=$DB->get_record('subscription_digital_product',['id'=>(int)$r['id']],'slug',IGNORE_MISSING);if($lr&&trim((string)$lr->slug)!=='')$refs[]='digital-product:'.trim((string)$lr->slug);}}$refs=array_values(array_unique($refs));
$period=CommerceStatisticsPeriodResolver::resolve($periodkey,$from,$until);$repo=new CommerceProductStatisticsDashboardRepository($DB);$snapshot=$repo->snapshot($period,$refs,$product->get_sku(),$currency!==''?$currency:null);$orders=$repo->order_rows($period,$refs,$currency!==''?$currency:null);$grants=$repo->manual_grant_rows($period,$product->get_sku());$payments=$repo->payment_rows($period,$refs,$currency!==''?$currency:null);
$filename='product-stats-'.preg_replace('/[^a-zA-Z0-9_-]+/','-',strtolower($product->get_sku())).'-'.date('Ymd-His').'.xlsx';$wb=new MoodleExcelWorkbook('-');$wb->send($filename);$head=$wb->add_format(['bold'=>1,'bg_color'=>'#FCE7F3']);$money=$wb->add_format(['num_format'=>'#,##0.00']);
$ws=$wb->add_worksheet(get_string('commerce_m51_export_summary','local_subscriptions'));$headers=['Metric','Value','Currency'];foreach($headers as $i=>$h)$ws->write_string(0,$i,$h,$head);$r=1;foreach($snapshot['global'] as $k=>$v){$ws->write_string($r,0,$k);$ws->write_number($r,1,(float)$v);$r++;}foreach($snapshot['currencies'] as $c=>$m){$ws->write_string($r,0,'revenue_collected');$ws->write_number($r,1,$m['revenueminor']/100,$money);$ws->write_string($r,2,$c);$r++;}
$ws=$wb->add_worksheet(get_string('commerce_m51_export_orders','local_subscriptions'));$h=['Date','Reference','User ID','Email','Product','Quantity','Currency','Net','Purchase status','Payment status','Provider'];foreach($h as $i=>$x)$ws->write_string(0,$i,$x,$head);$r=1;foreach($orders as $o){$ws->write_string($r,0,userdate((int)$o->timecreated,'%Y-%m-%d %H:%M'));$ws->write_string($r,1,(string)$o->reference);$ws->write_number($r,2,(int)$o->userid);$ws->write_string($r,3,(string)$o->customeremail);$ws->write_string($r,4,(string)$o->label);$ws->write_number($r,5,(int)$o->quantity);$ws->write_string($r,6,(string)$o->currency);$ws->write_number($r,7,((int)$o->netminor)/100,$money);$ws->write_string($r,8,(string)$o->purchasestatus);$ws->write_string($r,9,(string)($o->paymentstatus??''));$ws->write_string($r,10,(string)($o->provider??''));$r++;}
$ws=$wb->add_worksheet(get_string('commerce_m51_export_deliveries','local_subscriptions'));$h=['Date','Grant reference','User ID','Email','Quantity','Product SKU','Type','Resource','Status'];foreach($h as $i=>$x)$ws->write_string(0,$i,$x,$head);$r=1;foreach($grants as $g){$ws->write_string($r,0,userdate((int)$g->timecreated,'%Y-%m-%d %H:%M'));$ws->write_string($r,1,(string)$g->grantreference);$ws->write_number($r,2,(int)$g->beneficiaryuserid);$ws->write_string($r,3,(string)$g->beneficiaryemail);$ws->write_number($r,4,(int)$g->quantity);$ws->write_string($r,5,(string)$g->productsku);$ws->write_string($r,6,(string)$g->type);$ws->write_string($r,7,(string)$g->resourcekey);$ws->write_string($r,8,(string)$g->status);$r++;}
$ws=$wb->add_worksheet(get_string('commerce_m52_export_payments','local_subscriptions'));
$h=['Date','Purchase reference','Sequence','Provider','Provider reference','Status','Currency','Amount','Paid at'];
foreach($h as $i=>$x)$ws->write_string(0,$i,$x,$head);$r=1;
foreach($payments as $p){
    $ws->write_string($r,0,userdate((int)$p->timecreated,'%Y-%m-%d %H:%M'));
    $ws->write_string($r,1,(string)$p->purchasereference);
    $ws->write_number($r,2,(int)$p->sequence);
    $ws->write_string($r,3,(string)$p->provider);
    $ws->write_string($r,4,(string)$p->providerreference);
    $ws->write_string($r,5,(string)$p->status);
    $ws->write_string($r,6,(string)$p->currency);
    $ws->write_number($r,7,((int)$p->amountminor)/100,$money);
    $ws->write_string($r,8,!empty($p->paidat)?userdate((int)$p->paidat,'%Y-%m-%d %H:%M'):'');
    $r++;
}
$wb->close();exit;
