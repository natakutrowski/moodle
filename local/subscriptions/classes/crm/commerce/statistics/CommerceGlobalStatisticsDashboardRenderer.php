<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeries;

/** Premium global Commerce analytics renderer. */
final class CommerceGlobalStatisticsDashboardRenderer {
    public static function comparison_note(?string $description): string {
        if ($description===null||trim($description)==='')return'';
        return html_writer::div(
            html_writer::tag('i','',['class'=>'fa-solid fa-arrow-trend-up','aria-hidden'=>'true']).
            html_writer::tag('span',s($description)),
            'm53-comparison-note'
        );
    }

    public static function kpis(array $snapshot,?array $previous,callable $money): string {
        $g=$snapshot['global'];$pg=$previous['global']??[];
        $cards=[
            ['paidorders','commerce_m53_paid_orders','fa-solid fa-receipt','orders'],
            ['paidcustomers','commerce_m53_paid_customers','fa-solid fa-users','customers'],
            ['soldquantity','commerce_m53_units_sold','fa-solid fa-boxes-stacked','units'],
            ['delivered','commerce_m53_total_delivered','fa-solid fa-circle-check','delivered'],
        ];
        $html=html_writer::start_div('m53-kpi-layout');
        $html.=html_writer::start_div('m53-primary-kpis');
        foreach($cards as [$field,$label,$icon,$class]){
            $value=(int)($g[$field]??0);$old=$previous!==null?(int)($pg[$field]??0):null;
            $html.=html_writer::tag('article',
                html_writer::div(html_writer::tag('i','',['class'=>$icon,'aria-hidden'=>'true']),'m53-kpi-icon').
                html_writer::div(
                    html_writer::div(html_writer::tag('strong',s((string)$value)).($old!==null?self::trend($value,$old):''),'m53-kpi-value').
                    html_writer::div(s(get_string($label,'local_subscriptions')),'m53-kpi-label'),
                    'm53-kpi-copy'
                ),
                ['class'=>'m53-kpi-card m53-kpi-card--'.$class]
            );
        }
        $html.=html_writer::end_div();

        $html.=html_writer::start_div('m53-revenue-kpis');
        foreach($snapshot['currencies'] as $currency=>$row){
            $current=(int)$row['revenueminor'];$old=$previous!==null?(int)($previous['currencies'][$currency]['revenueminor']??0):null;
            $avg=(int)($row['averageorderminor']??0);$oldavg=$previous!==null?(int)($previous['currencies'][$currency]['averageorderminor']??0):null;
            $html.=html_writer::tag('article',
                html_writer::div(
                    html_writer::tag('span',self::flag((string)$currency).' '.s((string)$currency),['class'=>'m53-revenue-currency']).
                    html_writer::tag('span',s(get_string('commerce_m53_revenue_collected','local_subscriptions')),['class'=>'m53-revenue-caption']),
                    'm53-revenue-head'
                ).
                html_writer::div(html_writer::tag('strong',s($money($current,(string)$currency))).($old!==null?self::trend($current,$old):''),'m53-revenue-main').
                html_writer::div(
                    html_writer::tag('span',s(get_string('commerce_m53_average_order','local_subscriptions'))).
                    html_writer::tag('strong',s($money($avg,(string)$currency))).
                    ($oldavg!==null?self::trend($avg,$oldavg):''),
                    'm53-revenue-average'
                ),
                ['class'=>'m53-revenue-card']
            );
        }
        $html.=html_writer::end_div();

        $rate=(float)($g['paymentsuccessrate']??0);$oldrate=$previous!==null?(float)($pg['paymentsuccessrate']??0):null;
        $html.=html_writer::start_div('m53-health-strip');
        $health=[
            ['pending','commerce_m51_payment_pending','fa-regular fa-clock'],
            ['failed','commerce_m51_payment_failed','fa-solid fa-triangle-exclamation'],
            ['cancelled','commerce_m51_payment_cancelled','fa-solid fa-ban'],
            ['refunded','commerce_m51_payment_refunded','fa-solid fa-rotate-left'],
            ['pendingfulfillments','commerce_m53_pending_fulfillments','fa-solid fa-box-open'],
        ];
        $html.=html_writer::tag('article',
            html_writer::tag('i','',['class'=>'fa-solid fa-shield-heart','aria-hidden'=>'true']).
            html_writer::div(html_writer::tag('strong',s(format_float($rate,1)).' %').($oldrate!==null?self::trend($rate,$oldrate):'').
            html_writer::tag('span',s(get_string('commerce_m53_payment_success_rate','local_subscriptions'))),'m53-health-copy'),
            ['class'=>'m53-health-card m53-health-card--rate']
        );
        foreach($health as [$field,$label,$icon]){
            $html.=html_writer::tag('article',
                html_writer::tag('i','',['class'=>$icon,'aria-hidden'=>'true']).
                html_writer::div(html_writer::tag('strong',s((string)($g[$field]??0))).
                html_writer::tag('span',s(get_string($label,'local_subscriptions'))),'m53-health-copy'),
                ['class'=>'m53-health-card']
            );
        }
        $html.=html_writer::end_div();
        $html.=html_writer::end_div();
        return $html;
    }

    public static function funnel_and_breakdowns(array $snapshot,callable $money): string {
        $g=$snapshot['global'];
        $attempts=(int)($g['paymentattempts']??0);
        $paid=(int)($g['successfulpayments']??0);
        $pending=(int)($g['pending']??0);
        $failed=(int)($g['failed']??0);
        $cancelled=(int)($g['cancelled']??0);
        $refunded=(int)($g['refunded']??0);
        $notcompleted=$failed+$cancelled;

        $html=html_writer::start_div('m53-insights-grid');

        // Payment journey is a branch tree, not a linear funnel.
        $html.=html_writer::start_tag('section',['class'=>'m53-panel m53-payment-tree-panel']);
        $html.=html_writer::tag('h3',
            s(get_string('commerce_m53_payment_journey','local_subscriptions')).
            html_writer::tag('span','ⓘ',['class'=>'m53-tree-info','aria-hidden'=>'true']),
            ['class'=>'m53-panel-title m53-payment-tree-title']
        );
        $html.=html_writer::div(
            s(get_string('commerce_m53_payment_journey_help','local_subscriptions')),
            'm53-panel-help'
        );

        $html.=html_writer::start_div('m53-payment-tree m53-payment-tree--premium');

        // Root.
        $html.=self::premium_tree_node(
            'root',
            $attempts,
            get_string('commerce_m53_payment_attempts','local_subscriptions'),
            100.0,
            'fa-solid fa-cart-shopping'
        );

        // Main connector: vertical trunk + horizontal rail.
        $html.=html_writer::div(
            html_writer::span('', 'm53-tree-trunk-line').
            html_writer::span('', 'm53-tree-junction'),
            'm53-tree-main-connector'
        );

        // Main mutually-exclusive payment states.
        $html.=html_writer::start_div('m53-tree-branches m53-tree-branches--premium');
        $branches=[
            ['paid',$paid,'commerce_m51_payment_paid','fa-solid fa-check'],
            ['pending',$pending,'commerce_m51_payment_pending','fa-regular fa-clock'],
            ['notcompleted',$notcompleted,'commerce_m53_payment_not_completed','fa-solid fa-xmark'],
            ['refunded',$refunded,'commerce_m51_payment_refunded','fa-solid fa-arrow-rotate-left'],
        ];
        foreach($branches as [$class,$value,$label,$icon]){
            $pct=$attempts>0?($value/$attempts)*100:0.0;
            $html.=html_writer::div(
                html_writer::span('', 'm53-tree-branch-line m53-tree-branch-line--'.$class).
                self::premium_tree_node(
                    $class,
                    $value,
                    get_string($label,'local_subscriptions'),
                    $pct,
                    $icon
                ),
                'm53-tree-branch m53-tree-branch--'.$class
            );
        }
        $html.=html_writer::end_div();

        // "Not completed" is itself split into mutually-exclusive terminal states.
        if($notcompleted>0){
            $html.=html_writer::start_div('m53-tree-failure-subtree');
            $html.=html_writer::div(
                html_writer::span('', 'm53-tree-failure-trunk').
                html_writer::span('', 'm53-tree-failure-junction'),
                'm53-tree-failure-connector'
            );
            $html.=html_writer::start_div('m53-tree-failure-branches');

            $failedpct=$attempts>0?($failed/$attempts)*100:0.0;
            $cancelledpct=$attempts>0?($cancelled/$attempts)*100:0.0;

            $html.=html_writer::div(
                html_writer::span('', 'm53-tree-failure-line').
                self::premium_tree_node(
                    'failed',
                    $failed,
                    get_string('commerce_m51_payment_failed','local_subscriptions'),
                    $failedpct,
                    'fa-solid fa-circle-xmark'
                ),
                'm53-tree-failure-branch'
            );
            $html.=html_writer::div(
                html_writer::span('', 'm53-tree-failure-line').
                self::premium_tree_node(
                    'cancelled',
                    $cancelled,
                    get_string('commerce_m51_payment_cancelled','local_subscriptions'),
                    $cancelledpct,
                    'fa-solid fa-ban'
                ),
                'm53-tree-failure-branch'
            );

            $html.=html_writer::end_div();
            $html.=html_writer::end_div();
        }

        // Business summary: successful conversion vs attempts that have not
        // resulted in a successful payment.
        $conversion=$attempts>0?($paid/$attempts)*100:0.0;
        $abandoned=max(0,$attempts-$paid);
        $abandonment=$attempts>0?($abandoned/$attempts)*100:0.0;

        $html.=html_writer::start_div('m53-payment-tree-summary');
        $html.=html_writer::div(
            html_writer::div(
                html_writer::tag('i','',['class'=>'fa-solid fa-chart-column','aria-hidden'=>'true']),
                'm53-tree-summary-icon'
            ).
            html_writer::div(
                html_writer::tag('span',s(get_string('commerce_m53_conversion','local_subscriptions'))).
                html_writer::tag('strong',s(format_float($conversion,1)).' %'),
                'm53-tree-summary-metric'
            ).
            html_writer::div(
                s(get_string('commerce_m53_global_conversion','local_subscriptions',
                    (object)['paid'=>$paid,'attempts'=>$attempts,'rate'=>format_float($conversion,1)]
                )),
                'm53-tree-summary-copy'
            ),
            'm53-tree-summary-block m53-tree-summary-block--conversion'
        );

        $html.=html_writer::div('', 'm53-tree-summary-divider');

        $html.=html_writer::div(
            html_writer::div(
                html_writer::tag('span',s(get_string('commerce_m53_payment_not_completed','local_subscriptions'))).
                html_writer::tag('strong',s(format_float($abandonment,1)).' %'),
                'm53-tree-summary-metric'
            ).
            html_writer::div(
                s($abandoned).' / '.s($attempts),
                'm53-tree-summary-copy'
            ),
            'm53-tree-summary-block m53-tree-summary-block--abandonment'
        );
        $html.=html_writer::end_div();

        // Compact legend explaining the branch semantics.
        $legend=[
            ['paid','commerce_m51_payment_paid'],
            ['pending','commerce_m51_payment_pending'],
            ['notcompleted','commerce_m53_payment_not_completed'],
            ['refunded','commerce_m51_payment_refunded'],
        ];
        $html.=html_writer::start_div('m53-tree-legend');
        foreach($legend as [$class,$label]){
            $html.=html_writer::div(
                html_writer::span('', 'm53-tree-legend-dot m53-tree-legend-dot--'.$class).
                html_writer::tag('strong',s(get_string($label,'local_subscriptions'))),
                'm53-tree-legend-item'
            );
        }
        $html.=html_writer::end_div();

        $html.=html_writer::end_div();
        $html.=html_writer::end_tag('section');

        return $html . html_writer::end_div();
    }

    /**
     * Payment state by product. Each row is a branch distribution, not a linear funnel.
     */
    public static function product_payments(array $rows): string {
        if($rows===[])return'';

        $sections='';
        foreach($rows as $currency=>$items){
            if(!$items)continue;
            $body='';
            foreach($items as $item){
                $notcompleted=(int)$item['failed']+(int)$item['cancelled'];
                $body.=html_writer::tag('tr',
                    html_writer::tag('td',
                        html_writer::tag('strong',s(format_string((string)$item['label']))).
                        html_writer::tag('small',s((string)$item['reference']),['class'=>'d-block text-muted'])
                    ).
                    self::payment_cell((int)$item['attempts']).
                    self::payment_cell((int)$item['paid'],'paid').
                    self::payment_cell((int)$item['pending'],'pending').
                    self::payment_cell($notcompleted,'failed').
                    self::payment_cell((int)$item['refunded'],'refunded').
                    html_writer::tag('td',
                        html_writer::tag('strong',s(format_float((float)$item['conversion'],1)).' %',
                            ['class'=>'m53-product-conversion']),
                        ['class'=>'text-end']
                    )
                );
            }
            $head=html_writer::tag('tr',
                html_writer::tag('th',s(get_string('commerce_m53_product','local_subscriptions'))).
                html_writer::tag('th',s(get_string('commerce_m53_payment_attempts','local_subscriptions')),['class'=>'text-end']).
                html_writer::tag('th',s(get_string('commerce_m51_payment_paid','local_subscriptions')),['class'=>'text-end']).
                html_writer::tag('th',s(get_string('commerce_m51_payment_pending','local_subscriptions')),['class'=>'text-end']).
                html_writer::tag('th',s(get_string('commerce_m53_payment_not_completed','local_subscriptions')),['class'=>'text-end']).
                html_writer::tag('th',s(get_string('commerce_m51_payment_refunded','local_subscriptions')),['class'=>'text-end']).
                html_writer::tag('th',s(get_string('commerce_m53_conversion','local_subscriptions')),['class'=>'text-end'])
            );

            $sections.=html_writer::tag('article',
                html_writer::tag('h3',self::flag((string)$currency).' '.s((string)$currency),['class'=>'m53-product-payments-title']).
                html_writer::div(
                    html_writer::tag('table',
                        html_writer::tag('thead',$head).html_writer::tag('tbody',$body),
                        ['class'=>'table table-hover align-middle mb-0 m53-product-payments-table']
                    ),
                    'table-responsive'
                ),
                ['class'=>'m53-chart-card m53-product-payments-card']
            );
        }
        return html_writer::div($sections,'m53-product-payments-grid');
    }

    private static function premium_tree_node(
        string $class,
        int $value,
        string $label,
        float $percentage,
        string $icon
    ): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag('i','',['class'=>$icon,'aria-hidden'=>'true']),
                'm53-tree-node-icon'
            ).
            html_writer::div(
                html_writer::tag('strong',s((string)$value)).
                html_writer::tag('span',s($label)).
                html_writer::tag('small',s(format_float($percentage,1)).' %'),
                'm53-tree-node-copy'
            ),
            'm53-tree-node m53-tree-node--premium m53-tree-node--'.$class
        );
    }

    private static function tree_node(string $class,int $value,string $label,float $percentage): string {
        return html_writer::div(
            html_writer::tag('strong',s((string)$value)).
            html_writer::tag('span',s($label)).
            html_writer::tag('small',s(format_float($percentage,1)).' %'),
            'm53-tree-node m53-tree-node--'.$class
        );
    }

    private static function payment_cell(int $value,string $class=''): string {
        $content=html_writer::tag('span',s((string)$value),[
            'class'=>'m53-payment-count'.($class!==''?' m53-payment-count--'.$class:'')
        ]);
        return html_writer::tag('td',$content,['class'=>'text-end']);
    }

    public static function revenue(\renderer_base $output,array $series): string {
        if(!$series)return'';
        $cards='';
        foreach($series as $currency=>$item){
            $title=get_string('commerce_m53_revenue_evolution','local_subscriptions').' · '.$currency;
            $labels=$item->labels();$instant=array_map(static fn($v)=>$v/100,$item->values());
            $cumulative=[];$running=0.0;foreach($instant as $v){$running+=$v;$cumulative[]=$running;}
            $a=new \core\chart_line();$a->set_title($title);$a->set_labels($labels);$a->add_series(new \core\chart_series($currency,$instant));
            $b=new \core\chart_line();$b->set_title($title);$b->set_labels($labels);$b->add_series(new \core\chart_series($currency,$cumulative));
            $select=html_writer::select([
                'period'=>get_string('commerce_m52_revenue_period','local_subscriptions'),
                'cumulative'=>get_string('commerce_m52_revenue_cumulative','local_subscriptions'),
            ],'','period',false,['class'=>'form-select form-select-sm m53-revenue-mode']);
            $cards.=html_writer::tag('article',
                html_writer::div(html_writer::tag('h3',s($title),['class'=>'m53-chart-title']).$select,'m53-chart-head').
                html_writer::div($output->render($a),'m53-revenue-chart m53-revenue-chart--period').
                html_writer::div($output->render($b),'m53-revenue-chart m53-revenue-chart--cumulative d-none'),
                ['class'=>'m53-chart-card m53-revenue-chart-card']
            );
        }
        return html_writer::div($cards,'m53-revenue-grid');
    }

    public static function orders(\renderer_base $output,array $series): string {
        if(!$series)return'';
        $cards='';
        foreach($series as $currency=>$item){
            $title=get_string('commerce_m53_paid_orders_evolution','local_subscriptions').' · '.$currency;
            $chart=new \core\chart_bar();$chart->set_title($title);$chart->set_labels($item->labels());
            $chart->add_series(new \core\chart_series($currency,$item->values()));
            $cards.=self::chart_card($output->render($chart),$title);
        }
        return html_writer::div($cards,'m53-orders-grid');
    }

    public static function payments(array $payments): string {
        $cards='';
        $defs=[
            'paid'=>['commerce_m51_payment_paid','#26a269'],
            'pending'=>['commerce_m51_payment_pending','#e7a725'],
            'failed'=>['commerce_m51_payment_failed','#dc4455'],
            'cancelled'=>['commerce_m51_payment_cancelled','#8d8792'],
            'refunded'=>['commerce_m51_payment_refunded','#7853a7'],
        ];
        foreach($payments as $currency=>$values){
            $total=array_sum($values);if($total<=0)continue;
            $cursor=0.0;$stops=[];$legend='';
            foreach($defs as $key=>$def){
                $count=(int)($values[$key]??0);if(!$count)continue;
                $pct=$count/$total*100;$start=$cursor;$cursor+=$pct;
                $stops[]=sprintf('%s %.4F%% %.4F%%',$def[1],$start,$cursor);
                $legend.=html_writer::tag('li',
                    html_writer::tag('span','',['class'=>'m53-pie-swatch','style'=>'background:'.$def[1]]).
                    html_writer::tag('span',s(get_string($def[0],'local_subscriptions')),['class'=>'m53-pie-label']).
                    html_writer::tag(
                        'strong',
                        s((string)$count).' · '.s(format_float($pct,1)).' %',
                        ['class'=>'m53-pie-value']
                    )
                );
            }
            $pie=html_writer::tag('div','',['class'=>'m53-pie','style'=>'background:conic-gradient('.implode(',',$stops).')']);
            $title=get_string('commerce_m53_payment_distribution','local_subscriptions').' · '.$currency;
            $cards.=html_writer::tag('article',
                html_writer::tag('h3',self::flag((string)$currency).' '.s($title),['class'=>'m53-chart-title']).
                html_writer::div($pie.html_writer::tag('ul',$legend,['class'=>'m53-pie-legend']),'m53-pie-layout'),
                ['class'=>'m53-chart-card m53-pie-card']
            );
        }
        return $cards===''?'':html_writer::div($cards,'m53-pie-grid');
    }

    public static function top_products(\renderer_base $output,array $rows): string {
        $cards='';
        foreach($rows as $currency=>$items){
            if(!$items)continue;
            $title=get_string('commerce_statistics_chart_top_products','local_subscriptions').' · '.$currency;
            $chart=new \core\chart_bar();$chart->set_title($title);$chart->set_horizontal(true);
            $chart->set_labels(array_map(static fn($r)=>format_string($r['label']),$items));
            $chart->add_series(new \core\chart_series($currency,array_map(static fn($r)=>$r['revenue_minor']/100,$items)));
            $cards.=self::chart_card($output->render($chart),$title);
        }
        return $cards===''?'':html_writer::div($cards,'m53-top-products-grid');
    }

    private static function chart_card(string $chart,string $title): string {
        return html_writer::tag('article',
            html_writer::tag('h3',s($title),['class'=>'m53-chart-title']).
            html_writer::div($chart,'m53-chart-body crm-commerce-statistics-chart'),
            ['class'=>'m53-chart-card']
        );
    }

    private static function trend(int|float $current,int|float $previous): string {
        if($previous==0){
            if($current==0)return html_writer::tag('span','0 %',['class'=>'m53-trend m53-trend--flat']);
            return html_writer::tag('span','↑ '.s(get_string('commerce_m51_trend_new','local_subscriptions')),['class'=>'m53-trend m53-trend--up']);
        }
        $pct=(($current-$previous)/$previous)*100;
        if(abs($pct)<.5)return html_writer::tag('span','0 %',['class'=>'m53-trend m53-trend--flat']);
        $up=$pct>0;
        return html_writer::tag('span',($up?'↑ +':'↓ −').(int)round(abs($pct)).' %',['class'=>'m53-trend '.($up?'m53-trend--up':'m53-trend--down')]);
    }

    private static function flag(string $currency): string {
        return match(strtoupper($currency)){'EUR'=>'🇪🇺','RUB'=>'🇷🇺',default=>'🌐'};
    }
}
