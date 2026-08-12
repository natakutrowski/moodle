<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\statistics\CommerceGlobalStatisticsDashboardRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceGlobalStatisticsDashboardRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceGlobalStatisticsFilterRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsBreakdownRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsPageRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context=AdminSecurity::require(Capabilities::VIEW_STATISTICS);
$periodkey=strtolower(optional_param('period','30',PARAM_ALPHANUMEXT));
$from=optional_param('from','',PARAM_RAW_TRIMMED);
$until=optional_param('until','',PARAM_RAW_TRIMMED);
$currency=strtoupper(optional_param('currency','',PARAM_ALPHA));
$provider=strtolower(optional_param('provider','',PARAM_ALPHANUMEXT));
if(!array_key_exists($periodkey,CommerceStatisticsPeriodResolver::options()))$periodkey='30';
if(!in_array($currency,['','EUR','RUB'],true))$currency='';
if(!in_array($provider,['','stripe','alfa'],true))$provider='';

$period=CommerceStatisticsPeriodResolver::resolve($periodkey,$from,$until);
$previousperiod=CommerceStatisticsPeriodResolver::previous($periodkey,$period);
$repo=new CommerceGlobalStatisticsDashboardRepository($DB);
$snapshot=$repo->snapshot($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$previous=$previousperiod!==null?$repo->snapshot($previousperiod,$currency!==''?$currency:null,$provider!==''?$provider:null):null;
$revenue=$repo->revenue_series($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$orders=$repo->paid_order_series($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$products=$repo->top_products($period,$currency!==''?$currency:null,$provider!==''?$provider:null);
$productpayments=$repo->product_payment_breakdown($period,$currency!==''?$currency:null,$provider!==''?$provider:null);

$pageparams=array_filter(['period'=>$periodkey,'from'=>$from,'until'=>$until,'currency'=>$currency,'provider'=>$provider],static fn($v)=>$v!=='');
$pageurl=new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php',$pageparams);
$baseurl=new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php');
$exporturl=new moodle_url('/local/subscriptions/admin/commerce/statistics/export.php',$pageparams);
$pagetitle=get_string('commerce_statistics_title','local_subscriptions');

CrmPageConfigurator::configure($PAGE,$context,$pageurl,$pagetitle,'local-subscriptions-commerce-statistics-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_global_statistics.css');
$PAGE->requires->css('/local/subscriptions/styles/commerce_statistics_breakdowns.css');

$formatmoney=static function(int $minor,string $code): string{
    $major=$minor/100;
    if(class_exists('NumberFormatter')){
        $formatter=new NumberFormatter(current_language(),NumberFormatter::CURRENCY);
        $result=$formatter->formatCurrency($major,$code);
        if($result!==false)return$result;
    }
    return format_float($major,2).' '.$code;
};

$comparison=null;
if($previousperiod!==null){
    $range=(object)[
        'from'=>userdate($previousperiod->start(),get_string('strftimedatetimeshort')),
        'until'=>userdate($previousperiod->end()-1,get_string('strftimedatetimeshort')),
    ];
    $comparison=$periodkey==='today'
        ?get_string('commerce_m51_comparison_today','local_subscriptions',$range)
        :get_string('commerce_m51_comparison_previous','local_subscriptions',$range);
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE,$context);
echo CrmBreadcrumbRenderer::render([
    ['label'=>get_string('crm_commerce_title','local_subscriptions'),'url'=>new moodle_url(subscription_config::admin_commerce_page())],
    ['label'=>$pagetitle,'url'=>null],
]);
echo CrmPageHeader::render($pagetitle,get_string('commerce_statistics_description','local_subscriptions'),HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::STATISTICS);

echo html_writer::start_div('m53-statistics-shell');
echo CommerceGlobalStatisticsFilterRenderer::render($baseurl,$periodkey,$from,$until,$currency,$provider,$exporturl);
echo CommerceGlobalStatisticsDashboardRenderer::comparison_note($comparison);
echo CommerceGlobalStatisticsDashboardRenderer::kpis($snapshot,$previous,$formatmoney);
echo CommerceGlobalStatisticsDashboardRenderer::funnel_and_breakdowns($snapshot,$formatmoney);
echo CommerceStatisticsBreakdownRenderer::render($snapshot, $formatmoney);

echo html_writer::tag('h2',s(get_string('commerce_m53_commercial_evolution','local_subscriptions')),['class'=>'m53-section-heading']);
echo CommerceGlobalStatisticsDashboardRenderer::revenue($OUTPUT,$revenue);
echo CommerceGlobalStatisticsDashboardRenderer::orders($OUTPUT,$orders);

echo html_writer::tag('h2',s(get_string('commerce_m53_payment_health','local_subscriptions')),['class'=>'m53-section-heading']);
echo CommerceGlobalStatisticsDashboardRenderer::payments($snapshot['payments']);

echo html_writer::tag('h2',s(get_string('commerce_m53_product_payments','local_subscriptions')),['class'=>'m53-section-heading']);
echo html_writer::div(
    get_string('commerce_m53_product_payments_help','local_subscriptions'),
    'm53-section-help'
);
echo CommerceGlobalStatisticsDashboardRenderer::product_payments($productpayments);

echo html_writer::tag('h2',s(get_string('commerce_statistics_chart_top_products','local_subscriptions')),['class'=>'m53-section-heading']);
echo CommerceGlobalStatisticsDashboardRenderer::top_products($OUTPUT,$products);

echo CommerceStatisticsPageRenderer::operational_shortcuts();
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();

echo '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".m53-revenue-mode").forEach(function(s){s.addEventListener("change",function(){var c=this.closest(".m53-revenue-chart-card");if(!c)return;c.querySelector(".m53-revenue-chart--period").classList.toggle("d-none",this.value!=="period");c.querySelector(".m53-revenue-chart--cumulative").classList.toggle("d-none",this.value!=="cumulative");});});});</script>';
echo $OUTPUT->footer();
