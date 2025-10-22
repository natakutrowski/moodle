<?php
namespace local_subscriptions\output;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../lib/plans_lib.php');
require_once(__DIR__ . '/../../lib/scopes_lib.php');

use plugin_renderer_base;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\support\Region;
use local_subscriptions\support\SubsPresenter;

class renderer extends plugin_renderer_base {

    public function render_user_subscriptions_block(array $subscriptions): string {
        global $DB;

        $now   = time();
        $week  = 7 * 24 * 3600;

        $plans = [];
        $accessscopes = [];

        // Flags globaux
        $hasActive = false;

        // Index des QUEUED (<7 jours) par scope (empêche l’alerte “prolongez”)
        $queuedSoonByScope = [];

        // Repérage de la QUEUED la plus proche (même si > 7 jours), pour le cas D
        $earliestQueued = null;

        foreach ($subscriptions as $s) {
            $stlc = \core_text::strtolower((string)($s->status ?? ''));
            if ($stlc === Status::QUEUED) {
                // Earliest queued (même si > 7j)
                if ($earliestQueued === null || $s->start_date < $earliestQueued->start_date) {
                    $earliestQueued = $s;
                }
                // queued < 7 jours => indexée par scope
                if ($s->start_date >= $now && $s->start_date <= $now + $week) {
                    if (!isset($plans[$s->planid])) {
                        $plans[$s->planid] = $DB->get_record('subscription_plan', ['id' => $s->planid], 'id,accessscopeid,name,is_recurring');
                    }
                    $queuedSoonByScope[$plans[$s->planid]->accessscopeid] = true;
                }
            } else if ($stlc === Status::ACTIVE) {
                $hasActive = true;
            }
        }

        $data = [
            'subscriptions' => [],
            'mysubs_url'    => (UrlFactory::my_subscriptions())->out(false),
            'subscribe_url' => (UrlFactory::subscribe())->out(false),
        ];

        foreach ($subscriptions as $sub) {
            // Statut réel (privilégie la DB ; fallback = actif/expiré selon dates)
            $status = isset($sub->status) && $sub->status !== ''
                ? \core_text::strtolower((string)$sub->status)
                : (($sub->end_date > 0 && $sub->end_date < $now) ? Status::EXPIRED : Status::ACTIVE);

            // 1) n'afficher que ACTIVE
            // 2) et QUEUED qui démarrent dans < 7 jours
            $include = false;
            if ($status === Status::ACTIVE) {
                $include = true;
            } else if ($status === Status::QUEUED && $sub->start_date >= $now && $sub->start_date <= $now + $week) {
                $include = true;
            }
            if (!$include) { continue; }

            // Récupération du plan
            if (!isset($plans[$sub->planid])) {
                $plans[$sub->planid] = $DB->get_record('subscription_plan', ['id' => $sub->planid]);
            }
            $plan = $plans[$sub->planid];

            $planname = local_subscriptions_plan_display_name($plan);

            // Récupération du scope
            if (!isset($accessscopes[$plan->accessscopeid])) {
                $accessscopes[$plan->accessscopeid] = $DB->get_record('subscription_access_scope', ['id' => $plan->accessscopeid]);
            }
            $scope = $accessscopes[$plan->accessscopeid];

            $scopename = local_subscriptions_scope_display_name($scope);

            // Récupération des noms de cours
            $coursenames = [];
            if (!empty($scope->course_ids)) {
                $course_ids = explode(',', $scope->course_ids);
                list($sql, $params) = $DB->get_in_or_equal($course_ids);
                $courses = $DB->get_records_select('course', 'id ' . $sql, $params);
                foreach ($courses as $course) {
                    $coursenames[] = format_string($course->fullname);
                }

                // Tri alphabétique respectueux de la locale
                \core_collator::asort($coursenames);
                $coursenames = array_values($coursenames); // réindexe proprement
            }

            // Badge HTML (ta méthode dans SubsPresenter)
            $statusbadge = SubsPresenter::render_status_badge($status);

            // Classes de carte (par défaut)
            $bordercls = match ($status) {
                Status::ACTIVE   => 'border-success',
                Status::QUEUED   => 'border-secondary',
                default          => 'border-light text-dark'
            };

            // Alerte renouvellement (ACTIVE < 7j ET pas de queuedSoon pour le même scope)
            $showwarning = false;
            $renewmsg    = '';
            if ($status === Status::ACTIVE && $sub->end_date > 0) {
                $left = $sub->end_date - $now;
                if ($left > 0 && $left <= $week && empty($queuedSoonByScope[$plan->accessscopeid])) {
                    $showwarning = true;
                    $daysleft = max(1, (int)ceil($left / 86400.0));
                    $renewmsg  = get_string('renew_soon_msg', 'local_subscriptions', $daysleft);
                    $bordercls = 'border-warning';
                }
            }

            // Message “démarre dans X jours” pour les QUEUED visibles
            $queuedMsg = '';
            if ($status === Status::QUEUED) {
                $delta = max(0, $sub->start_date - $now);
                $days  = max(0, (int)ceil($delta / 86400.0));
                $queuedMsg = get_string('queued_starts_in', 'local_subscriptions', $days);
            }

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
                'planname'           => format_string($planname),
                'startdate'          => userdate($sub->start_date, get_string('strftimedate', 'langconfig')),
                'enddate'            => userdate($sub->end_date, get_string('strftimedate', 'langconfig')),
                'accessscope'        => format_string($scopename),
                'coursenames'        => $coursenames,
                'pricepaid'          => sprintf('%.2f %s', $sub->pricepaid ?? 0, $sub->currency ?? ''),
                'statusbadge'        => $statusbadge,          // <<< NOUVEAU : badge HTML prêt
                'statusclass'        => $bordercls,            // compat avec ton CSS/markup existant
                // Génération du contenu HTML du popover
                'popovercontent' => htmlspecialchars($popovercontent, ENT_QUOTES, 'UTF-8'),
                // Nouveau : alerte renouvellement
                'show_warning'     => $showwarning,
                'renew_msg'        => $renewmsg,
                'renew_url'        => $data['subscribe_url'],
                'queued_msg'       => $queuedMsg, // affiché si non vide
            ];
        }

        // Cas D : aucune ACTIVE et aucune QUEUED < 7j ⇒ on affiche la QUEUED la plus proche (s’il y en a une)
        if (empty($data['subscriptions']) && !$hasActive && $earliestQueued) {
            // Plan & Scope
            if (!isset($plans[$earliestQueued->planid])) {
                $plans[$earliestQueued->planid] = $DB->get_record('subscription_plan',
                    ['id' => $earliestQueued->planid], 'id,accessscopeid,name,is_recurring');
            }
            $plan  = $plans[$earliestQueued->planid];
            $planname = local_subscriptions_plan_display_name($plan);

            if (!isset($accessscopes[$plan->accessscopeid])) {
                $accessscopes[$plan->accessscopeid] = $DB->get_record('subscription_access_scope',
                    ['id' => $plan->accessscopeid], 'id,name,course_ids');
            }
            $scope = $accessscopes[$plan->accessscopeid];
            $scopename = local_subscriptions_scope_display_name($scope);

            // Courses (pour popover)
            $coursenames = [];
            if (!empty($scope->course_ids)) {
                $course_ids = explode(',', $scope->course_ids);
                [$sql, $params] = $DB->get_in_or_equal($course_ids);
                $courses = $DB->get_records_select('course', 'id '.$sql, $params);
                foreach ($courses as $course) {
                    $coursenames[] = format_string($course->fullname);
                }

                // Tri alphabétique respectueux de la locale
                \core_collator::asort($coursenames);
                $coursenames = array_values($coursenames); // réindexe proprement
            }
            $popovercontent = \html_writer::div(
                \html_writer::alist($coursenames)
            . \html_writer::empty_tag('hr')
            . \html_writer::link('#', '❌ '.get_string('close', 'local_subscriptions'), [
                    'class' => 'close-popover text-danger text-decoration-none d-block mt-2 text-end'
                ]),
                '',
                ['style' => 'min-width: 200px;']
            );

            $days = max(0, (int)ceil(($earliestQueued->start_date - $now) / 86400.0));
            $queuedMsg = get_string('queued_starts_in', 'local_subscriptions', $days);

            $data['subscriptions'][] = [
                'planname'         => format_string($planname),
                'startdate'        => userdate($earliestQueued->start_date, get_string('strftimedate', 'langconfig')),
                'enddate'          => userdate($earliestQueued->end_date,   get_string('strftimedate', 'langconfig')),
                'accessscope'      => format_string($scopename),
                'coursenames'      => $coursenames,
                'pricepaid'        => sprintf('%.2f %s', $earliestQueued->pricepaid ?? 0, $earliestQueued->currency ?? ''),
                'statusbadge'      => SubsPresenter::render_status_badge('queued'),
                'statusclass'      => 'border-secondary',
                'popovercontent'   => htmlspecialchars($popovercontent, ENT_QUOTES, 'UTF-8'),

                'show_warning'     => false,
                'renew_msg'        => '',
                'renew_url'        => $data['subscribe_url'],

                'queued_msg'       => $queuedMsg,
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
            $style   = 'border-radius:12px;';
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
                $output .= \html_writer::div(get_string('highlight_popular', 'local_subscriptions'), 'ls-badge-popular');
            } else if ($isPremium) {
                $output .= \html_writer::div(get_string('highlight_premium', 'local_subscriptions'), 'ls-badge-premium');
            }

            $titleclass = 'plan-title-neutral p-2 rounded mb-2 text-center';
            if ($isPopular)  { $titleclass .= ' ls-title-popular'; }
            if ($isPremium)  { $titleclass .= ' ls-title-premium'; }

            $displayname = \local_subscriptions_plan_display_name($plan);
            $output .= \html_writer::tag('h4', format_string($displayname), ['class'=>$titleclass]);

            $output .= \html_writer::tag('p', get_string('duration', 'local_subscriptions') . ' : ' . $durationtext, ['class' => 'mb-1']);
     
            $output .= \html_writer::tag('p', get_string('courselist','local_subscriptions').' : ', ['class' => 'mb-1']);
            $output .= \html_writer::div($courselist, 'plan-courselist mb-3');

            // lien (ancre) aspect bouton
            $output .= \html_writer::div($this->plan_description_link($plan), 'mb-1');
            $output .= $this->plan_description_modal_once($plan);

            // Récupérer les prix du plan
            $prices = $DB->get_records('subscription_plan_price', ['planid' => $plan->id]);
            $prices = array_values($prices); // Réindexe avec 0, 1, 2, etc.

            // Devise par défaut selon pays (RU/BY -> RUB), sinon 1ère dispo.
            $countrycode = Region::detect_country();

            // Mapping “préférence forte”
            $currencybycountry = [
                //'SE' => 'RUB',
                'RU' => 'RUB',
                'BY' => 'RUB',
                'FR' => 'EUR',
                'CA' => 'CAD',
                'US' => 'USD',
            ];

            $preferredcurrency = $currencybycountry[$countrycode] ?? null;

            // Indexe les prix par devise
            $pricebycurrency = [];
            foreach ($prices as $p) {
                $pricebycurrency[$p->currency] = $p->price;
            }

            // Choix final : préférée si dispo, sinon première
            $defaultcurrency = $preferredcurrency && array_key_exists($preferredcurrency, $pricebycurrency)
                ? $preferredcurrency
                : (array_key_first($pricebycurrency));

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
                UrlFactory::checkout($plan->id, $defaultcurrency),
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

    private array $descmodalsprinted = [];

        // ---- liens/boutons ----
    public function plan_description_link(\stdClass $plan, ?string $label=null, string $variant='outline-secondary', string $size='sm'): string {
        // Par défaut: "Afficher la description"
        $label = $label ?? get_string('plan_description_show', 'local_subscriptions');
        $id = (int)$plan->id;

        // Petite icône "info" avant le texte (FA4/Boost: fa-info-circle)
        $icon = \html_writer::tag('i', '', [
            'class' => 'icon fa fa-info-circle fa-fw me-1',
            'aria-hidden' => 'true'
        ]);

        // Lien texte (pas de .btn), ouvre la modale Bootstrap
        return \html_writer::link('#plan-desc-'.$id, $icon . s($label), [
            'class'          => 'ls-plan-desc-link link-secondary',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#plan-desc-'.$id,
            'aria-haspopup'  => 'dialog',
            'aria-controls'  => 'plan-desc-'.$id,
            'title'          => $label,
            'role'           => 'button' // hint d’accessibilité pour un lien qui déclenche une action
        ]);
    }


    public function plan_description_button(\stdClass $plan, ?string $label=null, string $variant='outline-secondary', string $size='sm'): string {
        $label = $label ?? get_string('plan_description_show', 'local_subscriptions'); // "Description"
        $id = (int)$plan->id;
        return \html_writer::tag('button', $label, [
            'type' => 'button',
            'class' => "btn btn-{$variant} btn-{$size} ls-plan-desc-btn",
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#plan-desc-'.$id,
        ]);
    }

    // Modale (imprimée 1 seule fois par plan) – utilise les traductions + rewrite pluginfile.
    public function plan_description_modal_once(\stdClass $plan): string {
        $id = (int)$plan->id;
        if (isset($this->descmodalsprinted[$id])) {
            return '';
        }
        $this->descmodalsprinted[$id] = true;

        // Nom d’affichage (ton helper de *name* existant, on ne change pas la stratégie ici).
        $name = local_subscriptions_plan_display_name($plan);

        // Description HTML avec fallback: lang courante → FR → autre → '-'
        // + réécriture @@PLUGINFILE@@ sur filearea 'plan_desc' (legacy-safe).
        $deschtml = local_subscriptions_plan_description_html(
            $id,
            \context_system::instance(),
            current_language(),
            '-' // texte si aucune description traduite
        );

        $data = ['id' => $id, 'name' => $name, 'descriptionhtml' => $deschtml];
        return $this->render_from_template('local_subscriptions/plan_description_modal', $data);
    }
    
}
