<?php
namespace local_subscriptions\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_user_subscriptions_block(array $subscriptions): string {
        global $DB;

        $plans = [];
        $accessscopes = [];
        $data = [
            'subscriptions' => [],
            'mysubs_url'    => (new \moodle_url('/user/my_subscriptions.php'))->out(false),
            'subscribe_url' => (new \moodle_url('/subscribe.php'))->out(false),
        ];

        foreach ($subscriptions as $sub) {
            // Récupération du plan
            if (!isset($plans[$sub->planid])) {
                $plans[$sub->planid] = $DB->get_record('subscription_plan', ['id' => $sub->planid]);
            }
            $plan = $plans[$sub->planid];

            // Récupération du scope
            if (!isset($accessscopes[$plan->accessscopeid])) {
                $accessscopes[$plan->accessscopeid] = $DB->get_record('subscription_access_scope', ['id' => $plan->accessscopeid]);
            }
            $scope = $accessscopes[$plan->accessscopeid];

            // Récupération des noms de cours
            $coursenames = [];
            if (!empty($scope->course_ids)) {
                $course_ids = explode(',', $scope->course_ids);
                list($sql, $params) = $DB->get_in_or_equal($course_ids);
                $courses = $DB->get_records_select('course', 'id ' . $sql, $params);
                foreach ($courses as $course) {
                    $coursenames[] = format_string($course->fullname);
                }
            }

            $expired = $sub->end_date < time();
            $statuskey = $expired ? 'expired' : 'active';

            $popovercontent = \html_writer::div(
                \html_writer::alist($coursenames) .
                \html_writer::empty_tag('hr') .
                \html_writer::link('#', '❌ '.get_string('close', 'local_subscriptions'), [
                    'class' => 'close-popover text-danger text-decoration-none d-block mt-2 text-end'
                ]),
                '',
                ['style' => 'min-width: 200px;']
            );

            $data['subscriptions'][] = [
                'planname'           => format_string($plan->name),
                'startdate'          => userdate($sub->start_date, get_string('strftimedate', 'langconfig')),
                'enddate'            => userdate($sub->end_date, get_string('strftimedate', 'langconfig')),
                'accessscope'        => format_string($scope->name ?? ''),
                'coursenames'        => $coursenames,
                'pricepaid'          => sprintf('%.2f %s', $sub->pricepaid ?? 0, $sub->currency ?? ''),
                'statuskey'          => $statuskey,
                'statusclass'        => $expired ? 'border-danger' : 'border-success',
                'statusclassbadge'   => $expired ? 'danger' : 'success',
                'expired'            => $expired,
                // Génération du contenu HTML du popover
                'popovercontent' => htmlspecialchars($popovercontent, ENT_QUOTES, 'UTF-8'),

            ];
        }

        return $this->render_from_template('local_subscriptions/myprofile_subscriptions', $data);
    }

    public function render_available_plans($plans, $selectedcurrency = null): string {

        global $DB;

        $featuredSetting = (int) get_config('local_subscriptions', 'featured_planid'); // fallback

        $output = \html_writer::start_div('subscription-plan-grid d-flex flex-wrap justify-content-start gap-3');

        foreach ($plans as $plan) {
            $durationtext = "<strong>".(\local_subscriptions\subscription_config::get_plans()[$plan->duration_key] ?? $plan->duration_key)."</strong>";
            $plan->courses = local_subscriptions_get_courses_by_plan($plan->id);

            $courselist = '';
            if (!empty($plan->courses)) {
                $courselist .= \html_writer::start_tag('ul', ['class' => 'list-unstyled courselist']);
                foreach ($plan->courses as $course) {
                    $descid = 'desc-' . $plan->id . '-' . $course->id;
                    $courselist .= \html_writer::start_tag('li', ['class' => 'course-item mb-1']);
                    // Après (évite le doublon):
                    $label = $course->fullname;
                    $hasicon = preg_match('/^\x{1F4D8}/u', $label); // 📘 en UTF-8
                    if (!$hasicon) {
                        $label = '&#x1F4D8; ' . $label; // entité => pas de mojibake
                    }
                    $courselist .= \html_writer::tag('a', $label, [
                        'href' => '#',
                        'class' => 'coursename',
                        'data-toggle' => 'desc-toggle',
                        'data-target' => '#' . $descid
                    ]);
                    $courselist .= \html_writer::tag('div', $course->summary, [
                        'id' => $descid,
                        'class' => 'course-desc mt-1 d-none',
                    ]);
                    $courselist .= \html_writer::end_tag('li');
                }
                $courselist .= \html_writer::end_tag('ul');
            }

            $hl = isset($plan->highlight_type) ? trim((string)$plan->highlight_type) : '';
            if (!$hl && $featuredSetting && (int)$plan->id === $featuredSetting) {
                $hl = 'popular'; // fallback rétro-compat.
            }

            $isPopular = ($hl === 'popular');
            $isPremium = ($hl === 'premium');

            $classes = 'card plan-card p-3 flex-fill position-relative';
            $style   = 'max-width:32%; min-width:300px; border-radius:12px;';
            if ($isPopular) {
                $classes .= ' ls-card-popular border-2 shadow-xl';
                $style   .= ' border:2px solid #ffc107;';
            } else if ($isPremium) {
                $classes .= ' ls-card-premium border-2 shadow-xl';
                $style   .= ' border:2px solid #8a2be2;'; // violet électrique
            } else {
                $style   .= ' border:1px solid #ccc;';
            }
            $output .= \html_writer::start_div($classes, ['style'=>$style]);

            // Badge
            if ($isPopular) {
                $output .= \html_writer::div(get_string('popular_badge', 'local_subscriptions'), 'ls-badge-popular');
            } else if ($isPremium) {
                $output .= \html_writer::div(get_string('premium_badge', 'local_subscriptions'), 'ls-badge-premium');
            }

            $titleclass = 'plan-title-neutral p-2 rounded mb-2 text-center';
            if ($isPopular)  { $titleclass .= ' ls-title-popular'; }
            if ($isPremium)  { $titleclass .= ' ls-title-premium'; }
            $output .= \html_writer::tag('h4', $plan->name, ['class'=>$titleclass]);

            if (!empty($plan->description)) {
                $output .= \html_writer::div($plan->description, 'plan-description text-muted mb-2', []);
            }
            $output .= \html_writer::tag('p', get_string('duration', 'local_subscriptions') . ' : ' . $durationtext, ['class' => 'mb-1']);
     
            $output .= \html_writer::tag('p', 'Liste des cours : ', ['class' => 'mb-1']);
            $output .= \html_writer::div($courselist, 'plan-courselist mb-3');


            // Récupérer les prix du plan
            $prices = $DB->get_records('subscription_plan_price', ['planid' => $plan->id]);
            $prices = array_values($prices); // Réindexe avec 0, 1, 2, etc.

            // Devise par défaut selon IP ou fallback sur la première.
            $countrycode = get_user_country_code() ?? '';
            $currencybycountry = [
                'RU' => 'RUB',
                'FR' => 'EUR',
                'CA' => 'CAD',
                'US' => 'USD'
                // Ajoute d'autres pays ici si besoin
            ];
            $preferredcurrency = $currencybycountry[$countrycode] ?? $prices[0]->currency;

            // Récupère le prix par devise préférée.
            $pricebycurrency = [];
            foreach ($prices as $p) {
                $pricebycurrency[$p->currency] = $p->price;
            }
            $defaultcurrency = array_key_exists($preferredcurrency, $pricebycurrency) ? $preferredcurrency : array_key_first($pricebycurrency);
            $defaultprice = $pricebycurrency[$defaultcurrency];

            $output .= \html_writer::start_div('bottom-zone mt-auto');

            $output .= \html_writer::div(get_string('price', 'local_subscriptions'), 'text-muted small mb-1'); // "Price" (ou ajoute ta string)
            $output .= \html_writer::start_div('plan-price-block mb-2', ['id' => "plan-price-{$plan->id}"]);
            $output .= \html_writer::span("<strong>{$defaultprice} {$defaultcurrency}</strong>", 'selected-price me-2', [
                'data-planid' => $plan->id
            ]);

            if (count($prices) > 1) {
                $output .= \html_writer::tag('a', get_string('change_currency', 'local_subscriptions'), [
                    'href' => '#',
                    'class' => 'change-currency-link small',
                    'data-planid' => $plan->id
                ]);

                $options = '';
                foreach ($prices as $p) {
                    $selected = ($p->currency === $defaultcurrency) ? ' selected' : '';
                    $options .= "<option value='{$p->currency}' data-price='{$p->price}'{$selected}>{$p->price} {$p->currency}</option>";
                }
                $output .= "<select id='currency-selector-{$plan->id}' class='form-select form-select-sm currency-selector mt-2' data-planid='{$plan->id}' style='display:none; max-width: 150px'>{$options}</select>";
            }

            if (!empty($plan->is_recurring)) {
                $output .= \html_writer::span(get_string('badge_recurring', 'local_subscriptions'), 'badge bg-info ms-2');
            }

            $output .= \html_writer::end_div(); // fin plan-price-block
 
            // Bouton Subscribe
            $btnclass = 'btn subscribe-button w-100 fs-5';
            if ($isPopular) {
                $btnclass .= ' ls-btn-popular';
            } else if ($isPremium) {
                $btnclass .= ' ls-btn-premium';
            } else {
                $btnclass .= ' btn-outline-primary';
            }

            $output .= \html_writer::link(
                new \moodle_url('/local/subscriptions/checkout.php', [
                    'planid' => $plan->id,
                    'currency' => $defaultcurrency
                ]),
                get_string('subscribe', 'local_subscriptions'),
                [
                    'class' => $btnclass,
                    'data-planid' => $plan->id
                ]
            );
         

            $output .= \html_writer::end_div(); // fin bottom-zone
            $output .= \html_writer::end_div(); // card
        }

        $output .= \html_writer::end_div(); // grid

        // Ajout du JS pour toggler la description
        $output .= \html_writer::script("
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[data-toggle=\"desc-toggle\"]').forEach(function(trigger) {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        var target = document.querySelector(trigger.getAttribute('data-target'));
                        if (target) {
                            target.classList.toggle('d-none');
                        }
                    });
                });
            });
        ");

        $output .= \html_writer::script("
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.currency-selector').forEach(function(select) {
                    select.addEventListener('change', function() {
                        const planid = this.dataset.planid;
                        const selectedCurrency = this.value;

                        const button = document.querySelector('.subscribe-button[data-planid=\"' + planid + '\"]');
                        if (button) {
                            const baseUrl = new URL(button.href);
                            baseUrl.searchParams.set('currency', selectedCurrency);
                            button.href = baseUrl.toString();
                        }
                    });
                });
            });
        ");

        return $output;
    }



    
}
