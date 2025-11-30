<?php
namespace local_subscriptions;

use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\mail\MailRenderer;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib/plans_lib.php');

class mailer {

    /* =========
     * Événements (constantes)
     * ========= */
    public const T_WELCOME               = 'welcome';
    public const T_UPGRADE_CONFIRMED     = 'upgrade_confirmed';
    public const T_SUBSCRIPTION_UPDATED  = 'subscription_updated';

    public const T_SUBSCRIPTION_EVENT          = 'subscription_event';
    public const T_RECURRING_STARTED           = 'recurring_started';
    public const T_RECEIPT                     = 'receipt';
    public const T_PAYMENT_FAILED              = 'payment_failed';
    public const T_PAYMENT_ABANDONED           = 'payment_abandoned';

    public const T_RENEWAL_OK                  = 'renewal_ok';
    public const T_FAILED_RECURRING            = 'failed_recurring';
    public const T_CANCELLATION_INFO           = 'cancellation_info';

    public const T_SUBSCRIPTION_ACTIVATED      = 'subscription_activated';
    public const T_SUBSCRIPTION_EXPIRED        = 'subscription_expired';
    public const T_SUBSCRIPTION_EXPIRY_REM     = 'subscription_expiry_reminder';
    public const T_REMINDER_FIRST              = 'reminder_first';
    public const T_REMINDER_SECOND             = 'reminder_second';

    public const T_CONTACT_ADMIN               = 'contact_admin';  // envoi côté admin/destinataire
    public const T_CONTACT_ACK                 = 'contact_ack';    // accusé réception côté émetteur
    
    public const T_TRIAL_STARTED  = 'T_TRIAL_STARTED';
    public const T_TRIAL_REM3     = 'T_TRIAL_REM3';
    public const T_TRIAL_PRE_SUSPEND = 'T_TRIAL_PRE_SUSPEND'; // J + suspend_after - 2
    public const T_TRIAL_SUSPENDED   = 'T_TRIAL_SUSPENDED';   // J + suspend_after
    public const T_TRIAL_EXPIRED  = 'T_TRIAL_EXPIRED';

    /** @var array<string, string[]> mapping type -> required args keys */
    private const REQUIREMENTS = [
        self::T_WELCOME               => ['user','plan','pr','sub','tmpPassword'],
        self::T_UPGRADE_CONFIRMED     => ['user','plan','pr','sub'],
        self::T_SUBSCRIPTION_UPDATED  => ['user','plan','pr','sub'],

        self::T_SUBSCRIPTION_EVENT     => ['user','plan','pr','sub','tmpPassword','isupgrade','isnewuser'],
        self::T_RECURRING_STARTED      => ['user','plan','pr'],
        self::T_RECEIPT                => ['user','plan','pr','sub'],
        self::T_PAYMENT_FAILED         => ['pr'],
        self::T_PAYMENT_ABANDONED      => ['pr'],

        self::T_RENEWAL_OK             => ['user','plan','sub','amount','currency','invoiceid','oldend'],
        self::T_FAILED_RECURRING       => ['user','plan'],
        self::T_CANCELLATION_INFO      => ['user','plan','sub','atperiodend'],

        self::T_SUBSCRIPTION_ACTIVATED => ['user','plan','sub'],
        self::T_SUBSCRIPTION_EXPIRED   => ['user','plan','sub'],
        self::T_SUBSCRIPTION_EXPIRY_REM=> ['user','plan','sub'],
        self::T_REMINDER_FIRST         => ['pr'],
        self::T_REMINDER_SECOND        => ['pr'],
        self::T_CONTACT_ADMIN          => ['toemail','fullname','fromemail','message','meta'],
        self::T_CONTACT_ACK            => ['toemail','fullname','message'],

        self::T_TRIAL_STARTED => ['toemail','firstname','subscribe_url'], // subscribe_url optionnel
        self::T_TRIAL_REM3    => ['toemail','firstname','continue_url','subscribe_url','course_fullname','daysleft'],
        self::T_TRIAL_PRE_SUSPEND => ['toemail','firstname','suspend_date','subscribe_url'],
        self::T_TRIAL_SUSPENDED   => ['toemail','firstname','suspend_date','delete_date','subscribe_url'],
        self::T_TRIAL_EXPIRED => ['toemail','firstname','subscribe_url','course_fullname'],
    ];
    
    /**
     * Point d’entrée unique : envoie l’e-mail correspondant au $type.
     * $args contient les paramètres attendus (voir REQUIREMENTS) + optionnellement 'lang'.
     *
     * @param string $type Une des constantes T_*
     * @param array  $args Paramètres nécessaires au type + 'lang' facultatif
     * @return bool  true si OK (les méthodes internes peuvent être void : on renvoie true si pas d’exception)
     * @throws \coding_exception si type inconnu ou paramètres manquants
     */
    public static function dispatch(string $type, array $args = []): bool {
        if (!isset(self::REQUIREMENTS[$type]) && $type !== self::T_SUBSCRIPTION_EVENT) {
            throw new \coding_exception("Unknown mail type: {$type}");
        }
        // Validation minimale (on validera fort après éventuelle conversion du type)
        if ($type !== self::T_SUBSCRIPTION_EVENT) {
            $missing = array_diff(self::REQUIREMENTS[$type], array_keys($args));
            if (!empty($missing)) {
                throw new \coding_exception('Missing mail args: '.implode(', ', $missing)." for type {$type}");
            }
        }

        // Canonicaliser le type si on nous envoie l’agrégat T_SUBSCRIPTION_EVENT
        if ($type === self::T_SUBSCRIPTION_EVENT) {
            $isupgrade = !empty($args['isupgrade']);
            $isnew     = !empty($args['isnewuser']);
            if ($isupgrade) {
                $type = self::T_UPGRADE_CONFIRMED;
            } else if ($isnew) {
                $type = self::T_WELCOME;
            } else {
                $type = self::T_SUBSCRIPTION_UPDATED;
            }
            // Validation forte une fois le type connu
            $missing = array_diff(self::REQUIREMENTS[$type], array_keys($args));
            if (!empty($missing)) {
                throw new \coding_exception('Missing mail args: '.implode(', ', $missing)." for type {$type}");
            }
        }

        // Langue
        $recipientoruser = $args['user'] ?? null;
        $lang = $args['lang'] ?? self::effective_emaillang($recipientoruser);

        // Contexte pour la copie (type + args)
        self::$ctx = ['type' => $type, 'args' => $args];

        $caller = function() use ($type, $args) {
            switch ($type) {
                case self::T_WELCOME:              
                    return \local_subscriptions\mail\Catalog::welcome($args['user'], $args['tmpPassword'] ?? '', $args['plan'], $args['pr'], $args['sub']);
                
                case self::T_UPGRADE_CONFIRMED:    
                    return \local_subscriptions\mail\Catalog::upgrade_confirmed($args['user'], $args['plan'], $args['pr'], $args['sub']);
                
                case self::T_SUBSCRIPTION_UPDATED: 
                    return \local_subscriptions\mail\Catalog::subscription_updated($args['user'], $args['plan'], $args['pr'], $args['sub']);

                case self::T_RECURRING_STARTED:
                    return \local_subscriptions\mail\Catalog::recurring_started($args['user'], $args['plan'], $args['pr']);

                case self::T_RECEIPT:
                    return \local_subscriptions\mail\Catalog::receipt($args['user'], $args['plan'], $args['pr'], $args['sub']);

                case self::T_PAYMENT_FAILED:
                    return \local_subscriptions\mail\Catalog::payment_failed($args['pr']);

                case self::T_PAYMENT_ABANDONED:
                    return \local_subscriptions\mail\Catalog::payment_abandoned($args['pr']);

                // Cycle d’abonnement
                case self::T_RENEWAL_OK:
                    return \local_subscriptions\mail\Catalog::renewal_ok($args['user'], $args['plan'], $args['sub'],
                                                 $args['amount'], $args['currency'], $args['invoiceid'], $args['oldend']);

                case self::T_FAILED_RECURRING:
                    return \local_subscriptions\mail\Catalog::failed_recurring($args['user'], $args['plan'], 
                        [
                            'amount'    => $args['amount']    ?? null,
                            'currency'  => $args['currency']  ?? null,
                            'invoiceid' => $args['invoiceid'] ?? null,
                            'failcode'  => $args['failcode']  ?? null,
                            'nextretry' => $args['nextretry'] ?? null,
                        ]);

                case self::T_CANCELLATION_INFO:
                    return \local_subscriptions\mail\Catalog::cancellation_info($args['user'], $args['plan'], $args['sub'], $args['atperiodend']);

                // Tâches planifiées / suivi
                case self::T_SUBSCRIPTION_ACTIVATED:
                    return \local_subscriptions\mail\Catalog::subscription_activated($args['user'], $args['plan'], $args['sub']);

                case self::T_SUBSCRIPTION_EXPIRED:
                    return \local_subscriptions\mail\Catalog::subscription_expired($args['user'], $args['plan'], $args['sub']);

                case self::T_SUBSCRIPTION_EXPIRY_REM: {
                    // Supporte soit 'days' (int), soit 'remindkey' (ex: 'd7', 'J-7')
                    $daysleft = null;
                    if (array_key_exists('days', $args)) {
                        $daysleft = (int)$args['days'];
                    } else if (!empty($args['remindkey'])) {
                        $rk = strtolower((string)$args['remindkey']);
                        // formats acceptés: 'd30', 'd7', 'd1', 'j-30', 'j-7', 'j-1', '30', '7', '1'
                        if (preg_match('~^(?:d|j-)?(\d+)$~', $rk, $m)) {
                            $daysleft = (int)$m[1];
                        }
                    }
                    if ($daysleft === null) {
                        throw new \coding_exception("Missing 'days' (or 'remindkey') for T_SUBSCRIPTION_EXPIRY_REM");
                    }
                    return \local_subscriptions\mail\Catalog::subscription_expiry_reminder($args['user'], $args['plan'], $args['sub'], $daysleft);
                }

                case self::T_REMINDER_FIRST:
                    return \local_subscriptions\mail\Catalog::send_reminder($args['pr']);

                case self::T_REMINDER_SECOND:
                    return \local_subscriptions\mail\Catalog::send_reminder_second($args['pr']);

                case self::T_CONTACT_ADMIN:
                    return \local_subscriptions\mail\Catalog::contact_admin($args['toemail'], $args['fullname'], $args['fromemail'], $args['message'], $args['meta']);

                case self::T_CONTACT_ACK:
                    return \local_subscriptions\mail\Catalog::contact_ack($args['toemail'], $args['fullname'], $args['message'],$args['fromsupport'] ?? null);    

                case self::T_TRIAL_STARTED:
                    \local_subscriptions\mail\Catalog::trial_started($args); break;

                case self::T_TRIAL_REM3:
                    \local_subscriptions\mail\Catalog::trial_rem3($args); break;

                case self::T_TRIAL_PRE_SUSPEND:
                    \local_subscriptions\mail\Catalog::trial_pre_suspend($args); break;

                case self::T_TRIAL_SUSPENDED:
                    \local_subscriptions\mail\Catalog::trial_suspended($args); break;

                case self::T_TRIAL_EXPIRED:
                    \local_subscriptions\mail\Catalog::trial_expired($args); break;

                default:
                    throw new \coding_exception("Unhandled mail type: {$type}");
            }
        };

        // Exécuter dans la langue choisie (sans polluer la session).
        $prev = null;
        if (!empty($lang)) {
            // force_current_language() renvoie l'ANCIEN forcelang ('' si rien n'était forcé)
            $prev = force_current_language($lang);
        }
        try {
            // Contexte pour la copie (type + args)
            self::$ctx = ['type' => $type, 'args' => $args];

            $caller(); // envoi
            return true;
        } finally {
            if ($prev !== null) {
                // restaurer EXACTEMENT l'état précédent (y compris “pas de forcelang”)
                force_current_language($prev); // $prev peut être ''
            }
            // Nettoyage du contexte
            self::$ctx = null;
        }
    }

    /** Langue effective pour un e-mail (réglage plugin > user->lang > langue site). */
    private static function effective_emaillang(?\stdClass $user): string {
        global $CFG;
        $cfg = get_config('local_subscriptions');

        // 1) Сlé supportée
        if (!empty(get_config('local_subscriptions','defaultemaillang'))) { 
            return strtolower(get_config('local_subscriptions','defaultemaillang')); 
        }

        // 2) Langue du destinataire si connue
        if (!empty($user) && !empty($user->lang)) {
            return strtolower($user->lang);
        }

        // 3) Langue du site (cron retombe souvent ici)
        if (!empty($CFG->lang)) { return strtolower($CFG->lang); }

        // 4) Fallback stable
        return 'ru';
    }


    /** Mode preview (dry-run) pour les CLIs: n’envoie pas les emails mais capture le rendu. */
    private static bool $preview = false;
    /** Dernier rendu capturé: ['subject'=>..., 'html'=>..., 'text'=>...] */
    private static ?array $last = null;

    /** Active le mode preview (désactive l’envoi réel). */
    public static function enable_preview(): void {
        self::$preview = true;
        self::$last = null;
    }

    /** Récupère le dernier rendu capturé (ou null). */
    public static function get_last_render(): ?array {
        return self::$last;
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
    public static function send_welcome(\stdClass $user, string $tmpPassword, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        global $CFG;
        $user = self::ensure_full_user($user);

        $title = get_string('welcome_subject', 'local_subscriptions');

        // Identifiant de connexion : on utilise l'email comme "username"
        $loginid = (string)($user->email ?: $user->username);

        $planname = local_subscriptions_plan_display_name($plan);

        // Montant final payé (même logique que send_receipt)
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, $sub);
        $price = '';
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur); // même helper que dans send_receipt()
        }


        // Tableau : uniquement l'identifiant (email)
        $tbl = MailRenderer::table()
            ->lined()
            ->row('welcome_username', MailRenderer::code($loginid))
            ->render();


        $tbl2 = MailRenderer::table()
            ->lined()
            ->plan(format_string($planname))
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->amount($price)
            ->render();

        $body = \html_writer::tag('p', get_string('welcome_body_intro', 'local_subscriptions', $user->firstname));
        $body .= $tbl;

        // Avertissement de sécurité (garde ton texte générique)
        $body .= \html_writer::tag('p', get_string('welcome_security_hint', 'local_subscriptions'));
        $body .= \html_writer::tag('p', get_string('welcome_mycourses', 'local_subscriptions'));
        $body .= $tbl2;

        $body .= self::pr_ref_badge($pr);

        $brandcolor = get_config('local_subscriptions', 'brand_color') ?: '#005f73';
        $brandcolorDark = get_config('local_subscriptions', 'brand_color_dark') ?: '#013140';
        $buttonurl1 = (new \moodle_url('https://t.me/+tXrnh5eHmzszNWNk'))->out(false);
        $buttonlabel1 = get_string('welcome_button_canal','local_subscriptions');
        $btn1 = '
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:24px auto;">
        <tr>
            <td bgcolor="'.s($brandcolor).'" style="border-radius:8px;">
            <a href="'.s($buttonurl1).'"
                style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;background:'.s($brandcolor).';"
                onmouseover="this.style.background=\''.s($brandcolorDark).'\';"
                onmouseout="this.style.background=\''.s($brandcolor).'\';"
            >'.$buttonlabel1.'</a>
            </td>
        </tr>
        </table>';

        $buttonurl2 = (new \moodle_url('https://t.me/+Ze_-_1hWxgJlYWZk'))->out(false);
        $buttonlabel2 = get_string('welcome_button_group','local_subscriptions');
        $btn2 = '
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:24px auto;">
        <tr>
            <td bgcolor="'.s($brandcolor).'" style="border-radius:8px;">
            <a href="'.s($buttonurl2).'"
                style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;background:'.s($brandcolor).';"
                onmouseover="this.style.background=\''.s($brandcolorDark).'\';"
                onmouseout="this.style.background=\''.s($brandcolor).'\';"
            >'.$buttonlabel2.'</a>
            </td>
        </tr>
        </table>';

        $body .= \html_writer::tag('p', get_string('welcome_text_canal', 'local_subscriptions'));
        $body .= $btn1;
        $body .= \html_writer::tag('p', get_string('welcome_text_group', 'local_subscriptions'));
        $body .= $btn2;
        $supportEmail = (string)(get_config('local_subscriptions', 'support_email') ?: '');
        $body .= \html_writer::tag('p', get_string('welcome_footer', 'local_subscriptions', $supportEmail));
        
        $body .= 
            '<img src="'.$CFG->wwwroot . '/local/subscriptions/pix/email/mailWelcomeFooter.png"
                            style="display:block;width:100%;object-fit:cover;border:0;outline:none;text-decoration:none;">';

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            '',
            ''
        );

        self::deliver($user, $title, $html, $text);
    }




    /**
     * Envoie un reçu d'achat (branding Moodle). Le reçu Stripe continue d’être envoyé par Stripe.
     */
    public static function send_receipt(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        global $CFG;
        
        $user = self::ensure_full_user($user);

        $title    = get_string('receipt_title', 'local_subscriptions');

        // Montant final payé (sub/pr)
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, $sub);
        $price = '';
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur);
        }

        $planname = local_subscriptions_plan_display_name($plan);

        $table = MailRenderer::table()
            ->lined()
            ->plan(format_string($planname))
            ->amount($price)
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->provider($pr->payment_provider ?? '')
            ->txid($pr->transactionid ?? null)
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('receipt_intro', 'local_subscriptions'));
        $body .= $table;
        $body .= self::pr_ref_badge($pr);

        $brandcolor = get_config('local_subscriptions', 'brand_color') ?: '#005f73';
        $brandcolorDark = get_config('local_subscriptions', 'brand_color_dark') ?: '#013140';
        $buttonurl = (new \moodle_url('/local/campus/mycourses.php'))->out(false);
        $buttonurl  = self::login_redirect_for($buttonurl);
        $buttonlabel = get_string('receipt_button_open','local_subscriptions');
        $btn = '
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:24px auto;">
        <tr>
            <td bgcolor="'.s($brandcolor).'" style="border-radius:8px;">
            <a href="'.s($buttonurl).'"
                style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;background:'.s($brandcolor).';"
                onmouseover="this.style.background=\''.s($brandcolorDark).'\';"
                onmouseout="this.style.background=\''.s($brandcolor).'\';"
            >'.$buttonlabel.'</a>
            </td>
        </tr>
        </table>';


        $body .= $btn;


        $body .= \html_writer::tag('p', get_string('receipt_footer', 'local_subscriptions'));
        
        $body .= 
            '<img src="'.$CFG->wwwroot . '/local/subscriptions/pix/email/mailReceiptFooter.png"
                            style="display:block;width:100%;object-fit:cover;border:0;outline:none;text-decoration:none;">';

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            '',
            ''
        );

        self::deliver($user, $title, $html, $text);
    }


    /**
     * Envoie un email de confirmation d'abonnement pour un utilisateur EXISTANT (pas de mot de passe).
     */
    public static function send_subscription_update(
        \stdClass $user,
        \stdClass $plan,
        \stdClass $pr,
        \stdClass $sub
    ): void {
        $user = self::ensure_full_user($user);

        // Sujet
        $planname = local_subscriptions_plan_display_name($plan);
        $title = get_string('subupdate_subject', 'local_subscriptions', format_string($planname));

        // Montant final payé (sub/pr)
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, $sub);
        $price = '';
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur);
        }
        $tbl = MailRenderer::table()
            ->lined()
            ->row_code('receipt_amount', $price)
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->render();
        
        // Corps
        $body = ''
            . \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('subupdate_body', 'local_subscriptions', $planname));
        $body .= $tbl;
        $body .= self::pr_ref_badge($pr);

        $manageUrl = self::login_redirect_for(UrlFactory::my_subscriptions());
        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('mail_button_manage','local_subscriptions'),
            $manageUrl
        );

        self::deliver($user, $title, $html, $text);
    }

    public static function send_abandoned(\stdClass $pr): void {
        global $DB;
        $user = self::resolve_user_for_pr($pr);
        if (!$user || empty($user->email)) {
            // Optionnel: log discret pour debug
            error_log('[subs][mailer] skip abandoned: no recipient for PR #'.$pr->id);
            return;
        }
        $user = self::ensure_full_user($user);

        $plan = $DB->get_record('subscription_plan', ['id'=>$pr->planid], 'id,name', IGNORE_MISSING);
        $title = get_string('email_abandoned_subject', 'local_subscriptions');
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, null);
        $price = '';
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur);
        }

        $planname = local_subscriptions_plan_display_name($plan);
        $retryurl = self::build_retry_url($pr);

        $tbl = MailRenderer::table()
            ->lined()
            ->plan($planname)
            ->amount($price)
            ->render();

        $body = \html_writer::tag('p', get_string('email_abandoned_intro', 'local_subscriptions'));
        $body .= $tbl;
        $body .= self::pr_ref_badge($pr);

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('email_button_retry', 'local_subscriptions'), 
            $retryurl
        );
        
        self::deliver($user, $title, $html, $text);
    }


    public static function send_failed(\stdClass $pr): void {
        global $DB;
        $user = self::resolve_user_for_pr($pr);
        if (!$user) { return; }
        $user = self::ensure_full_user($user);

        $plan = $DB->get_record('subscription_plan', ['id'=>$pr->planid], 'id,name', IGNORE_MISSING);
        $title = get_string('email_failed_subject', 'local_subscriptions');
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, null);
        $price = '';
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur);
        }

        $planname = local_subscriptions_plan_display_name($plan);
        $retryurl = self::build_retry_url($pr);

        $tbl = MailRenderer::table()
            ->lined()
            ->plan($planname)
            ->amount($price)
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', s(trim(($user->firstname ?? '').' '.($user->lastname ?? '')))));
        $body .= \html_writer::tag('p', get_string('email_failed_intro', 'local_subscriptions'));
        $body .= $tbl;
        $body .= \html_writer::tag('p', get_string('email_failed_help', 'local_subscriptions'));
        $body .= self::pr_ref_badge($pr);

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('email_button_retry', 'local_subscriptions'), 
            $retryurl
        );
        
        self::deliver($user, $title, $html, $text);
    }

    public static function send_reminder(\stdClass $pr): void {
        global $DB;

        $user = self::resolve_user_for_pr($pr);
        if (!$user) { return; }
        $user = self::ensure_full_user($user);

        // Récup plan (pour le nom affichable)
        $plan   = $DB->get_record('subscription_plan', ['id' => $pr->planid], 'id,name', IGNORE_MISSING);
        $planname = $plan ? local_subscriptions_plan_display_name($plan) : ('#'.$pr->planid);

        // Montant depuis la PR (plus fiable pour un rappel)
        $price   = '';
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, null);
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur);
        }


        // Lien de relance (crée aussi retry_token / retry_expires si manquants)
        $retryurl = self::build_retry_url($pr);
        $expires  = !empty($pr->retry_expires) ? userdate((int)$pr->retry_expires) : '';

        $title = get_string('email_reminder_subject', 'local_subscriptions');        

        $body = \html_writer::tag('p', get_string('email_reminder_intro', 'local_subscriptions'));
        
        // Tableau d’infos
        $tbl = MailRenderer::table()
            ->lined()
            ->plan($planname)
            ->amount($price);
        if ($expires !== '') {
            // libellé “valide jusqu’à”
            $tbl->row('email_retry_expires', MailRenderer::code($expires));
        }
        $body .= $tbl->render();        
        $body .= self::pr_ref_badge($pr);

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('email_button_retry', 'local_subscriptions'), 
            $retryurl
        );
        
        self::deliver($user, $title, $html, $text);
    }
    
    public static function send_reminder_second(\stdClass $pr): void {
        global $DB;

        $user = self::resolve_user_for_pr($pr);
        if (!$user) { return; }
        $user = self::ensure_full_user($user);

        // Plan + prix
        $plan   = $DB->get_record('subscription_plan', ['id' => $pr->planid], 'id,name', IGNORE_MISSING);
        $planname = $plan ? local_subscriptions_plan_display_name($plan) : ('#'.$pr->planid);
        $price   = '';
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, null);
        if ($amt !== null && $cur) {
            $price = self::money($amt, $cur);
        }

        // Lien + expiration
        $retryurl = self::build_retry_url($pr);
        $expires  = !empty($pr->retry_expires) ? userdate((int)$pr->retry_expires) : '';

        $title = get_string('email_reminder2_subject', 'local_subscriptions');

        $body = \html_writer::tag('p', get_string('email_reminder2_intro', 'local_subscriptions'));

        // Tableau d’infos
        $tbl = MailRenderer::table()
            ->lined()
            ->plan($planname)
            ->amount($price);
        if ($expires !== '') {
            // libellé “valide jusqu’à”
            $tbl->row('email_retry_expires', MailRenderer::code($expires));
        }
        $body .= $tbl->render();        
        $body .= self::pr_ref_badge($pr);

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('email_button_retry', 'local_subscriptions'), 
            $retryurl
        );
        
        self::deliver($user, $title, $html, $text);
    }

    public static function send_recurring_started(\stdClass $user, \stdClass $plan, \stdClass $pr): void {
        $user = self::ensure_full_user($user);
        
        // Titre du mail (header du template)
        $planname = local_subscriptions_plan_display_name($plan);
        $title = get_string(
            'mail_recurring_started_subject',
            'local_subscriptions',
            format_string($planname)
        );

        $tbl = MailRenderer::table()
            ->lined()
            ->plan(format_string($planname))
            ->row('receipt_period', MailRenderer::code(userdate(time()))) // date de démarrage info
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('mail_recurring_started_body', 'local_subscriptions', [
            'plan'  => format_string($planname),
            'start' => userdate(time()),
        ]));
        $body .= $tbl;
        $body .= self::pr_ref_badge($pr);

        $manageUrl = self::login_redirect_for(UrlFactory::my_subscriptions());
        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('view_my_subscriptions', 'local_subscriptions'),
            $manageUrl
        );

        self::deliver($user, $title, $html, $text);
    }

    /**
     * Relance d’expiration J–X (jours dynamiques) pour une souscription non récurrente.
     */
    public static function send_subscription_expiry_reminder(\stdClass $user, \stdClass $plan, \stdClass $sub, int $daysleft): void {
        $user = self::ensure_full_user($user);

        $daysleft = max(0, $daysleft); // sécurité
        $planname = local_subscriptions_plan_display_name($plan);
        $enddate  = userdate((int)$sub->end_date);

        // Sujet et corps
        // Cas particulier J–0 si tu veux un texte différent (optionnel)
        $title = get_string('expiry_reminder_subject', 'local_subscriptions', $daysleft);

        if ($daysleft === 0) {
            $title = get_string('expiry_reminder_subject_today', 'local_subscriptions');
            // et un body spécifique si tu veux
        }

        $tbl = MailRenderer::table()
            ->lined()
            ->row('receipt_plan', s($planname))
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('expiry_reminder_body', 'local_subscriptions', [
            'plan' => $planname,
            'date' => $enddate,
        ]));
        $body .= $tbl;

        $subUrl = UrlFactory::subscribe((int)$sub->planid);
        $subUrl = self::login_redirect_for($subUrl);
        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('renew_now', 'local_subscriptions'),
            $subUrl
        );

        self::deliver($user, $title, $html, $text);
    }


    /**
     * Notification d’activation d’une brique "queued" -> "active".
     */
    public static function send_subscription_activated(\stdClass $user, \stdClass $plan, \stdClass $sub): void {
        $user = self::ensure_full_user($user);
        
        $planname = local_subscriptions_plan_display_name($plan);
        $title = get_string('subscription_activated_subject', 'local_subscriptions', $planname);

        $body = ''
            . \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('subscription_activated_body', 'local_subscriptions', $planname));
        $body .= MailRenderer::table()
                    ->lined()
                    ->plan($planname)
                    ->period_ts_short_2l($sub->start_date, $sub->end_date)
                    ->render();

        $manageUrl = self::login_redirect_for(UrlFactory::my_subscriptions());
        [$html, $text] = MailRenderer::layout(
            $title, 
            $body,  
            get_string('view_my_subscriptions', 'local_subscriptions'), 
            $manageUrl
        );

        self::deliver($user, $title, $html, $text);
    }

    /**
     * Notification d’expiration (pas de brique suivante).
     */
    public static function send_subscription_expired(\stdClass $user, \stdClass $plan, \stdClass $sub): void {
        $user = self::ensure_full_user($user);
        
        $planname = local_subscriptions_plan_display_name($plan);
        $enddate  = userdate((int)$sub->end_date);

        $title = get_string('subscription_expired_subject', 'local_subscriptions', $planname);

        $tbl = MailRenderer::table()
            ->lined()
            ->row('receipt_plan', s($planname))
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('subscription_expired_body', 'local_subscriptions', [
            'plan' => $planname,
            'date' => $enddate,
        ]));
        $body .= $tbl;

        $subUrl = UrlFactory::subscribe((int)$sub->planid);
        $subUrl = self::login_redirect_for($subUrl);

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('expired_button_renew', 'local_subscriptions'), 
            $subUrl
        );

        self::deliver($user, $title, $html, $text);
    }

    public static function send_upgrade_confirmation(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        $user = self::ensure_full_user($user);
        $planname = local_subscriptions_plan_display_name($plan);
        $title = get_string('upgrade_confirmed_subject', 'local_subscriptions', format_string($planname));

        // Montant final payé (sub/pr)
        [$amt, $cur] = self::final_price_from_pr_and_sub($pr, $sub);
        $paid = '';
        if ($amt !== null && $cur) {
            $paid = self::money($amt, $cur);
        }

        $tbl = MailRenderer::table()
            ->lined()
            ->row('receipt_plan', format_string($planname))
            ->period_ts_short_2l($sub->start_date, $sub->end_date);

        if ($paid !== '') {
            $tbl->row('receipt_total', $paid);
        }        

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('upgrade_confirmed_body', 'local_subscriptions', format_string($planname)));
        $body .= $tbl->render();

        $manageUrl = self::login_redirect_for(UrlFactory::my_subscriptions());
        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('view_my_subscriptions', 'local_subscriptions'),
            $manageUrl
        );

        self::deliver($user, $title, $html, $text);
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

        $user = self::ensure_full_user($user);
        $planname = local_subscriptions_plan_display_name($plan);

        // Sujet : “Renouvellement confirmé – {Nom du plan}”
        $title = get_string('renewal_subject', 'local_subscriptions', format_string($planname));

        // Montant (si fourni par l’événement Stripe)
        $price = null;
        if ($amount !== null) {
            $cur   = strtoupper($currency ?? '');
            $price = format_float((float)$amount, 2) . ' ' . $cur;
        }

        $table = MailRenderer::table()
            ->lined()
            ->row_if('receipt_amount', $price)
            ->period_ts_short_2l(($oldend !== null) ? $oldend : $sub->start_date, $sub->end_date);
        if (!empty($invoiceid)) {
            $table->row_code('receipt_invoice', $invoiceid);
        }

        // Corps HTML
        $body = ''
            . \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)))
            . \html_writer::tag('p', get_string('renewal_body', 'local_subscriptions', format_string($planname)));
        $body .= $table->render();

        $manageUrl = self::login_redirect_for(UrlFactory::my_subscriptions());
        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('mail_button_manage', 'local_subscriptions'),
            $manageUrl
        );

        self::deliver($user, $title, $html, $text);
    }

    public static function send_failed_recurring($user, $plan, array $opts = []): void {
        global $DB;

        $planname = local_subscriptions_plan_display_name($plan);
        $user = self::ensure_full_user($user);

        $title = get_string('recurring_failed_subject', 'local_subscriptions', format_string($planname));

        // Message de base.
        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('recurring_failed_body', 'local_subscriptions', format_string($planname)));

        $tbl = MailRenderer::table()
            ->lined();
        if (!empty($opts['amount']) && !empty($opts['currency'])) {
            $tbl->row_code('receipt_amount', sprintf('%.2f %s', $opts['amount'], strtoupper($opts['currency'])));
        }
        if (!empty($opts['invoiceid'])) { $tbl->row_code('receipt_invoice', (string)$opts['invoiceid']); }
        if (!empty($opts['failcode']))  { $tbl->row_code('payment_failcode', (string)$opts['failcode']); }
        if (!empty($opts['nextretry'])) { $tbl->row_code('payment_nextretry', userdate((int)$opts['nextretry'])); }

        $body .= $tbl->render();

        $portalUrl = self::login_redirect_for(UrlFactory::portal());

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('recurring_failed_button', 'local_subscriptions'),
            $portalUrl
        );

        self::deliver($user, $title, $html, $text);
    }


    public static function send_cancellation_info($user, $plan, $sub, ?int $atperiodend = null): void {
        global $DB;
        $planname = local_subscriptions_plan_display_name($plan);

        // Sécurise fullname()/email_to_user()
        $user = self::ensure_full_user($user);

        $title = get_string('recurring_canceled_subject', 'local_subscriptions', format_string($planname));

        $start = $sub && !empty($sub->start_date) ? (int)$sub->start_date : 0;
        $end   = $sub && !empty($sub->end_date)   ? (int)$sub->end_date   : 0;

        $effectline = $atperiodend
            ? get_string('recurring_canceled_effect_on',  'local_subscriptions', $end ? userdate($end) : '')
            : get_string('recurring_canceled_effect_now', 'local_subscriptions');

        $tbl = MailRenderer::table()
            ->lined()
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('recurring_canceled_body', 'local_subscriptions', format_string($planname)));
        $body .= \html_writer::tag('p', $effectline);
        $body .= $tbl;

        $subUrl = self::login_redirect_for(new \moodle_url('/local/subscriptions/subscribe.php'));
        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('recurring_canceled_button', 'local_subscriptions'),
            $subUrl
        );

        self::deliver($user, $title, $html, $text);
    }

    public static function send_contact_admin(string $toemail, string $fullname, string $fromemail, string $message, array $meta = []): void {
        global $CFG;

        // destinataire "admin" (pseudo user complet pour éviter ensure_full_user() DB)
        $rcpt = self::pseudo_user($toemail, 'CampusFR', 'support');
        $fromOV = self::pseudo_from($toemail, 'CampusFR support');

        $title = get_string('contact_admin_subject', 'local_subscriptions'); // i18n plus bas

        $table = MailRenderer::table()
            ->lined()
            ->row('contact_label_name',  MailRenderer::code($fullname !== '' ? $fullname : '—'))
            ->row('contact_label_email', MailRenderer::code($fromemail));

        // META techniques (optionnelles)
        if (!empty($meta['ip'])) {
            $table->row('contact_label_ip', MailRenderer::code((string)$meta['ip']));
        }
        if (!empty($meta['useragent'])) {
            $table->row('contact_label_ua', s((string)$meta['useragent']));
        }

        // Message
        $table->row('contact_label_msg', nl2br(s($message)));

        $body = $table->render();

        // ===== Pré-réponse “mailto:” (texte brut) =====
        $reSubject = get_string('contact_reply_subject', 'local_subscriptions'); // "Re : …"
        $greet     = get_string('contact_reply_greeting', 'local_subscriptions',
                        trim($fullname) ?: get_string('anonymous', 'block_edly_contact_form'));
        $remind    = get_string('contact_reply_reminder', 'local_subscriptions'); // "Rappel de votre message :"
        $marker    = get_string('contact_reply_marker',   'local_subscriptions'); // "— Réponse ci-dessous —"

        // Citation à la TB : chaque ligne préfixée par "> "
        $quoted = '> ' . preg_replace("/(\r\n|\r|\n)/", "$1> ", trim($message));

        $mailtoBody =
            $greet . "\r\n" .
            "\r\n" .        // 1re ligne vide
            "\r\n" .        // 2e ligne vide (l’admin écrit ici sa réponse)
            $marker . "\r\n" .
            "\r\n" .
            $remind . "\r\n" .
            $quoted . "\r\n";

        $mailto = 'mailto:'.rawurlencode($fromemail)
                . '?subject='.rawurlencode($reSubject)
                . '&body='   .rawurlencode($mailtoBody);

        // ===== Lien admin HTML (éditeur) avec HMAC =====
        $buttonAdminHtml = '';
        if (!empty($meta['replyurl'])) {
            $buttonAdminHtml =
                \html_writer::empty_tag('hr', ['style'=>'border:none;border-top:1px solid #eee;margin:12px 0;']) .
                \html_writer::link(
                    $meta['replyurl'],
                    get_string('reply_in_admin', 'local_subscriptions'),
                    ['style'=>'display:inline-block;padding:10px 14px;background:#0f6c76;color:#fff;border-radius:8px;text-decoration:none;']
                );
            $body .= $buttonAdminHtml; // on ajoute au corps avant layout
        }

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('reply_now', 'local_subscriptions'),
            $mailto
        );

        // ✅ From = support@… ; Reply-To = utilisateur
        self::deliver_from($rcpt, $fromOV, $title, $html, $text, $fromemail, $fullname);
    }

    public static function send_contact_ack(string $toemail, string $fullname, string $message, ?string $fromsupport = null): void {
        global $CFG;
        // accusé côté émetteur
        $rcpt = self::pseudo_user($toemail, ($fullname ?: '—'), '');
        $from = self::pseudo_from(
            $fromsupport ?: ((string)($CFG->supportemail ?? $CFG->noreplyaddress)),
            'CampusFR support'
        );

        $title = get_string('contact_copy_subject', 'local_subscriptions');

        $siteurl = (new \moodle_url('/'))->out(false);
        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', s(trim($fullname))));
        $body .= \html_writer::tag('p', get_string('contact_copy_intro', 'local_subscriptions'));
        $body .= \html_writer::tag('p', '<strong>'.get_string('contact_label_msg','local_subscriptions').'</strong><br>'.nl2br(s($message)));

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('view_site','local_subscriptions'),
            $siteurl
        );

        // ✅ From = support@… ; Reply-To = support@…
        self::deliver_from($rcpt, $from, $title, $html, $text, $from->email, 'CampusFR support');
    }


    /** Essai démarré (site-wide) */
    public static function send_trial_started(array $p): void {
        global $SITE, $CFG;

        $to        = (string)$p['toemail'];
        $first     = (string)($p['firstname'] ?? '');
        $loginurl  = (string)($p['login_url'] ?? '');
        $mycourses = (string)($p['mycourses_url'] ?? $loginurl);

        $email     = (string)($p['email'] ?? '');
        $reseturl  = (string)($p['reset_url'] ?? '');

        $subject  = get_string('mail_trial_started_subject', 'local_campus');

        // Intro (ex: "Hi X, your 7-day trial has started!")
        $vars = (object)['firstname' => $first];
        $body = \html_writer::tag('p',
            get_string('mail_trial_started_body', 'local_campus', $vars)
        );

        // Tableau des infos de connexion (login = email, pas de mot de passe)
        if ($email !== '') {
            $labelUser  = get_string('trial_username_label', 'local_campus'); // "Email de connexion"

            $tablehtml  = MailRenderer::open();
            $tablehtml .= MailRenderer::tr(
                $labelUser,
                MailRenderer::code($email),
                false
            );
            $tablehtml .= MailRenderer::close();

            $body .= $tablehtml;

            // Rappel sécurité + reset
            if ($reseturl !== '') {
                $body .= \html_writer::tag('p',
                    get_string('mail_trial_reset_hint', 'local_campus', (object)['url' => $reseturl])
                );
            } else {
                $body .= \html_writer::tag('p',
                    get_string('mail_trial_security_hint', 'local_campus')
                );
            }
        }

        // Paragraphe "vous pouvez accéder à vos cours d’essai…"
        if ($mycourses !== '') {
            $mycoursesLogin = self::login_redirect_for($mycourses);
            $body .= \html_writer::tag('p',
                get_string('mail_trial_started_mycourses', 'local_campus', (object)['url' => $mycoursesLogin])
            );
        }

        // Remise (facultative) : X % et deadline
        $dpct = (string)(get_config('local_subscriptions', 'trial_discount_percent') ?: '');
        $ddur = (int)get_config('local_subscriptions', 'trial_discount_hours') ?: '';

        if ($dpct !== '' && $ddur > 0) {
            $ddur = (string)($ddur / 24);
            $body .= \html_writer::tag('p',
                get_string('mail_trial_discount_line', 'local_campus',
                    (object)['pct' => $dpct, 'duration' => $ddur]
                )
            );

            $brandcolor = get_config('local_subscriptions', 'brand_color') ?: '#005f73';
            $brandcolorDark = get_config('local_subscriptions', 'brand_color_dark') ?: '#013140';
            $buttonurl = (new \moodle_url('/local/subscriptions/subscribe.php'))->out(false);
            $buttonurl  = self::login_redirect_for($buttonurl);
            $buttonlabel = get_string('mail_trial_discount_btn', 'local_campus',
                    (object)['pct' => $dpct]);
            $btn = '
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:24px auto;">
            <tr>
                <td bgcolor="'.s($brandcolor).'" style="border-radius:8px;">
                <a href="'.s($buttonurl).'"
                    style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-weight:600;border-radius:8px;background:'.s($brandcolor).';"
                    onmouseover="this.style.background=\''.s($brandcolorDark).'\';"
                    onmouseout="this.style.background=\''.s($brandcolor).'\';"
                >'.s($buttonlabel).'</a>
                </td>
            </tr>
            </table>';


            $body .= $btn;
        }

        $supportEmail = (string)(get_config('local_subscriptions', 'support_email') ?: '');

        $body .= \html_writer::tag('p',
            get_string('mail_trial_started_support', 'local_campus', (object)['url' => $supportEmail])
        );
        $body .= 
            '<img src="'.$CFG->wwwroot . '/local/subscriptions/pix/email/mailTrialFooter.png"
                            style="display:block;width:100%;object-fit:cover;border:0;outline:none;text-decoration:none;">';


        [$html, $text] = MailRenderer::layout(
            $subject,
            $body,
            '', '',
        );

        self::deliver(self::pseudo_user($to, $first, ''), $subject, $html, $text);
    }


    public static function send_trial_rem3(array $p): void {
        $to      = (string)$p['toemail'];
        $first   = (string)$p['firstname'];
        $conturl = (string)$p['continue_url'];   // bouton 1 (continuer essai)
        $suburl  = (string)$p['subscribe_url'];  // bouton 2 (s’abonner)
        $course  = (string)($p['course_fullname'] ?? '');
        $days    = (int)($p['daysleft'] ?? 3);

        $subject  = get_string('mail_trial_rem3_subject_generic','local_campus', $course);
        $bodyhtml = format_text(
            get_string('mail_trial_rem3_body','local_campus', (object)['firstname'=>$first,'daysleft'=>$days]),
            FORMAT_HTML
        );

        // ➜ Nouvelle méthode du renderer qui ajoute un 2e bouton SANS casser layout()
        $conturlLogin = self::login_redirect_for($conturl);
        $suburlLogin  = self::login_redirect_for($suburl);

        [$html, $text] = MailRenderer::layout_with_extra_button(
            $subject,
            $bodyhtml,
            get_string('mail_trial_cta_continue','local_campus'), $conturlLogin,  // bouton secondaire (dans le corps)
            get_string('mail_trial_cta_subscribe','local_campus'), $suburlLogin   // bouton principal (celui de layout)
        );

        self::deliver(self::pseudo_user($to, $first, ''), $subject, $html, $text);
    }
    
    public static function send_trial_pre_suspend(array $p): void {
        $to    = (string)$p['toemail'];
        $first = (string)$p['firstname'];
        $sdate = userdate((int)$p['suspend_date']);
        $suburl= (string)$p['subscribe_url'];

        $subject = get_string('trial_presuspend_subject','local_campus');
        $bodyhtml = format_text(
            get_string('trial_presuspend_body','local_campus', (object)['firstname'=>$first,'date'=>$sdate]),
            FORMAT_HTML
        );

        $suburlLogin = self::login_redirect_for($suburl);

        [$html,$text] = MailRenderer::layout(
            $subject, $bodyhtml,
            get_string('mail_trial_cta_subscribe','local_campus'),
            $suburlLogin
        );
        self::deliver(self::pseudo_user($to, $first, ''), $subject, $html, $text);
    }

    public static function send_trial_suspended(array $p): void {
        $to     = (string)$p['toemail'];
        $first  = (string)$p['firstname'];
        $sdate  = userdate((int)$p['suspend_date']);
        $ddate  = userdate((int)$p['delete_date']);
        $suburl = (string)$p['subscribe_url'];

        $subject = get_string('trial_suspended_subject','local_campus');
        $bodyhtml = format_text(
            get_string('trial_suspended_body','local_campus', (object)[
                'firstname'=>$first, 'sdate'=>$sdate, 'ddate'=>$ddate
            ]),
            FORMAT_HTML
        );

        $suburlLogin = self::login_redirect_for($suburl);

        [$html,$text] = MailRenderer::layout(
            $subject, $bodyhtml,
            get_string('mail_trial_cta_subscribe','local_campus'),
            $suburlLogin
        );
        self::deliver(self::pseudo_user($to, $first, ''), $subject, $html, $text);
    }
    public static function send_trial_expired(array $p): void {
        $to      = (string)$p['toemail'];
        $first   = (string)$p['firstname'];
        $course  = (string)($p['course_fullname'] ?? '');
        $suburl  = (string)$p['subscribe_url'];
        $sdate   = !empty($p['suspend_date']) ? userdate((int)$p['suspend_date'], get_string('strftimedatefullshort')) : null;

        $subject  = get_string('mail_trial_expired_subject_generic','local_campus', $course);

        $vars = (object)['firstname'=>$first];
        $body = get_string('mail_trial_expired_body','local_campus', $vars);

        if ($sdate) {
            $body .= '<br>'.get_string('mail_trial_expired_hint_suspend','local_campus', $sdate);
        }

        $suburlLogin = self::login_redirect_for($suburl);

        [$html, $text] = MailRenderer::layout(
            $subject, format_text($body, FORMAT_HTML),
            get_string('mail_trial_cta_subscribe','local_campus'), $suburlLogin
        );
        self::deliver(self::pseudo_user($to, $first, ''), $subject, $html, $text);
    }

    /** Crée un "user" complet in-memory pour email_to_user + ensure_full_user() */
    public static function pseudo_user(string $email, string $firstname = '', string $lastname = ''): \stdClass {
        $u = \core_user::get_support_user();      // a tous les champs étendus
        $u = self::ensure_full_user($u);          // no-op ici, mais garde la forme “complète”
        $clone = clone $u;

        $clone->id        = -100;                 // ≠ 0 pour éviter "null user"
        $clone->email     = $email;
        $clone->firstname = $firstname !== '' ? $firstname : $u->firstname;
        $clone->lastname  = $lastname;
        $clone->mailformat = 1;
        $clone->deleted    = 0;
        $clone->suspended  = 0;
        $clone->confirmed  = 1;

        return $clone;
    }

    private static function safe_support_from(): \stdClass {
        global $CFG;
        try {
            $sp = self::ensure_full_user(\core_user::get_support_user());
            return $sp;
        } catch (\Throwable $e) {
            // Repli : construit un "from" complet avec l'adresse support/noreply
            $email = (string)($CFG->supportemail ?? $CFG->noreplyaddress ?? 'noreply@example.com');
            return self::pseudo_from($email, 'CampusFR support');
        }
    }
    
    /** Clone du user support avec une adresse FROM spécifique (complète pour email_to_user). */
    public static function pseudo_from(string $email, string $name = 'CampusFR support'): \stdClass {
        $base = self::ensure_full_user(\core_user::get_support_user());
        $from = clone $base;
        $from->id        = -200;        // ≠ 0
        $from->email     = $email;
        $from->firstname = $name;
        $from->lastname  = '';
        $from->mailformat= 1;
        $from->deleted   = 0;
        $from->suspended = 0;
        $from->confirmed = 1;
        return $from;
    }

    /** Envoi avec FROM override + Reply-To optionnels. */
    public static function deliver_from(\stdClass $to, \stdClass $from, string $subject, string $html, string $text, ?string $replyto = null, ?string $replytoname = null): void {
        if (self::$preview) { self::$last = ['subject'=>$subject,'html'=>$html,'text'=>$text]; return; }
        $rcpt = self::ensure_full_user($to);
        $from = self::ensure_full_user($from);
        $rcpt->mailformat = 1;
        @email_to_user($rcpt, $from, $subject, $text, $html, '', '', true, $replyto, $replytoname);

        // Copie admin enrichie (mêmes règles que deliver)
        $copy = trim((string)(get_config('local_subscriptions','email_copy_to') ?? ''));
        if ($copy !== '') {
            $list = preg_split('/[,;\s]+/', $copy, -1, PREG_SPLIT_NO_EMPTY);

            // Prépare FROM sûr
            $fromOV = self::safe_support_from();

            // Préfixe
            $rawtype = (string)((self::$ctx)['type'] ?? '');
            $label   = self::type_label($rawtype);
            $prid    = (!empty(self::$ctx['args']['pr']->id)) ? (' PR#'.(int)self::$ctx['args']['pr']->id) : '';
            $pref    = $label ? '['.$label.']'.$prid.' ' : '';

            $appendHtml = ''; $appendTxt = '';
            if (self::copy_verbose_enabled()) {
                [$appendHtml, $appendTxt] = self::build_copy_appendix();
            }

            foreach ($list as $addr) {
                $addr = trim($addr);
                if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) { continue; }

                $admin = self::ensure_full_user(\core_user::get_support_user());
                $admin->email      = $addr;
                $admin->mailformat = 1;

                $copysubject = $pref . '[COPY] ' . $subject;
                @email_to_user($admin, $fromOV, $copysubject, $text.$appendTxt, $html.$appendHtml, '', '', true, $replyto, $replytoname);
            }
        }
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
        return (UrlFactory::retry(['pid'=>$pr->id,'t'=>$pr->retry_token]))->out(false);
    }

    private static function pr_ref_badge(?\stdClass $pr): string {
        // Masqué pour les clients; affiché seulement en preview/param admin
        if (!self::show_tech_footers()) {
            return '';
        }
        if (!$pr) { return ''; }

        $parts = [];
        if (isset($pr->id) && $pr->id) {
            $parts[] = 'PR #'.(int)$pr->id; // (tu peux i18n si tu veux)
        } else {
            $parts[] = 'Preview';
        }
        if (!empty($pr->creation_date)) {
            $parts[] = userdate((int)$pr->creation_date);
        }
        if (!$parts) { return ''; }

        return '<div style="margin-top:12px;font-size:11px;color:#94a3b8;">'
            . s(implode(' · ', $parts))
            . '</div>';
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

    /** Raccourci get_string avec composant. */
    private static function t(string $key, $a = null): string {
        return get_string($key, 'local_subscriptions', $a);
    }    


    /**
     * Calcule le montant final payé et la devise à afficher à partir d'une
     * payment request (PR) et éventuellement d'une souscription liée.
     *
     * Priorités :
     *  1) $sub->pricepaid / $sub->currency (si dispo)
     *  2) $pr->locked_final_price / $pr->currency
     *  3) $pr->amount_minor (cents) / $pr->currency
     *  4) $pr->price / $pr->currency
     *
     * @return array{0: ?float, 1: ?string} [montant, devise]
     */
    private static function final_price_from_pr_and_sub(?\stdClass $pr = null, ?\stdClass $sub = null): array {
        $amount = null;
        $cur    = null;

        // 1) Souscription : source de vérité quand elle existe
        if ($sub && isset($sub->pricepaid) && $sub->pricepaid !== null && $sub->pricepaid !== '') {
            $amount = (float)$sub->pricepaid;
            $cur    = $sub->currency ?? null;
        }

        // 2) Payment request
        if ($pr instanceof \stdClass) {
            if ($amount === null) {
                if (isset($pr->locked_final_price) && is_numeric($pr->locked_final_price) && (float)$pr->locked_final_price > 0) {
                    $amount = (float)$pr->locked_final_price;
                } elseif (isset($pr->amount_minor) && is_numeric($pr->amount_minor) && (int)$pr->amount_minor > 0) {
                    $amount = ((int)$pr->amount_minor) / 100.0;
                } elseif (isset($pr->price) && is_numeric($pr->price)) {
                    $amount = (float)$pr->price;
                }
            }
            if ($cur === null && !empty($pr->currency)) {
                $cur = (string)$pr->currency;
            }
        }

        if ($amount === null) {
            return [null, null];
        }
        if ($cur !== null) {
            $cur = strtoupper($cur);
        }
        return [$amount, $cur];
    }

    /**
     * Enveloppe une URL interne derrière la page de login,
     * en ajoutant ?returnurl=... pour forcer la connexion avant d'y accéder.
     *
     * - Accepte une string ou un moodle_url.
     * - Ne touche pas aux mailto:, javascript:, ni aux URLs externes.
     * - Ne re-wrap pas /login/index.php ni /login/forgot_password.php.
     */
    private static function login_redirect_for($target): string {
        global $CFG;

        if ($target instanceof \moodle_url) {
            $targeturl = $target->out(false);
        } else {
            $targeturl = (string)$target;
        }

        if ($targeturl === '') {
            return $targeturl;
        }

        $lower = strtolower($targeturl);

        // Ne jamais wrapper les mailto: ou javascript:
        if (strpos($lower, 'mailto:') === 0 || strpos($lower, 'javascript:') === 0) {
            return $targeturl;
        }

        // Ne pas toucher aux URLs externes (autre domaine que Moodle)
        if (preg_match('~^https?://~i', $targeturl) && strpos($targeturl, $CFG->wwwroot) !== 0) {
            return $targeturl;
        }

        // Ne pas re-wrapper la page de login ou la page de reset
        $path = parse_url($targeturl, PHP_URL_PATH) ?? '';
        if (preg_match('~^/login/(index\.php|forgot_password\.php)~', $path)) {
            return $targeturl;
        }

        $login = new \moodle_url('/login/index.php', ['returnurl' => $targeturl]);
        return $login->out(false);
    }    

    /** Format monétaire major units (EUR, etc.). */
    private static function money(float $amount, ?string $cur): string {
        $cur = $cur ? strtoupper($cur) : '';
        return format_float($amount, 2).' '.s($cur);
    }

    /** Envoi robuste (support user, try/catch). */
    public static function deliver(\stdClass $user, string $subject, string $html, string $text): void {

        $subject = self::local_subscriptions_plain_text($subject);

        // Mode preview: ne pas appeler email_to_user (évite le debugging() de noemailever)
        if (self::$preview) {
            self::$last = ['subject' => $subject, 'html' => $html, 'text' => $text];
            return;
        }

        $recipient = self::ensure_full_user($user);
        $recipient->mailformat = 1;

        // FROM sûr (jamais d'exception même si user support supprimé)
        $from = self::safe_support_from();

        @email_to_user($recipient, $from, $subject, $text, $html);

        // --- Copie admin (log@...) enrichie ---
        $copy = trim((string)(get_config('local_subscriptions', 'email_copy_to') ?? ''));
        if ($copy !== '') {
            $list = preg_split('/[,;\s]+/', $copy, -1, PREG_SPLIT_NO_EMPTY);

            $from = self::safe_support_from();

            // Préfixe sujet : [T_xxx][PR#123] …
            $rawtype = (string)((self::$ctx)['type'] ?? '');
            $label   = self::type_label($rawtype);
            $prid    = (!empty(self::$ctx['args']['pr']->id)) ? (' PR#'.(int)self::$ctx['args']['pr']->id) : '';
            $pref    = $label ? '['.$label.']'.$prid.' ' : '';

            // Appendice technique (HTML + TXT)
            $appendHtml = ''; $appendTxt = '';
            if (self::copy_verbose_enabled()) {
                [$appendHtml, $appendTxt] = self::build_copy_appendix();
            }

            foreach ($list as $addr) {
                $addr = trim($addr);
                if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) { continue; }

                $rcpt = self::ensure_full_user(\core_user::get_support_user());
                $rcpt->email = $addr;
                $rcpt->mailformat = 1;

                $copysubject = $pref . $subject;
                @email_to_user($rcpt, $from, $copysubject, $text.$appendTxt, $html.$appendHtml);
            }
        }

    }    

    /** Afficher les infos techniques (ex: PR#) ? Preview CLI ou setting admin. */
    private static function show_tech_footers(): bool {
        // Affiche en mode preview (CLI de prévisualisation)
        if (property_exists(self::class, 'preview') && self::$preview === true) {
            return true;
        }
        // Ou si activé explicitement par un setting admin (désactivé par défaut)
        $cfg = (string)(get_config('local_subscriptions', 'email_show_pr_ref') ?? '0');
        return $cfg === '1';
    }


    public static function send_subscription_event(
        \stdClass $user,
        \stdClass $plan,
        \stdClass $pr,
        \stdClass $sub,
        ?string $tmppassword = null,
        bool $isupgrade = false,
        bool $isnewuser = false
    ): void {
        if (!empty($isupgrade)) {
            self::send_upgrade_confirmation($user, $plan, $pr, $sub);
        } else if (!empty($isnewuser)) {
            self::send_welcome($user, $tmppassword ?? '', $plan, $pr, $sub);
        } else {
            self::send_subscription_update($user, $plan, $pr, $sub);
        }
    }


    /** Contexte courant du mail en cours (type + args bruts) pour la copie log. */
    private static ?array $ctx = null;

    /** Active/désactive l’appendice technique pour les copies (via setting). */
    private static function copy_verbose_enabled(): bool {
        return (string)(get_config('local_subscriptions','email_copy_verbose') ?? '1') === '1';
    }

    /** Label lisible pour un type (préfixe sujet des copies). */
    private static function type_label(string $type): string {
        static $labels = [
            self::T_WELCOME               => 'T_WELCOME',
            self::T_UPGRADE_CONFIRMED     => 'T_UPGRADE_CONFIRMED',
            self::T_SUBSCRIPTION_UPDATED  => 'T_SUBSCRIPTION_UPDATED',
            self::T_RECEIPT               => 'T_RECEIPT',
            self::T_RECURRING_STARTED     => 'T_RECURRING_STARTED',
            self::T_PAYMENT_FAILED        => 'T_PAYMENT_FAILED',
            self::T_PAYMENT_ABANDONED     => 'T_PAYMENT_ABANDONED',
            self::T_SUBSCRIPTION_ACTIVATED=> 'T_SUBSCRIPTION_ACTIVATED',
            self::T_SUBSCRIPTION_EXPIRED  => 'T_SUBSCRIPTION_EXPIRED',
            self::T_SUBSCRIPTION_EXPIRY_REM=> 'T_SUBSCRIPTION_EXPIRY_REM',
            self::T_REMINDER_FIRST        => 'T_REMINDER_FIRST',
            self::T_REMINDER_SECOND       => 'T_REMINDER_SECOND',
            self::T_TRIAL_STARTED         => 'T_TRIAL_STARTED',
            self::T_TRIAL_REM3            => 'T_TRIAL_REM3',
            self::T_TRIAL_PRE_SUSPEND     => 'T_TRIAL_PRE_SUSPEND',
            self::T_TRIAL_SUSPENDED       => 'T_TRIAL_SUSPENDED',
            self::T_TRIAL_EXPIRED         => 'T_TRIAL_EXPIRED',
            // autres si besoin…
        ];
        return $labels[$type] ?? strtoupper($type);
    }

    /** Formate un timestamp Unix en 'd/m/Y H:i:s' (timezone utilisateur). */
    private static function fmtDebugTs($ts): ?string {
        if (!is_numeric($ts) || (int)$ts <= 0) { return null; }
        return userdate((int)$ts, '%d/%m/%Y %H:%M:%S');
    }

    /** Convertit certaines clés timestamp d'un tableau en dates lisibles. */
    private static function fmtTsArray(array $arr, array $keys): array {
        foreach ($keys as $k) {
            if (array_key_exists($k, $arr)) {
                $fmt = self::fmtDebugTs($arr[$k]);
                if ($fmt !== null) { $arr[$k] = $fmt; }
            }
        }
        return $arr;
    }


    /** Construit l’appendice technique HTML/TXT pour la copie log, à partir de self::$ctx. */
    private static function build_copy_appendix(): array {
        $ctx  = self::$ctx ?: [];
        $type = (string)($ctx['type'] ?? 'UNKNOWN');     // ex: 'receipt'
        $args = (array)($ctx['args'] ?? []);
        $label = self::type_label($type);                // ex: 'T_RECEIPT'

        $pick = function(array $src, array $keys): array {
            $out = [];
            foreach ($keys as $k) { if (array_key_exists($k, $src)) { $out[$k] = $src[$k]; } }
            return $out;
        };

        $pr  = $args['pr']   ?? null;
        $usr = $args['user'] ?? null;
        $pln = $args['plan'] ?? null;
        $sub = $args['sub']  ?? null;

        // Pour les mails de trial (T_TRIAL_*), on construit un "user" synthétique
        // à partir de toemail/email + firstname si aucun user n’a été passé.
        if (!$usr && in_array($type, [
                self::T_TRIAL_STARTED,
                self::T_TRIAL_REM3,
                self::T_TRIAL_PRE_SUSPEND,
                self::T_TRIAL_SUSPENDED,
                self::T_TRIAL_EXPIRED,
            ], true)) {

            $email = $args['email']   ?? $args['toemail']   ?? null;
            $fname = $args['firstname'] ?? null;

            if ($email || $fname) {
                $usr = (object)[
                    'id'        => null,
                    'email'     => (string)$email,
                    'firstname' => (string)$fname,
                ];
            }
        }



        // PR : champs utiles
        $prinfo = is_object($pr) ? $pick((array)$pr, [
            'id','payment_provider','price','currency','transactionid','sessionid','status',
            'locked_list_price','locked_discount_percent','locked_discount_amount','locked_discount_reason','locked_final_price',
            'amount_minor','creation_date','payment_date','locked_at','last_update','expires_at',
            'email','firstname','lastname','accept_language','created_ip','created_useragent','http_referer'
        ]) : [];

        // USER
        $uinfo = is_object($usr) ? $pick((array)$usr, [
            'id','username','email','firstname','lastname','lang','suspended','deleted','lastip'
        ]) : [];

        // PLAN
        $pinfo = is_object($pln) ? $pick((array)$pln, [
            'id','name','is_trial','is_recurring','duration_key','accessscopeid'
        ]) : [];

        // SUB
        $sinfo = is_object($sub) ? $pick((array)$sub, [
            'id','status','payment_provider','pricepaid','currency','transactionid',
            'start_date','end_date','creation_date','last_update','provider_subscription_id','provider_customer_id'
        ]) : [];

        // ➜ Formater les timestamps en clair
        $prinfo  = self::fmtTsArray($prinfo,  ['creation_date','payment_date','locked_at','last_update','expires_at','retry_expires','login_token_expires']);
        $sinfo   = self::fmtTsArray($sinfo,   ['start_date','end_date','creation_date','last_update']);

        // Compact pour JSON
        $data = [
            'type' => $label,   // ← met le tag canonique 'T_RECEIPT' au lieu de 'receipt'
            'user' => $uinfo,
            'plan' => $pinfo,
            'sub'  => $sinfo,
            'pr'   => $prinfo,
        ];

        // Si tout est vide (cas simple comme T_TRIAL_STARTED où il n’y a pas de user/plan/sub/pr),
        // on ajoute quand même les args bruts pour que le log soit utile.
        if (empty($uinfo) && empty($pinfo) && empty($sinfo) && empty($prinfo) && !empty($args)) {
            // On peut enlever 'lang' qui est pas très intéressant
            $raw = $args;
            unset($raw['lang']);
            $data['args'] = $raw;
        }


        // ----- HTML -----
        $html  = '<hr style="border:none;border-top:1px solid #eee;margin:16px 0;">';
        $html .= '<div style="font:13px/1.4 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#334155">';
        $html .= '<div style="font-weight:600;margin-bottom:6px">['
            .  htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            .  '] ' . 'Journal technique</div>';
        $html .= '<pre style="white-space:pre-wrap;word-break:break-word;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px;margin:0">'
            .  htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8')
            .  '</pre></div>';

        // ----- TXT -----
        $text  = "\n\n----- [" . $label . "] Journal technique -----\n";
        $text .= json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";

        return [$html, $text];
    }

    /**
     * Transforme un contenu HTML en texte brut lisible.
     *
     * @param string $html
     * @return string
     */
    private static function local_subscriptions_plain_text(string $html): string {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }



}
