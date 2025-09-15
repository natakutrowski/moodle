<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

class mailer {

    private static function render_email_layout(string $title, string $bodyhtml, ?string $buttonlabel = null, ?string $buttonurl = null): array {
        global $SITE;
        // Couleurs & logo (tu peux plus tard lire ça depuis des settings du plugin).
        $brandname = $SITE->fullname;
        $brandcolor = '#005f73'; // ta teinte
        $brandcolorDark = '#013140';
        $logo = get_config('local_subscriptions', 'brand_logo_url') ?: ''; // optionnel

        $btn = '';
        if ($buttonlabel && $buttonurl) {
            $btn = '
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:24px auto;">
            <tr>
                <td bgcolor="'.$brandcolor.'" style="border-radius:8px;">
                <a href="'.s($buttonurl).'" style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;">'
                    . s($buttonlabel) .
                '</a>
                </td>
            </tr>
            </table>';
        }

        $logoHtml = $logo ? '<img src="'.s($logo).'" height="32" style="display:block;border:0;outline:none;text-decoration:none;">' : '<strong style="font-size:16px;color:#111;">'.s($brandname).'</strong>';

        $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="x-apple-disable-message-reformatting"><meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark"></head>
    <body style="margin:0;padding:0;background:#f6f9fc;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr><td align="center" style="padding:24px 12px;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="620" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
            <tr>
            <td style="padding:16px 20px;background:#ffffff;border-bottom:1px solid #eee;">
                <table width="100%" role="presentation"><tr>
                <td>'.$logoHtml.'</td>
                <td align="right" style="font-size:12px;color:#6b7280;">'.date('Y-m-d').'</td>
                </tr></table>
            </td>
            </tr>
            <tr>
            <td style="padding:24px 24px 8px 24px;">
                <h1 style="margin:0 0 12px 0;font-size:20px;line-height:1.4;color:#111111;">'.s($title).'</h1>
                <div style="font-size:14px;line-height:1.7;color:#374151;">'.$bodyhtml.'</div>
                '.$btn.'
            </td>
            </tr>
            <tr>
            <td style="padding:16px 24px 20px 24px;border-top:1px solid #eee;">
                <div style="font-size:12px;color:#6b7280;">
                © '.date('Y').' '.s($brandname).'. All rights reserved.
                </div>
            </td>
            </tr>
        </table>
        <div style="font-size:11px;color:#94a3b8;margin-top:10px;">If you didn’t expect this email, you can safely ignore it.</div>
        </td></tr>
    </table>
    </body></html>';

        // Version texte (fallback) – on strippe les balises.
        $text = trim(html_entity_decode(strip_tags($title."\n\n".preg_replace('/<br\s*\/?>/i', "\n", $bodyhtml)."\n\n".($buttonurl ?: '')), ENT_QUOTES, 'UTF-8'));

        return [$html, $text];
    }

    private static function resolve_user_for_pr(\stdClass $pr): ?\stdClass {
        global $DB;
        // 1) Si on a un userid, on tente de récupérer le compte réel
        if (!empty($pr->userid)) {
            $user = $DB->get_record('user', ['id' => $pr->userid, 'deleted' => 0], '*', IGNORE_MISSING);
            if ($user && !empty($user->email)) {
                return $user;
            }
        }
        // 2) Sinon on se rabat sur l’email stocké dans la PR
        if (!empty($pr->email)) {
            $u = new \stdClass();
            $u->id = -1;
            $u->email = $pr->email;
            $u->firstname = $pr->firstname ?? '';
            $u->lastname  = $pr->lastname  ?? '';

            // Champs de nom "étendus" attendus par core_user::get_fullname()
            $u->firstnamephonetic = '';
            $u->lastnamephonetic  = '';
            $u->middlename        = '';
            $u->alternatename     = '';

            // (facultatif mais fréquent)
            $u->mailformat = 1;

            return $u;
        }
        // 3) Pas d’email → pas d’envoi possible
        return null;
    }


    /**
     * Envoie l'email de bienvenue pour un NOUVEL utilisateur.
     * Contient username + mot de passe temporaire + lien de connexion.
     */
    public static function send_welcome(\stdClass $user, string $tmpPassword, \stdClass $plan, \stdClass $paymentreq): void {
        global $SITE;
        $support = \core_user::get_support_user();

        $title = get_string('welcome_subject', 'local_subscriptions', $SITE->fullname);
        $loginurl = (new \moodle_url('/login/index.php'))->out(false);

        $price   = format_float((float)($paymentreq->price ?? 0), 2).' '.strtoupper($paymentreq->currency ?? '');
        $planname = s($plan->name ?? '');

        $body = ''
        . \html_writer::tag('p', get_string('welcome_hello', 'local_subscriptions', fullname($user)))
        . \html_writer::tag('p', get_string('welcome_body_intro', 'local_subscriptions', $SITE->fullname))
        . \html_writer::start_tag('table', ['role'=>'presentation','cellspacing'=>'0','cellpadding'=>'0','border'=>'0','style'=>'margin:16px 0;font-size:14px;'])
        . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('welcome_username','local_subscriptions','').'</td><td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.s($user->username).'</code></td></tr>'
        . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('welcome_temp_password_label','local_subscriptions').'</td><td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.s($tmpPassword).'</code></td></tr>'
        . \html_writer::end_tag('table')
        . \html_writer::tag('p', get_string('welcome_security_hint', 'local_subscriptions'))
        . \html_writer::empty_tag('hr', ['style'=>'border:none;border-top:1px solid #eee;margin:18px 0;'])
        . \html_writer::tag('p', get_string('welcome_plan_summary', 'local_subscriptions', $planname))
        . \html_writer::tag('p', get_string('welcome_amount_summary', 'local_subscriptions', $price));

        $body .= self::pr_ref_badge($paymentreq);
        [$html, $text] = self::render_email_layout($title, $body, get_string('welcome_button_login','local_subscriptions'), $loginurl);

        email_to_user($user, $support, $title, $text, $html);
    }


    /**
     * Envoie un reçu d'achat (branding Moodle). Le reçu Stripe continue d’être envoyé par Stripe.
     */
    public static function send_receipt(\stdClass $user, \stdClass $plan, \stdClass $paymentreq, \stdClass $sub): void {
        $support = \core_user::get_support_user();

        $title   = get_string('receipt_title', 'local_subscriptions');
        $price   = format_float((float)($paymentreq->price ?? 0), 2).' '.strtoupper($paymentreq->currency ?? '');
        $period  = userdate($sub->start_date).' → '.userdate($sub->end_date);
        $planname= s($plan->name ?? '');
        $tx      = s($paymentreq->transactionid ?? '');

        $coursesurl = (new \moodle_url('/'))->out(false); // ou vers une page “Mes cours”
        $body = ''
        . \html_writer::tag('p', get_string('subupdate_hello', 'local_subscriptions', fullname($user)))
        . \html_writer::tag('p', get_string('receipt_intro', 'local_subscriptions'))
        . \html_writer::start_tag('table', ['role'=>'presentation','cellspacing'=>'0','cellpadding'=>'0','border'=>'0','style'=>'margin:12px 0;font-size:14px;'])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_plan','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$planname.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_amount','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$price.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_period','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$period.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_tx','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$tx.'</td></tr>'
        . \html_writer::end_tag('table');

        $body .= self::pr_ref_badge($paymentreq);
        [$html, $text] = self::render_email_layout($title, $body, get_string('receipt_button_open','local_subscriptions'), $coursesurl);

        email_to_user($user, $support, $title, $text, $html);
    }


    /**
     * Envoie un email de confirmation d'abonnement pour un utilisateur EXISTANT (pas de mot de passe).
     */
    public static function send_subscription_update(
        \stdClass $user,
        \stdClass $plan,
        \stdClass $paymentreq,
        \stdClass $sub
    ): void {
        global $SITE;
        $support = \core_user::get_support_user();

        // Sujet
        $title = get_string('subupdate_subject', 'local_subscriptions', format_string($plan->name ?? ''));

        // Montant payé (price en priorité, sinon amount)
        $amount   = isset($paymentreq->price) ? (float)$paymentreq->price : (float)($paymentreq->amount ?? 0);
        $currency = strtoupper($paymentreq->currency ?? '');
        $price    = format_float($amount, 2) . ' ' . $currency;

        // Période
        $period  = userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date);
        $planname = s($plan->name ?? '');

        // Corps
        $body = ''
            . \html_writer::tag('p', get_string('subupdate_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('subupdate_body', 'local_subscriptions', $planname))
            . \html_writer::start_tag('table', [
                'role' => 'presentation',
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'style' => 'margin:16px 0;font-size:14px;'
            ])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_amount','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.$price.'</code></td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_period','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.$period.'</code></td></tr>';

        // Transaction id si dispo
        if (!empty($paymentreq->transactionid)) {
            $body .= '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_tx','local_subscriptions').'</td>'
                . '<td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.s($paymentreq->transactionid).'</code></td></tr>';
        }
        $body .= \html_writer::end_tag('table');

        $body .= self::pr_ref_badge($paymentreq);

        // Bouton → page des abonnements
        $url = (new \moodle_url('/local/subscriptions/profile.php'))->out(false);
        [$html, $text] = self::render_email_layout(
            $title,
            $body,
            get_string('subupdate_button_manage','local_subscriptions'),
            $url
        );

        email_to_user($user, $support, $title, $text, $html);
    }

    public static function send_abandoned(\stdClass $pr): void {
        global $DB;
        $user = self::resolve_user_for_pr($pr);
        if (!$user || empty($user->email)) {
            // Optionnel: log discret pour debug
            error_log('[subs][mailer] skip abandoned: no recipient for PR #'.$pr->id);
            return;
        }

        $plan = $DB->get_record('subscription_plan', ['id'=>$pr->planid], 'id,name', IGNORE_MISSING);
        $title = get_string('email_abandoned_subject', 'local_subscriptions');
        $price = format_float((float)($pr->price ?? 0), 2).' '.strtoupper($pr->currency ?? '');
        $planname = s($plan->name ?? '');
        $retryurl = self::build_retry_url($pr);

        $body = \html_writer::tag('p', get_string('email_abandoned_intro', 'local_subscriptions'))
            . \html_writer::start_tag('table', ['role'=>'presentation','cellspacing'=>'0','cellpadding'=>'0','border'=>'0','style'=>'margin:12px 0;font-size:14px;'])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_plan','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$planname.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_amount','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$price.'</td></tr>'
            . \html_writer::end_tag('table');

        $body .= self::pr_ref_badge($pr);
        [$html, $text] = self::render_email_layout($title, $body, get_string('email_button_retry', 'local_subscriptions'), $retryurl);
        email_to_user($user, \core_user::get_support_user(), $title, $text, $html);
    }


    public static function send_failed(\stdClass $pr): void {
        global $DB;
        $user = self::resolve_user_for_pr($pr);
        if (!$user) { return; }

        $plan = $DB->get_record('subscription_plan', ['id'=>$pr->planid], 'id,name', IGNORE_MISSING);
        $title = get_string('email_failed_subject', 'local_subscriptions');
        $price = format_float((float)($pr->price ?? 0), 2).' '.strtoupper($pr->currency ?? '');
        $planname = s($plan->name ?? '');
        $retryurl = self::build_retry_url($pr);

        $body = \html_writer::tag('p', get_string('subupdate_hello', 'local_subscriptions', s(trim(($user->firstname ?? '').' '.($user->lastname ?? '')))))
            . \html_writer::tag('p', get_string('email_failed_intro', 'local_subscriptions'))
            . \html_writer::start_tag('table', ['role'=>'presentation','cellspacing'=>'0','cellpadding'=>'0','border'=>'0','style'=>'margin:12px 0;font-size:14px;'])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_plan','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$planname.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_amount','local_subscriptions','').'</td><td style="padding:4px 8px;">'.$price.'</td></tr>'
            . \html_writer::end_tag('table')
            . \html_writer::tag('p', get_string('email_failed_help', 'local_subscriptions'));

        $body .= self::pr_ref_badge($pr);
        [$html, $text] = self::render_email_layout($title, $body, get_string('email_button_retry', 'local_subscriptions'), $retryurl);
        email_to_user($user, \core_user::get_support_user(), $title, $text, $html);
    }

    public static function send_reminder(\stdClass $pr): void {
        $user = self::resolve_user_for_pr($pr);
        if (!$user) { return; }
        $title = get_string('email_reminder_subject', 'local_subscriptions');
        $retryurl = self::build_retry_url($pr);
        $body = \html_writer::tag('p', get_string('email_reminder_intro', 'local_subscriptions'));
        $body .= self::pr_ref_badge($pr);
        [$html, $text] = self::render_email_layout($title, $body, get_string('email_button_retry', 'local_subscriptions'), $retryurl);
        email_to_user($user, \core_user::get_support_user(), $title, $text, $html);
    }
    
    public static function send_reminder_second(\stdClass $pr): void {
        $user = self::resolve_user_for_pr($pr);
        if (!$user || empty($user->email)) { error_log('[subs][mailer] skip R2: no recipient PR#'.$pr->id); return; }

        $title   = get_string('email_reminder2_subject', 'local_subscriptions'); // + strings EN ci-dessous
        $retryurl= self::build_retry_url($pr);
        $body = \html_writer::tag('p', get_string('email_reminder2_intro', 'local_subscriptions'));

        $body .= self::pr_ref_badge($pr);
        [$html, $text] = self::render_email_layout($title, $body, get_string('email_button_retry', 'local_subscriptions'), $retryurl);
        email_to_user($user, \core_user::get_support_user(), $title, $text, $html);
    }

    public static function send_recurring_started(\stdClass $user, \stdClass $plan, \stdClass $pr): void {
        global $SITE;

        // Titre du mail (header du template)
        $title = get_string(
            'mail_recurring_started_subject',
            'local_subscriptions',
            format_string($plan->name)
        );

        // Corps (sera injecté dans ton layout)
        $body  = \html_writer::tag(
            'p',
            get_string('subupdate_hello', 'local_subscriptions', fullname($user))
        );
        $body .= \html_writer::tag(
            'p',
            get_string('mail_recurring_started_body', 'local_subscriptions', [
                'plan'  => format_string($plan->name),
                'start' => userdate(time()),
            ])
        );

        // Badge (PR ref, tx, etc.) si tu utilises déjà ce helper
        if (method_exists(__CLASS__, 'pr_ref_badge')) {
            $body .= self::pr_ref_badge($pr);
        }

        // Bouton → page des abonnements
        $buttontext = get_string('view_my_subscriptions', 'local_subscriptions');
        $buttonurl  = (new \moodle_url('/local/subscriptions/profile.php'))->out(false);

        // Utilise TON layout (HTML + texte)
        [$html, $text] = self::render_email_layout($title, $body, $buttontext, $buttonurl);

        // Forcer HTML sur le destinataire
        $recipient = clone $user;
        $recipient->mailformat = 1;

        email_to_user($recipient, \core_user::get_support_user(), $title, $text, $html);
    }

    /**
     * Relance d’expiration J-30 / J-7 / J-1 pour une souscription non récurrente.
     * $remindkey ∈ {'d30','d7','d1'}
     */
    public static function send_subscription_expiry_reminder(\stdClass $user, \stdClass $plan, \stdClass $sub, string $remindkey): void {
        $user = self::ensure_full_user($user);
        $support = \core_user::get_support_user();

        $daysleft = [
            'd30' => 30,
            'd7'  => 7,
            'd1'  => 1,
        ][$remindkey] ?? 7;

        $planname = format_string($plan->name ?? '');
        $period   = userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date);
        $enddate  = userdate((int)$sub->end_date);

        // Titre & corps
        $title = get_string('expiry_reminder_subject', 'local_subscriptions', $daysleft);

        $body = ''
            . \html_writer::tag('p', get_string('subupdate_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('expiry_reminder_body', 'local_subscriptions', [
                'plan' => $planname,
                'date' => $enddate,
            ]))
            . \html_writer::start_tag('table', [
                'role' => 'presentation',
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'style' => 'margin:12px 0;font-size:14px;'
            ])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_plan','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;">'.$planname.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_period','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.$period.'</code></td></tr>'
            . \html_writer::end_tag('table');

        // Bouton → page d’abonnement pour ce plan (prolonger/renouveler)
        $buttonurl  = (new \moodle_url('/local/subscriptions/subscribe.php', ['planid' => (int)$sub->planid]))->out(false);
        $buttontext = get_string('expiry_button_renew', 'local_subscriptions');

        [$html, $text] = self::render_email_layout($title, $body, $buttontext, $buttonurl);

        $recipient = clone $user;
        $recipient->mailformat = 1;
        email_to_user($recipient, $support, $title, $text, $html);
    }

    /**
     * Notification d’activation d’une brique "queued" -> "active".
     */
    public static function send_subscription_activated(\stdClass $user, \stdClass $plan, \stdClass $sub): void {
        $user = self::ensure_full_user($user);
        $support  = \core_user::get_support_user();
        $planname = format_string($plan->name ?? '');
        $period   = userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date);

        $title = get_string('subscription_activated_subject', 'local_subscriptions', $planname);

        $body = ''
            . \html_writer::tag('p', get_string('subupdate_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('subscription_activated_body', 'local_subscriptions', $planname))
            . \html_writer::start_tag('table', [
                'role' => 'presentation',
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'style' => 'margin:12px 0;font-size:14px;'
            ])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_plan','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;">'.$planname.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_period','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.$period.'</code></td></tr>'
            . \html_writer::end_tag('table');

        // Bouton → "Voir mes abonnements"
        $buttonurl  = (new \moodle_url('/local/subscriptions/profile.php'))->out(false);
        $buttontext = get_string('view_my_subscriptions', 'local_subscriptions');

        [$html, $text] = self::render_email_layout($title, $body, $buttontext, $buttonurl);

        $recipient = clone $user;
        $recipient->mailformat = 1;
        email_to_user($recipient, $support, $title, $text, $html);
    }

    /**
     * Notification d’expiration (pas de brique suivante).
     */
    public static function send_subscription_expired(\stdClass $user, \stdClass $plan, \stdClass $sub): void {
        $user = self::ensure_full_user($user);
        $support  = \core_user::get_support_user();
        $planname = format_string($plan->name ?? '');
        $period   = userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date);
        $enddate  = userdate((int)$sub->end_date);

        $title = get_string('subscription_expired_subject', 'local_subscriptions', $planname);

        $body = ''
            . \html_writer::tag('p', get_string('subupdate_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('subscription_expired_body', 'local_subscriptions', [
                'plan' => $planname,
                'date' => $enddate,
            ]))
            . \html_writer::start_tag('table', [
                'role' => 'presentation',
                'cellspacing' => '0',
                'cellpadding' => '0',
                'border' => '0',
                'style' => 'margin:12px 0;font-size:14px;'
            ])
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_plan','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;">'.$planname.'</td></tr>'
            . '<tr><td style="padding:4px 8px;color:#6b7280;">'.get_string('receipt_period','local_subscriptions').'</td>'
            . '<td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'.$period.'</code></td></tr>'
            . \html_writer::end_tag('table');

        // Bouton → s’abonner de nouveau / prolonger
        $buttonurl  = (new \moodle_url('/local/subscriptions/subscribe.php', ['planid' => (int)$sub->planid]))->out(false);
        $buttontext = get_string('expired_button_renew', 'local_subscriptions');

        [$html, $text] = self::render_email_layout($title, $body, $buttontext, $buttonurl);

        $recipient = clone $user;
        $recipient->mailformat = 1;
        email_to_user($recipient, $support, $title, $text, $html);
    }

    public static function send_renewal_ok(
        \stdClass $user,
        \stdClass $plan,
        \stdClass $sub,
        ?float $amount = null,              // montant TTC (en unités majeures), ex: 19.90
        ?string $currency = null,           // devise (EUR), sera upper
        ?string $invoiceid = null,          // id de facture Stripe (optionnel)
        ?int $oldend = null                 // ancienne date de fin, pour afficher la nouvelle période
    ): void {
        global $SITE;

        $user = self::ensure_full_user($user);

        $support = \core_user::get_support_user();

        // Sujet : “Renouvellement confirmé – {Nom du plan}”
        $title = get_string('renewal_subject', 'local_subscriptions', format_string($plan->name ?? ''));

        // Montant (si fourni par l’événement Stripe)
        $price = null;
        if ($amount !== null) {
            $cur   = strtoupper($currency ?? '');
            $price = format_float((float)$amount, 2) . ' ' . $cur;
        }

        // Période de renouvellement : si oldend dispo → oldend → new end, sinon start → end
        $period = ($oldend !== null)
            ? (userdate((int)$oldend) . ' → ' . userdate((int)$sub->end_date))
            : (userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date));

        // Corps HTML
        $body = ''
            . \html_writer::tag('p', get_string('renewal_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('renewal_body', 'local_subscriptions', format_string($plan->name ?? '')))

            . \html_writer::start_tag('table', [
                'role' => 'presentation', 'cellspacing' => '0', 'cellpadding' => '0', 'border' => '0',
                'style' => 'margin:16px 0;font-size:14px;'
            ]);

        if ($price !== null) {
            $body .= '<tr><td style="padding:4px 8px;color:#6b7280;">'
                .  get_string('receipt_amount','local_subscriptions')
                .  '</td><td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'
                .  $price . '</code></td></tr>';
        }

        $body .= '<tr><td style="padding:4px 8px;color:#6b7280;">'
            .  get_string('receipt_period','local_subscriptions')
            .  '</td><td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'
            .  $period . '</code></td></tr>';

        if (!empty($invoiceid)) {
            $body .= '<tr><td style="padding:4px 8px;color:#6b7280;">'
                .  get_string('receipt_invoice','local_subscriptions')
                .  '</td><td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'
                .  s($invoiceid) . '</code></td></tr>';
        }

        $body .= \html_writer::end_tag('table');

        // Bouton : aller vers “Mes abonnements”
        $url = (new \moodle_url('/user/my_subscriptions.php'))->out(false);
        [$html, $text] = self::render_email_layout(
            $title,
            $body,
            get_string('renewal_button_manage', 'local_subscriptions'),
            $url
        );

        // Forcer HTML
        $recipient = clone $user;
        $recipient->mailformat = 1;

        email_to_user($recipient, $support, $title, $text, $html);
    }

    public static function send_failed_recurring($userOrSub, $plan = null, $sub = null,
        ?float $amount = null, ?string $currency = null, ?string $invoiceid = null,
        ?string $failcode = null, ?int $nextretry = null): void {

        global $DB;

        // Compat : ancien appel -> seul $sub était fourni
        if (is_object($userOrSub) && $plan === null && $sub === null && isset($userOrSub->userid)) {
            $sub  = $userOrSub;
            $user = \core_user::get_user($sub->userid, '*', MUST_EXIST);
            $plan = $DB->get_record('subscription_plan', ['id'=>$sub->planid], '*', MUST_EXIST);
        } else {
            $user = $userOrSub;
        }

        $user = self::ensure_full_user($user);

        $support = \core_user::get_support_user();
        $title = get_string('recurring_failed_subject', 'local_subscriptions', format_string($plan->name ?? ''));

        $body = ''
            . \html_writer::tag('p', get_string('recurring_failed_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('recurring_failed_body', 'local_subscriptions', format_string($plan->name ?? '')));

        // Bouton → Customer Portal (Stripe)
        $url = (new \moodle_url('/local/subscriptions/portal.php'))->out(false);
        [$html, $text] = self::render_email_layout(
            $title,
            $body,
            get_string('recurring_failed_button', 'local_subscriptions'),
            $url
        );

        $recipient = clone $user;
        $recipient->mailformat = 1;
        email_to_user($recipient, $support, $title, $text, $html);
    }

    public static function send_cancellation_info($userOrSub, $plan = null, $sub = null, ?int $atperiodend = null): void {
        global $DB;

        // Compat : ancien appel -> seul $sub était passé
        if (is_object($userOrSub) && $plan === null && $sub === null && isset($userOrSub->userid)) {
            $sub  = $userOrSub;
            $user = \core_user::get_user($sub->userid, '*', MUST_EXIST);
            $plan = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', MUST_EXIST);
        } else {
            $user = $userOrSub; // appel moderne: ($user, $plan, $sub, ...)
        }

        // Sécurise fullname()/email_to_user()
        $user = self::ensure_full_user($user);

        $support = \core_user::get_support_user();
        $title = get_string('recurring_canceled_subject', 'local_subscriptions', format_string($plan->name ?? ''));

        $period = userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date);

        $body = ''
            . \html_writer::tag('p', get_string('recurring_canceled_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('recurring_canceled_body', 'local_subscriptions', format_string($plan->name ?? '')))

            . \html_writer::start_tag('table', [
                'role' => 'presentation', 'cellspacing' => '0', 'cellpadding' => '0', 'border' => '0',
                'style' => 'margin:16px 0;font-size:14px;'
            ]);

        $body .= '<tr><td style="padding:4px 8px;color:#6b7280;">'
            .  get_string('receipt_period','local_subscriptions')
            .  '</td><td style="padding:4px 8px;"><code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;">'
            .  $period . '</code></td></tr>';

        [$html, $text] = self::render_email_layout(
            $title,
            $body,
            get_string('recurring_canceled_button', 'local_subscriptions'),
            (new \moodle_url('/subscribe.php'))->out(false) // bouton "se réabonner"
        );

        $recipient = clone $user;
        $recipient->mailformat = 1;
        email_to_user($recipient, $support, $title, $text, $html);
    }

    /** Helpers internes **/
    private static function fake_user_from_pr(\stdClass $pr): \stdClass {
        // email_to_user() exige un "user" (id/email/firstname/lastname)
        $u = new \stdClass();
        $u->id = 0;
        $u->email = $pr->email ?: 'no-reply@example.com';
        $u->firstname = $pr->firstname ?: '';
        $u->lastname  = $pr->lastname ?: '';
        return $u;
    }
    private static function build_retry_url(\stdClass $pr): string {
        global $CFG, $DB;
        // Si pas de token → on en génère un ici de manière défensive
        if (empty($pr->retry_token)) {
            $secret = get_config('local_subscriptions','email_link_secret') ?: ($CFG->passwordsaltmain ?? 'secret');
            $rand = random_string(32);
            $pr->retry_token   = hash('sha256', $pr->id . ':' . $rand . ':' . $secret);
            $pr->retry_expires = time() + 3*24*3600;
            $DB->update_record('subscription_payment_request', $pr);
        }
        return (new \moodle_url('/local/subscriptions/retry_payment.php', ['pid'=>$pr->id,'t'=>$pr->retry_token]))->out(false);
    }

    private static function pr_ref_badge(\stdClass $pr): string {
        $ref = 'PR #'.(int)$pr->id;
        if (!empty($pr->creation_date)) {
            $ref .= ' · '.userdate((int)$pr->creation_date);
        }
        return '<div style="margin-top:12px;font-size:11px;color:#94a3b8;">'.$ref.'</div>';
    }


    private static function ensure_full_user(\stdClass $user): \stdClass {
        // Si les champs problématiques sont déjà là, on ne refait pas de requête.
        if (property_exists($user, 'firstnamephonetic')
            && property_exists($user, 'lastnamephonetic')
            && property_exists($user, 'middlename')
            && property_exists($user, 'alternatename')) {
            return $user;
        }
        return \core_user::get_user($user->id, '*', MUST_EXIST);
    }


}
