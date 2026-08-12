<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;
use moodle_url;

/** Premium reusable filter toolbar for global Commerce analytics. */
final class CommerceGlobalStatisticsFilterRenderer {
    public static function render(
        moodle_url $action,
        string $period,
        string $from,
        string $until,
        string $currency,
        string $provider,
        moodle_url $exporturl
    ): string {
        $html=html_writer::start_tag('form',[
            'method'=>'get','action'=>$action->out(false),'class'=>'m53-stat-toolbar'
        ]);
        $html.=self::field(
            'period',
            get_string('commerce_statistics_period','local_subscriptions'),
            html_writer::select(CommerceStatisticsPeriodResolver::options(),'period',$period,false,[
                'id'=>'m53-period','class'=>'form-select'
            ])
        );
        $html.=html_writer::start_div('m53-custom-range'.($period==='custom'?'':' d-none'),['id'=>'m53-custom-range']);
        $html.=self::field('from',get_string('commerce_statistics_period_from','local_subscriptions'),
            html_writer::empty_tag('input',['type'=>'date','name'=>'from','value'=>$from,'class'=>'form-control']));
        $html.=self::field('until',get_string('commerce_statistics_period_until','local_subscriptions'),
            html_writer::empty_tag('input',['type'=>'date','name'=>'until','value'=>$until,'class'=>'form-control']));
        $html.=html_writer::end_div();

        $html.=self::field('currency',get_string('commerce_statistics_currency','local_subscriptions'),
            html_writer::select([
                ''=>get_string('commerce_statistics_all_currencies','local_subscriptions'),
                'EUR'=>'🇪🇺 EUR','RUB'=>'🇷🇺 RUB',
            ],'currency',$currency,false,['class'=>'form-select']));
        $html.=self::field('provider',get_string('commerce_statistics_provider','local_subscriptions'),
            html_writer::select([
                ''=>get_string('commerce_statistics_all_providers','local_subscriptions'),
                'stripe'=>'Stripe','alfa'=>'Alfa-Bank',
            ],'provider',$provider,false,['class'=>'form-select']));

        $html.=html_writer::div(
            html_writer::empty_tag('input',['type'=>'submit','class'=>'btn btn-primary','value'=>get_string('applyfilters')]),
            'm53-toolbar-action'
        );
        $html.=html_writer::div(
            html_writer::link($exporturl,
                html_writer::tag('i','',['class'=>'fa-solid fa-file-excel me-2','aria-hidden'=>'true']).
                s(get_string('commerce_m53_export_excel','local_subscriptions')),
                ['class'=>'btn btn-outline-secondary']
            ),
            'm53-toolbar-action'
        );
        $html.=html_writer::end_tag('form');

        $html.='<script>document.addEventListener("DOMContentLoaded",function(){var p=document.getElementById("m53-period"),r=document.getElementById("m53-custom-range");if(p&&r){p.addEventListener("change",function(){r.classList.toggle("d-none",this.value!=="custom");});}});</script>';
        return $html;
    }

    private static function field(string $name,string $label,string $control): string {
        return html_writer::div(
            html_writer::tag('label',s($label),['class'=>'form-label']).$control,
            'm53-toolbar-field m53-toolbar-field--'.$name
        );
    }
}
