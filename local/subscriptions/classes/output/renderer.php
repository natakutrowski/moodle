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

    private static function format_end(?int $ts): string {
        if (empty($ts)) {
            return get_string('subfield_unlimited', 'local_subscriptions'); // 'Sans fin'
        }
        return userdate((int)$ts, get_string('strftimedate', 'langconfig'));
    }

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
                        $plans[$s->planid] = $DB->get_record('subscription_plan', ['id' => $s->planid],
                            'id,accessscopeid,name,is_recurring,is_trial');
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

            $planname    = local_subscriptions_plan_display_name($plan);
            $isTrialPlan = !empty($plan->is_trial);

            // Récupération du scope
            if (!isset($accessscopes[$plan->accessscopeid])) {
                $accessscopes[$plan->accessscopeid] = $DB->get_record('subscription_access_scope', ['id' => $plan->accessscopeid]);
            }
            $scope    = $accessscopes[$plan->accessscopeid];
            $scopename = local_subscriptions_scope_display_name($scope);

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

            // Prix : on le masque pour les plans d’essai
            $showPrice   = !$isTrialPlan;
            $priceString = '';

            if ($showPrice) {
                $amount = (float)($sub->pricepaid ?? 0);
                $cur    = (string)($sub->currency ?? '');
                $priceString = sprintf('%.2f %s', $amount, $cur);
            }


            $data['subscriptions'][] = [
                'planname'           => format_string($planname),
                'startdate'          => userdate($sub->start_date, get_string('strftimedate', 'langconfig')),
                'enddate'            => self::format_end($sub->end_date ?? null),
                'accessscope'        => format_string($scopename),
                'pricepaid'          => $priceString,
                'statusbadge'        => $statusbadge,          // <<< NOUVEAU : badge HTML prêt
                'statusclass'        => $bordercls,            // compat avec ton CSS/markup existant
                // Nouveau : alerte renouvellement
                'show_warning'     => $showwarning,
                'renew_msg'        => $renewmsg,
                'renew_url'        => $data['subscribe_url'],
                'queued_msg'       => $queuedMsg, // affiché si non vide
                'is_trial'     => $isTrialPlan ? true : false,  // bien un booléen
            ];
        }

        // Cas D : aucune ACTIVE et aucune QUEUED < 7j ⇒ on affiche la QUEUED la plus proche (s’il y en a une)
        if (empty($data['subscriptions']) && !$hasActive && $earliestQueued) {
            // Plan & Scope
            if (!isset($plans[$earliestQueued->planid])) {
                $plans[$earliestQueued->planid] = $DB->get_record('subscription_plan',
                    ['id' => $earliestQueued->planid], 'id,accessscopeid,name,is_recurring,is_trial');

            }
            $plan  = $plans[$earliestQueued->planid];
            $planname = local_subscriptions_plan_display_name($plan);

            $isTrialPlan = !empty($plan->is_trial);

            if (!isset($accessscopes[$plan->accessscopeid])) {
                $accessscopes[$plan->accessscopeid] = $DB->get_record('subscription_access_scope',
                    ['id' => $plan->accessscopeid], 'id,name,course_ids');
            }
            $scope = $accessscopes[$plan->accessscopeid];
            $scopename = local_subscriptions_scope_display_name($scope);

            $days = max(0, (int)ceil(($earliestQueued->start_date - $now) / 86400.0));
            $queuedMsg = get_string('queued_starts_in', 'local_subscriptions', $days);

            $showPrice   = !$isTrialPlan;
            $priceString = '';
            if ($showPrice) {
                $amount = (float)($earliestQueued->pricepaid ?? 0);
                $cur    = (string)($earliestQueued->currency ?? '');
                $priceString = sprintf('%.2f %s', $amount, $cur);
            }

            $data['subscriptions'][] = [
                'planname'         => format_string($planname),
                'startdate'        => userdate($earliestQueued->start_date, get_string('strftimedate', 'langconfig')),
                'enddate'          => self::format_end($earliestQueued->end_date ?? null),
                'accessscope'      => format_string($scopename),
                'pricepaid'        => $priceString,
                'statusbadge'      => SubsPresenter::render_status_badge('queued'),
                'statusclass'      => 'border-secondary',
                'show_warning'     => false,
                'renew_msg'        => '',
                'renew_url'        => $data['subscribe_url'],
                'queued_msg'       => $queuedMsg,
                'is_trial'     => $isTrialPlan ? true : false,
            ];
        }

        return $this->render_from_template('local_subscriptions/myprofile_subscriptions', $data);
    }

    public function render_available_plans($plans, $selectedcurrency = null): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

        $selectedcurrency = strtoupper((string)$selectedcurrency);
        if (!in_array($selectedcurrency, ['EUR','RUB'], true)) {
            $selectedcurrency = 'EUR';
        }

        // Si la page subscribe est en mode "embedded" (popup), on veut que les liens
        // vers checkout restent aussi en embedded=1.
        $embedded = (int)$this->page->url->param('embedded');

        $discountOpen = (isloggedin() && !isguestuser())
            ? \local_subscriptions\trial_manager::is_discount_window_open((int)$USER->id)
            : false;
        $discPct = (int)(get_config('local_subscriptions','trial_discount_percent') ?? 15);

        $featuredSetting = (int) get_config('local_subscriptions', 'featured_planid'); // fallback

        // On travaille sur une liste indexée pour pouvoir repositionner le "popular"
        $planlist = array_values($plans);
        $popularIndex = null;

        // Premier passage : déterminer highlight_type effectif et index du popular
        foreach ($planlist as $idx => $plan) {
            $hl = isset($plan->highlight_type) ? trim((string)$plan->highlight_type) : '';
            if (!$hl && $featuredSetting && (int)$plan->id === $featuredSetting) {
                $hl = 'popular'; // fallback rétro-compat.
            }
            $plan->highlight_effective = $hl;

            if ($hl === 'popular' && $popularIndex === null) {
                $popularIndex = $idx;
            }

            $planlist[$idx] = $plan;
        }

        // Si on a un plan "popular" et au moins 3 plans, on le place au milieu
        if ($popularIndex !== null && count($planlist) >= 3) {
            $popular = $planlist[$popularIndex];
            array_splice($planlist, $popularIndex, 1);           // retire de sa position
            $middle = (int) floor(count($planlist) / 2);         // index milieu
            array_splice($planlist, $middle, 0, [$popular]);     // réinsère au milieu
        }

        // Container principal (on passe en justify-content-center côté CSS)
        $output = \html_writer::start_div(
            'subscription-plan-grid d-flex flex-wrap justify-content-center gap-3'
        );

        foreach ($planlist as $plan) {
            $durationtext = "<strong>".(\local_subscriptions\subscription_config::get_plans()[$plan->duration_key] ?? $plan->duration_key)."</strong>";

            // Plus besoin de récupérer la liste des cours ici
            // $plan->courses = local_subscriptions_get_courses_by_plan($plan->id);

            $hl = $plan->highlight_effective ?? '';
            $isPopular = ($hl === 'popular');
            $isPremium = ($hl === 'premium');

            $classes = 'card plan-card p-3 flex-fill position-relative';
            $style   = 'border-radius:12px;';
            if ($isPopular) {
                $classes .= ' ls-card-popular shadow-xl';
                $style   .= ' border:2px solid #ffc107;';
            } else if ($isPremium) {
                $classes .= ' ls-card-premium shadow-xl';
                $style   .= ' border:2px solid #8a2be2;'; // violet électrique
            } else {
                $style   .= ' border:1px solid #ccc;';
            }
            $output .= \html_writer::start_div($classes, ['style'=>$style]);

            // Badge
            if ($isPopular) {
                $output .= \html_writer::div(
                    get_string('highlight_popular', 'local_subscriptions'),
                    'ls-badge-popular'
                );
            } else if ($isPremium) {
                $output .= \html_writer::div(
                    get_string('highlight_premium', 'local_subscriptions'),
                    'ls-badge-premium'
                );
            }

            $titleclass = 'plan-title-neutral p-2 rounded mb-2 text-center';
            if ($isPopular)  { $titleclass .= ' ls-title-popular'; }
            if ($isPremium)  { $titleclass .= ' ls-title-premium'; }

            $displayname = \local_subscriptions_plan_display_name($plan);
            $output .= \html_writer::tag('h4', format_string($displayname), ['class'=>$titleclass]);

            // Durée
            $output .= \html_writer::tag(
                'p',
                get_string('duration', 'local_subscriptions') . ' : ' . $durationtext,
                ['class' => 'mb-3 text-muted']
            );

            // === Zone centrale épurée : on laisse un espace blanc réglable ===
            $output .= \html_writer::div('', 'plan-middle-space mb-3');

            // === Description/Modal supprimées ===
            // $output .= \html_writer::div($this->plan_description_link($plan), 'mb-1');
            // $output .= $this->plan_description_modal_once($plan);

            // --- PRIX PAR PLAN / PRIX PERSONNALISÉ UTILISATEUR ---
            $info = \local_subscriptions\pricing_manager::get_plan_price_or_fallback($plan->id, $selectedcurrency, $DB);
            $usedCurrency = $info['currency'];
            $basePrice    = (float)$info['price'];
            $hasSelected  = (bool)$info['available'];

            // Si subscribe.php a enrichi le plan via SubscriptionAdvisor,
            // on utilise le vrai prix utilisateur : upgrade, remise trial, etc.
            $hasPersonalizedPrice = property_exists($plan, 'display_amount');

            $isUpgrade = !empty($plan->display_is_upgrade);
            $upgradeSummary = $plan->display_upgrade_summary ?? '';
            $upgradeBadge = property_exists($plan, 'display_badge') && !empty($plan->display_badge)
                ? $plan->display_badge
                : get_string('upgrade_badge', 'local_subscriptions');
            if ($hasPersonalizedPrice) {
                $finalPrice = (float)$plan->display_amount;
                $usedCurrency = $plan->display_currency ?? $usedCurrency;

                $discountPercent = !empty($plan->display_discount_percent)
                    ? (float)$plan->display_discount_percent
                    : 0;

                $discAmount = 0;
                $applyDiscount = false;

            } else {
                // Ancienne logique pour visiteurs non connectés.
                $isTrialPlan = isset($plan->is_trial)
                    ? (int)$plan->is_trial
                    : (int)$DB->get_field('subscription_plan', 'is_trial', ['id'=>$plan->id], IGNORE_MISSING);

                $applyDiscount = ($discountOpen && !$isTrialPlan && $discPct > 0);
                list($finalPrice, $discAmount) = $this->apply_discount($basePrice, $applyDiscount, $discPct);

                $discountPercent = $discAmount > 0 ? $discPct : 0;
            }

            // Zone prix.
            $output .= \html_writer::start_div('bottom-zone mt-auto');

            if ($isUpgrade) {
                $output .= \html_writer::div(
                    \html_writer::span($upgradeBadge, 'badge bg-primary me-2')
                    . s($upgradeSummary),
                    'alert alert-info py-2 px-3 mb-2 small'
                );
            }

            $output .= \html_writer::start_div('d-flex align-items-center justify-content-between mb-1');
            $output .= \html_writer::div(get_string('price', 'local_subscriptions'), 'text-muted small');

            if ($discountPercent > 0) {
                $output .= \html_writer::span(
                    get_string('badge_limited_offer','local_subscriptions', $discountPercent),
                    'badge bg-warning text-dark price-badge ms-2'
                );
            }

            $output .= \html_writer::end_div();

            // Bloc affichage prix.
            $output .= \html_writer::start_div('plan-price-block mb-2', [
                'id' => "plan-price-{$plan->id}",
            ]);

            $displayBasePrice = !empty($plan->display_base_amount)
                ? (float)$plan->display_base_amount
                : $basePrice;

            $displayFinalPrice = property_exists($plan, 'display_amount')
                ? (float)$plan->display_amount
                : $finalPrice;

            if ($displayBasePrice > $displayFinalPrice + 0.01) {
                $output .= \html_writer::span(
                    $this->format_money($displayBasePrice, $usedCurrency),
                    'old text-muted text-decoration-line-through me-2'
                );

                $output .= \html_writer::span(
                    '<strong class="new text-success">' . $this->format_money($displayFinalPrice, $usedCurrency) . '</strong>',
                    'selected-price'
                );
            } else {
                $output .= \html_writer::span(
                    '<strong>' . $this->format_money($displayFinalPrice, $usedCurrency) . '</strong>',
                    'selected-price'
                );
            }

            if (!$hasSelected) {
                $note = (object)['curr'=>$selectedcurrency, 'fallback'=>$usedCurrency];
                $output .= \html_writer::div(
                    get_string('price_unavailable_in','local_subscriptions', $note),
                    'text-muted small mt-1'
                );
            }

            $output .= \html_writer::end_div(); // plan-price-block

            // Prix équivalent par mois.
            $months = $this->months_for_plan($plan);
            if (!$isUpgrade && $months > 0 && $displayFinalPrice > 0 && $hasSelected) {
                $monthly = $displayFinalPrice / $months;
                $monthlyStr = $this->format_money($monthly, $usedCurrency);
                $perMonth = get_string('plan_price_per_month', 'local_subscriptions', $monthlyStr);
                $output .= \html_writer::div($perMonth, 'text-muted small');
            }

            // Bouton Subscribe (checkout) – on passe la devise réellement utilisée
            $btnclass = 'btn subscribe-button w-100 fs-5';
            if ($isPopular)      { $btnclass .= ' ls-btn-popular'; }
            else if ($isPremium) { $btnclass .= ' ls-btn-premium'; }
            else                 { $btnclass .= ' btn-outline-primary'; }

            // URL de checkout
            $checkouturl = UrlFactory::checkout($plan->id, $usedCurrency);

            // Si la page courante est embedded=1 (popup), on garde ce mode pour checkout
            if (!empty($embedded)) {
                $checkouturl = new \moodle_url($checkouturl->out(false), ['embedded' => 1]);
            }

            $buttonlabel = !empty($plan->display_cta)
                ? $plan->display_cta
                : get_string('subscribe', 'local_subscriptions');

            $output .= \html_writer::link(
                $checkouturl,
                $buttonlabel,
                ['class' => $btnclass, 'data-planid' => $plan->id]
            );

            $output .= \html_writer::end_div(); // bottom-zone
            $output .= \html_writer::end_div(); // card
        }

        $output .= \html_writer::end_div(); // grid

        // Plus besoin du JS de toggle description, on l’enlève
        return $output;
    }


    /** Retourne le symbole pour EUR/RUB, sinon le code. */
    private function currency_symbol(string $cur): string {
        $c = strtoupper($cur);
        return ($c === 'EUR') ? '€' : (($c === 'RUB' || $c === 'RUR') ? '₽' : $c);
    }

    /** Formate un prix en tenant compte du réglage symboles. */
    private function format_money(float $amount, string $currency): string {
        $usesymbol = (bool) get_config('local_subscriptions','display_currency_symbols');
        $cur = strtoupper($currency);
        $amt = number_format($amount, 2, '.', '');
        if ($usesymbol) {
            $sym = $this->currency_symbol($cur);
            // Convention EU: 49,00 € ; on reste simple: "49.00 €"
            return $amt.' '.$sym;
        } else {
            return $amt.' '.$cur;
        }
    }

    /** 
     * Applique une remise en % sur un prix de base.
     * @return array [finalPrice, discountAmount]
     */
    private function apply_discount(float $base, bool $allowed, int $pct): array {
        if (!$allowed || $pct <= 0) {
            return [round($base, 2), 0.0];
        }
        $disc = round($base * $pct / 100, 2);
        return [round($base - $disc, 2), $disc];
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

    /**
     * Essaie de déduire le nombre de mois de la durée d’un plan.
     * On se base sur duration_key (ex: 1month, 6months, 1year) avec quelques fallbacks.
     */
    private function months_for_plan(\stdClass $plan): int {
        $key = trim(mb_strtolower($plan->duration_key ?? ''));
        if ($key === '') {
            return 0;
        }

        // Mapping explicite des cas classiques
        $map = [
            '1month'   => 1,
            '3months'  => 3,
            '6months'  => 6,
            '12months' => 12,
            '1year'    => 12,
            '3years'   => 36,
        ];
        if (isset($map[$key])) {
            return $map[$key];
        }

        // Fallback générique : on extrait le nombre et on regarde s'il y a "year" ou "month"
        if (preg_match('/(\d+)/', $key, $m)) {
            $n = (int)$m[1];
            if (strpos($key, 'year') !== false) {
                return $n * 12;
            }
            if (strpos($key, 'month') !== false) {
                return $n;
            }
        }

        return 0;
    }

    
}
