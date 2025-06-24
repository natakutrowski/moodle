<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib/lib.php');

use local_subscriptions\subscription_config;

class local_subscriptions_plans_renderer extends plugin_renderer_base {

    /**
     * Affiche le bouton "Ajouter un plan".
     */
    public static function render_add_button(moodle_url $url): string {
        return html_writer::link($url, '➕ ' . get_string('addplan', 'local_subscriptions'), [
            'class' => 'btn btn-primary',
            'style' => 'margin-bottom: 1em; display: inline-block;'
        ]);
    }

    /**
     * Affiche la table des plans avec actions.
     */
    public static function render_plans_table(array $plans, string $currentlang, string $order, string $dir): string {
        global $DB, $OUTPUT;
        
        $upstyle = ($order === 'name' && $dir === 'asc') ? 'font-weight:bold;' : '';
        $downstyle = ($order === 'name' && $dir === 'desc') ? 'font-weight:bold;' : '';
        $sorticons = html_writer::link(
            new moodle_url(subscription_config::manage_page(), ['tab' => 'plans', 'order' => 'name', 'dir' => 'asc']),
            '🔼',
            ['style' => "text-decoration:none; $upstyle", 'title' => get_string('sortaz', 'local_subscriptions')]
        ) . ' ' .
        html_writer::link(
            new moodle_url(subscription_config::manage_page(), ['tab' => 'plans', 'order' => 'name', 'dir' => 'desc']),
            '🔽',
            ['style' => "text-decoration:none; $downstyle", 'title' => get_string('sortza', 'local_subscriptions')]
        );

        $table = new html_table();
        $table->head = [
            get_string('name', 'local_subscriptions') . ' ' . $sorticons,
            get_string('description', 'local_subscriptions'),
            get_string('scope', 'local_subscriptions'),
            get_string('duration', 'local_subscriptions'),
            get_string('dates', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions')
        ];
        $table->attributes['data-table'] = 'subscription-plans';
        $table->attributes['class'] = 'generaltable table table-bordered table-striped';
        $table->rowclasses = [];

        foreach ($plans as $p) {

            $translations = local_subscriptions_get_plan_translations($p->id); // par ex. [['lang' => 'en'], ['lang' => 'fr']]
            $prices = local_subscriptions_get_plan_prices($p->id); // par ex. [['currency' => 'EUR'], ['currency' => 'USD']]

            // 🈂️ Traductions
            $availablelangs = array_map(fn($t) => $t->lang, $translations);
            if (!empty($availablelangs)) {
                $translationtooltip = get_string('availabletranslations', 'local_subscriptions') . ': ';
                $translationtooltip .= implode(', ', array_map(function($lang) {
                    return local_subscriptions_get_lang_flag($lang) . ' ' . strtoupper($lang);
                }, $availablelangs));
            } else {
                $translationtooltip = get_string('notranslation', 'local_subscriptions');
            }

            // 💰 Devises
            $availablecurrencies = array_map(fn($p) => strtoupper($p->currency), $prices);
            if (!empty($availablecurrencies)) {
                $currencytooltip = get_string('availablecurrencies', 'local_subscriptions') . ': ';
                $currencytooltip .= implode(', ', array_map(function($cur) {
                    return subscription_config::get_currency_symbol($cur) . ' ' . $cur;
                }, $availablecurrencies));
            } else {
                $currencytooltip = get_string('nocurrency', 'local_subscriptions');
            }
            
            $scopelink = '';
            if ($scope = $DB->get_record('subscription_access_scope', ['id' => $p->access_scope_id], 'name', IGNORE_MISSING)) {
                $url = new moodle_url(subscription_config::manage_page(), [
                    'tab' => 'scopes',
                    'edit' => $p->access_scope_id,
                    'sesskey' => sesskey()
                ]);
                $scopelink = html_writer::link($url, $scope->name, ['target' => '_blank']);
            }

            $editurl = new moodle_url(subscription_config::manage_page(), [
                'tab' => 'plans',
                'edit' => $p->id,
                'sesskey' => sesskey()
            ]);
            $deleteurl = new moodle_url(subscription_config::manage_page(), [
                'tab' => 'plans',
                'delete' => $p->id,
                'sesskey' => sesskey()
            ]);
            $translationurl = new moodle_url(subscription_config::plans_translations_page(), [
                'planid' => $p->id,
                'sesskey' => sesskey()
            ]);
            $priceurl = new moodle_url(subscription_config::plans_prices_page(), [
                'planid' => $p->id,
                'sesskey' => sesskey()
            ]);

            $icons = [];
            // ✏️ Éditer
            $icons[] = html_writer::link($editurl,
                $OUTPUT->pix_icon('i/edit', get_string('editplan', 'local_subscriptions'), ''),
                [
                    'title' => get_string('editplan', 'local_subscriptions'),
                    'class' => 'me-2 action-icon'
                ]
            );

            // 🗑️ Supprimer
            $icons[] = html_writer::link('#',
                $OUTPUT->pix_icon('i/delete', get_string('deleteplan', 'local_subscriptions'), ''),
                [
                    'class' => 'deleteplan me-2 action-icon',
                    'data-deleteurl' => $deleteurl->out(false),
                    'data-name' => $p->name,
                    'data-id' => $p->id,
                    'title' => get_string('deleteplan', 'local_subscriptions')
                ]
            );

            // 🌐 Traductions
            $icons[] = html_writer::link($translationurl,
                $OUTPUT->pix_icon('i/siteevent', get_string('translatetooltip', 'local_subscriptions'), '', ['title' => $translationtooltip]),
                ['class' => 'me-2 action-icon']
            );

            // 💰 Prices
            $icons[] = html_writer::link($priceurl,
                $OUTPUT->pix_icon('m/USD', get_string('pricestooltip', 'local_subscriptions'), '', ['title' => $currencytooltip]),
                ['class' => 'me-2 action-icon']
            );

            // 👁️ Actif / Inactif avec icône <i> et spinner
            $iconclass = $p->is_active ? 'fa-eye' : 'fa-eye-slash';
            $label = $p->is_active
                ? get_string('deactivateplan', 'local_subscriptions')
                : get_string('activateplan', 'local_subscriptions');

            $iconhtml = html_writer::tag('i', '', [
                'class' => "fa {$iconclass}",
                'aria-hidden' => 'true',
                'title' => $label,
                'data-state' => $p->is_active ? 'active' : 'inactive',
            ]);

            $icons[] = html_writer::link('#', $iconhtml, [
                'class' => 'toggleplan me-2 action-icon',
                'data-id' => $p->id,
                'title' => $label,
            ]);

            // 🚨 Icône d’alerte si problème
            $alerts = [];

            if (empty($availablelangs)) {
                $alerts[] = get_string('notranslation', 'local_subscriptions');
            }
            if (empty($availablecurrencies)) {
                $alerts[] = get_string('nocurrency', 'local_subscriptions');
            }

            if (!empty($alerts)) {
                $warningtooltip = implode(' — ', $alerts);
                $icons[] = html_writer::div(
                    $OUTPUT->pix_icon('i/risk_xss', $warningtooltip, '', ['class' => 'icon-warning', 'style' => 'margin-left: 6px;']),
                    '',
                    ['class' => 'me-2 action-icon']
                );
            }

            // Conteneur flex aligné
            $actions = html_writer::div(implode('', $icons), '', ['class' => 'd-flex align-items-center']);

            $actions_container = html_writer::div($actions, 'subscription_actions');

            $created = userdate($p->creation_date);
            $updated = userdate($p->last_update);
            $is_updated = $p->last_update > $p->creation_date;

            $datescell = '<small>' . get_string('createdon', 'local_subscriptions') . ' <strong>' . $created . '</strong><br>';
            $datescell .= get_string('modifiedon', 'local_subscriptions') . ' <strong' . ($is_updated ? ' style="color:#007bff;"' : '') . '>' . $updated . '</strong></small>';

            if ($p->translated_name){
                $namecell = $p->translated_name . "<br><small><i>(" . $p->name . ")</i></small>";
            } else {                
                $namecell = "-<br><small><i>(" . $p->name . ")</i></small>";
            }

            $table->data[] = [
                $namecell,
                $p->translated_description ?? '-',
                $scopelink,
                subscription_config::get_plans()[$p->duration_key] ?? $p->duration_key,
                $datescell,
                $actions_container
            ];

            $table->rowclasses[] = $p->is_active ? '' : 'plan-inactive';
        }

        return html_writer::table($table);
    }

    public static function local_subscriptions_render_plans_translations_table(array $plans, array $translations, int $planid = 0, int $adding = 0, int $editing = 0): string {
        global $DB;

        $currentlang = current_language();
        $table = new html_table();
        $table->head = [
            get_string('plandefaultname', 'local_subscriptions'),
            get_string('translatedlanguages', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions')
        ];
        $table->attributes['class'] = 'generaltable table table-striped';

        foreach ($plans as $plan) {
            if (($planid && $plan->id != $planid) || ($adding && $plan->id != $adding)) {
                continue;
            }

            if ($editing) {
                $translation = $DB->get_record('subscription_plan_translation', ['id' => $editing], '*', MUST_EXIST);
                if ($plan->id != $translation->plan_id) {
                    continue;
                }
            }

            $rows = array_filter($translations, fn($t) => $t->plan_id == $plan->id);
            $rows = array_values($rows);

            $langues = [];
            foreach ($rows as $t) {
                $url = new moodle_url(subscription_config::plans_translations_page(), [
                    'edit' => $t->id,
                    'sesskey' => sesskey()
                ]);

                $flag = local_subscriptions_get_lang_flag($t->lang);
                $name = local_subscriptions_get_lang_name($t->lang);

                $langues[] = html_writer::link($url, $flag, [
                    'title' => $name
                ]);
            }

            $current = array_filter($rows, fn($t) => $t->lang === $currentlang);
            $current = reset($current);

            $translatedname = $current ? format_string($current->name) : format_string($plan->name);
            $tooltip = $current && !empty($current->description) ? s(strip_tags($current->description)) : '';
            $icon = $current ? ' 🌐' : '';

            $namediv = html_writer::tag('span', $translatedname . $icon, [
                'title' => $tooltip,
                'style' => $tooltip ? 'cursor: help;' : ''
            ]);

            $addurl = new moodle_url(subscription_config::plans_translations_page(), [
                'add' => $plan->id, 'sesskey' => sesskey()
            ]);

            $table->data[] = [
                $namediv,
                !empty($langues) ? implode(' ', $langues) : '-',
                html_writer::link($addurl, '➕ ' . get_string('addtranslation', 'local_subscriptions'))
            ];
        }

        return html_writer::table($table);
    }

    /**
     * Affiche le tableau des prix pour un plan.
     */
    public static function render_prices_table(array $prices): string {
        global $OUTPUT;

        if (empty($prices)) {
            return $OUTPUT->notification(get_string('noprices', 'local_subscriptions'), 'info');
        }

        $rows = [];
        foreach ($prices as $price) {
            $editurl = new moodle_url(subscription_config::plans_prices_page(), ['edit' => $price->id, 'planid' => $price->plan_id]);
            $deleteurl = new moodle_url(subscription_config::plans_prices_page(), ['delete' => $price->id, 'planid' => $price->plan_id, 'sesskey' => sesskey()]);
            
            $icons = [];

            // ✏️ Éditer
            $icons[] = html_writer::link($editurl,
                $OUTPUT->pix_icon('i/edit', get_string('editprice', 'local_subscriptions'), ''),
                [
                    'title' => get_string('editplan', 'local_subscriptions'),
                    'class' => 'me-2 action-icon'
                ]
            );

            // 🗑️ Supprimer
            $icons[] = html_writer::link('#',
                $OUTPUT->pix_icon('i/delete', get_string('deleteprice', 'local_subscriptions'), ''),
                [
                    'class' => 'deleteprice me-2 action-icon',
                    'data-deleteurl' => $deleteurl->out(false),
                    'data-currency' => $price->currency,
                    'data-id' => $price->id,
                    'title' => get_string('deleteprice', 'local_subscriptions')
                ]
            );


            // Conteneur flex aligné
            $actions = html_writer::div(implode('', $icons), '', ['class' => 'd-flex align-items-center']);

            $actions_container = html_writer::div($actions, 'subscription_actions');
                    
            $currencyicon = $price->currency === 'USD' ? '💵' :
                ($price->currency === 'EUR' ? '💶' :
                ($price->currency === 'RUB' ? '💴' : '💰'));

            $symbol = subscription_config::get_currency_symbol($price->currency); // € $ ₽ etc.

            $currency = $currencyicon . ' ' . $price->currency;


            $rows[] = html_writer::tag('tr',
                html_writer::tag('td', strtoupper($currency)) .
                html_writer::tag('td', format_float($price->price, 2) . ($symbol ? " $symbol" : " ".$price->currency)) .
                html_writer::tag('td', $actions_container)
            );
        }

        $table = html_writer::tag('table',
            html_writer::tag('thead', html_writer::tag('tr',
                html_writer::tag('th', get_string('currency', 'local_subscriptions')) .
                html_writer::tag('th', get_string('price', 'local_subscriptions')) .
                html_writer::tag('th', get_string('actions', 'local_subscriptions'))
            )) .
            html_writer::tag('tbody', implode('', $rows)),
            ['class' => 'generaltable table table-bordered table-striped']
        );

        return $table;
    }


}

