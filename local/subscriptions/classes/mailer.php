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
    public const T_TRIAL_EXPIRED  = 'T_TRIAL_EXPIRED';

    /** @var array<string, string[]> mapping type -> required args keys */
    private const REQUIREMENTS = [
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
        self::T_SUBSCRIPTION_EXPIRY_REM=> ['user','plan','sub','remindkey'],
        self::T_REMINDER_FIRST         => ['pr'],
        self::T_REMINDER_SECOND        => ['pr'],
        self::T_CONTACT_ADMIN          => ['toemail','fullname','fromemail','message','meta'],
        self::T_CONTACT_ACK            => ['toemail','fullname','message'],

        self::T_TRIAL_STARTED => ['toemail','firstname','subscribe_url'], // subscribe_url optionnel
        self::T_TRIAL_REM3    => ['toemail','firstname','continue_url','subscribe_url','course_fullname','daysleft'],
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
        if (!isset(self::REQUIREMENTS[$type])) {
            throw new \coding_exception("Unknown mail type: {$type}");
        }
        // Validation des paramètres obligatoires.
        $missing = array_diff(self::REQUIREMENTS[$type], array_keys($args));
        if (!empty($missing)) {
            throw new \coding_exception('Missing mail args: '.implode(', ', $missing)." for type {$type}");
        }

        // Résolution de la langue d’e-mail (réglage plugin > langue user > langue site),
        // surchargeable via $args['lang'].
        $recipientoruser = $args['user'] ?? null;
        $lang = $args['lang'] ?? self::effective_emaillang($recipientoruser);

        $caller = function() use ($type, $args) {
            switch ($type) {
                // Paiements / commandes
                case self::T_SUBSCRIPTION_EVENT:
                    // send_subscription_event($user, $plan, $pr, $sub, $tmppassword, $isupgrade, $isnew)
                    return self::send_subscription_event(
                        $args['user'], $args['plan'], $args['pr'], $args['sub'],
                        $args['tmpPassword'], $args['isupgrade'], $args['isnewuser']
                    );

                case self::T_RECURRING_STARTED:
                    return self::send_recurring_started($args['user'], $args['plan'], $args['pr']);

                case self::T_RECEIPT:
                    return self::send_receipt($args['user'], $args['plan'], $args['pr'], $args['sub']);

                case self::T_PAYMENT_FAILED:
                    return self::send_failed($args['pr']);

                case self::T_PAYMENT_ABANDONED:
                    return self::send_abandoned($args['pr']);

                // Cycle d’abonnement
                case self::T_RENEWAL_OK:
                    return self::send_renewal_ok($args['user'], $args['plan'], $args['sub'],
                                                 $args['amount'], $args['currency'], $args['invoiceid'], $args['oldend']);

                case self::T_FAILED_RECURRING:
                    return self::send_failed_recurring($args['user'], $args['plan'], 
                        [
                            'amount'    => $args['amount']    ?? null,
                            'currency'  => $args['currency']  ?? null,
                            'invoiceid' => $args['invoiceid'] ?? null,
                            'failcode'  => $args['failcode']  ?? null,
                            'nextretry' => $args['nextretry'] ?? null,
                        ]);

                case self::T_CANCELLATION_INFO:
                    return self::send_cancellation_info($args['user'], $args['plan'], $args['sub'], $args['atperiodend']);

                // Tâches planifiées / suivi
                case self::T_SUBSCRIPTION_ACTIVATED:
                    return self::send_subscription_activated($args['user'], $args['plan'], $args['sub']);

                case self::T_SUBSCRIPTION_EXPIRED:
                    return self::send_subscription_expired($args['user'], $args['plan'], $args['sub']);

                case self::T_SUBSCRIPTION_EXPIRY_REM:
                    return self::send_subscription_expiry_reminder($args['user'], $args['plan'], $args['sub'], $args['remindkey']);

                case self::T_REMINDER_FIRST:
                    return self::send_reminder($args['pr']);

                case self::T_REMINDER_SECOND:
                    return self::send_reminder_second($args['pr']);

                case self::T_CONTACT_ADMIN:
                    return self::send_contact_admin($args['toemail'], $args['fullname'], $args['fromemail'], $args['message'], $args['meta']);

                case self::T_CONTACT_ACK:
                    return self::send_contact_ack($args['toemail'], $args['fullname'], $args['message'],$args['fromsupport'] ?? null);    

                case self::T_TRIAL_STARTED:
                    self::send_trial_started($args); break;

                case self::T_TRIAL_REM3:
                    self::send_trial_rem3($args); break;

                case self::T_TRIAL_EXPIRED:
                    self::send_trial_expired($args); break;

                default:
                    throw new \coding_exception("Unhandled mail type: {$type}");
            }
        };

        // Exécuter dans la langue choisie (sans polluer le reste).
        $old = current_language();
        if (!empty($lang)) {
            force_current_language($lang);
        }
        try {
            $caller(); // beaucoup de send_xxx sont void → pas d’exception = OK
            return true;
        } finally {
            force_current_language($old);
        }
    }

    /** Langue effective pour un e-mail (réglage plugin > user->lang > $CFG->lang). */
    private static function effective_emaillang(?\stdClass $user): string {
        global $CFG;
        $cfg = get_config('local_subscriptions');
        if (!empty($cfg->defaultemaillang)) {
            return $cfg->defaultemaillang;
        }
        if (!empty($user) && !empty($user->lang)) {
            return $user->lang;
        }
        return $CFG->lang ?? current_language();
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
    private static function send_welcome(\stdClass $user, string $tmpPassword, \stdClass $plan, \stdClass $pr): void {
        global $SITE;
        $user = self::ensure_full_user($user);
    
        $title = get_string('welcome_subject', 'local_subscriptions', $SITE->fullname);
        $loginurl = (new \moodle_url('/login/index.php', [
            'username' => (string)$user->username,   // pré-remplit le champ identifiant
        ]))->out(false);

        $price   = format_float((float)($pr->price ?? 0), 2).' '.strtoupper($pr->currency ?? '');
        $planname = local_subscriptions_plan_display_name($plan);

        $tbl = MailRenderer::table()
            ->lined()
            ->row('welcome_username', MailRenderer::code((string)$user->username))
            ->row('welcome_temp_password_label', MailRenderer::code((string)$tmpPassword))
            ->render();

        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('welcome_body_intro', 'local_subscriptions', $SITE->fullname));
        $body .= $tbl;

        /* 👉 on conserve bien l’avertissement de sécurité */
        $body .= \html_writer::tag('p', get_string('welcome_security_hint', 'local_subscriptions'));

        $body .= \html_writer::empty_tag('hr', ['style'=>'border:none;border-top:1px solid #eee;margin:18px 0;']);
        $body .= \html_writer::tag('p', get_string('welcome_plan_summary', 'local_subscriptions', $planname));
        $body .= \html_writer::tag('p', get_string('welcome_amount_summary', 'local_subscriptions', $price));
        $body .= self::pr_ref_badge($pr);

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('btn_signin','local_subscriptions'),
            $loginurl
        );

        self::deliver($user, $title, $html, $text);
    }


    /**
     * Envoie un reçu d'achat (branding Moodle). Le reçu Stripe continue d’être envoyé par Stripe.
     */
    public static function send_receipt(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        $user = self::ensure_full_user($user);

        $title    = get_string('receipt_title', 'local_subscriptions');
        $price    = format_float((float)($pr->price ?? 0), 2) . ' ' . strtoupper($pr->currency ?? '');
        $period   = userdate((int)$sub->start_date) . ' → ' . userdate((int)$sub->end_date);
        $planname = local_subscriptions_plan_display_name($plan);

        $table = MailRenderer::table()
            ->lined()
            ->plan(format_string($planname))
            ->amount($price)
            ->period_ts_short_2l($sub->start_date, $sub->end_date)
            ->provider($pr->payment_provider ?? '')
            ->txid($pr->transactionid ?? null)
            ->render();

        // Tableau récap : lignes de base.
        $body  = \html_writer::tag('p', get_string('mail_hello', 'local_subscriptions', fullname($user)));
        $body .= \html_writer::tag('p', get_string('receipt_intro', 'local_subscriptions'));
        $body .= $table;
    
        // Badge référence SPR (si tu as déjà cette méthode).
        $body .= self::pr_ref_badge($pr);

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('receipt_button_open','local_subscriptions'),
            (new \moodle_url('/'))->out(false)
        );

        self::deliver($user, $title, $html, $text);
    }

    /**
     * Envoie un email de confirmation d'abonnement pour un utilisateur EXISTANT (pas de mot de passe).
     */
    private static function send_subscription_update(
        \stdClass $user,
        \stdClass $plan,
        \stdClass $pr,
        \stdClass $sub
    ): void {
        $user = self::ensure_full_user($user);

        // Sujet
        $planname = local_subscriptions_plan_display_name($plan);
        $title = get_string('subupdate_subject', 'local_subscriptions', format_string($planname));

        // Montant payé (price en priorité, sinon amount)
        $amount   = isset($pr->price) ? (float)$pr->price : (float)($pr->amount ?? 0);
        $currency = strtoupper($pr->currency ?? '');
        $price    = format_float($amount, 2) . ' ' . $currency;

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

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('mail_button_manage','local_subscriptions'),
            (UrlFactory::my_subscriptions())->out(false)
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
        $price = format_float((float)($pr->price ?? 0), 2).' '.strtoupper($pr->currency ?? '');
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
        $price = format_float((float)($pr->price ?? 0), 2).' '.strtoupper($pr->currency ?? '');
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
        if (isset($pr->price) && isset($pr->currency)) {
            $price = format_float((float)$pr->price, 2) . ' ' . strtoupper((string)$pr->currency);
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
        if (isset($pr->price) && isset($pr->currency)) {
            $price = format_float((float)$pr->price, 2) . ' ' . strtoupper((string)$pr->currency);
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

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('view_my_subscriptions', 'local_subscriptions'),
            (UrlFactory::my_subscriptions())->out(false)
        );

        self::deliver($user, $title, $html, $text);
    }

    /**
     * Relance d’expiration J-30 / J-7 / J-1 pour une souscription non récurrente.
     * $remindkey ∈ {'d30','d7','d1'}
     */
    public static function send_subscription_expiry_reminder(\stdClass $user, \stdClass $plan, \stdClass $sub, string $remindkey): void {
        $user = self::ensure_full_user($user);

        $daysleft = [
            'd30' => 30,
            'd7'  => 7,
            'd1'  => 1,
        ][$remindkey] ?? 7;

        $planname = local_subscriptions_plan_display_name($plan);
        $enddate  = userdate((int)$sub->end_date);

        // Titre & corps
        $title = get_string('expiry_reminder_subject', 'local_subscriptions', $daysleft);

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

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('renew_now', 'local_subscriptions'), 
            (UrlFactory::subscribe((int)$sub->planid))->out(false)
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

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body,  
            get_string('view_my_subscriptions', 'local_subscriptions'), 
            (UrlFactory::my_subscriptions())->out(false)
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

        [$html, $text] = MailRenderer::layout(
            $title, 
            $body, 
            get_string('expired_button_renew', 'local_subscriptions'), 
            (UrlFactory::subscribe((int)$sub->planid))->out(false)
        );

        self::deliver($user, $title, $html, $text);
    }

    private static function send_upgrade_confirmation(\stdClass $user, \stdClass $plan, \stdClass $pr, \stdClass $sub): void {
        $user = self::ensure_full_user($user);
        $planname = local_subscriptions_plan_display_name($plan);
        $title = get_string('upgrade_confirmed_subject', 'local_subscriptions', format_string($planname));

        // Montant payé : préférer la sub, sinon PR (et ne diviser par 100 que si besoin)
        $paid = '';
        $amt  = null;
        $cur  = null;

        if (!empty($sub->pricepaid)) {
            $amt = (float)$sub->pricepaid;
            $cur = $sub->currency ?? ($pr->currency ?? null);
        } elseif (!empty($pr->price)) {
            $raw = (float)$pr->price;
            // Si c'est du minor (cents) on divise, sinon on garde tel quel.
            $amt = ($raw >= 100) ? round($raw/100, 2) : round($raw, 2);
            $cur = $pr->currency ?? null;
        }

        if ($amt !== null && $cur) {
            $paid = format_float($amt, 2).' '.strtoupper($cur);
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

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('view_my_subscriptions', 'local_subscriptions'),
            (UrlFactory::my_subscriptions())->out(false)
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

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('mail_button_manage', 'local_subscriptions'),
            (UrlFactory::my_subscriptions())->out(false)
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

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('recurring_failed_button', 'local_subscriptions'),
            (UrlFactory::portal())->out(false)
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

        [$html, $text] = MailRenderer::layout(
            $title,
            $body,
            get_string('recurring_canceled_button', 'local_subscriptions'),
            (new \moodle_url('/subscribe.php'))->out(false)
        );

        self::deliver($user, $title, $html, $text);
    }

    private static function send_contact_admin(string $toemail, string $fullname, string $fromemail, string $message, array $meta = []): void {
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

    private static function send_contact_ack(string $toemail, string $fullname, string $message, ?string $fromsupport = null): void {
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
        $to      = (string)$p['toemail'];
        $first   = (string)$p['firstname'];
        $suburl  = (string)$p['subscribe_url'];

        $subject  = get_string('mail_trial_started_subject', 'local_campus');
        $bodyhtml = format_text(
            get_string('mail_trial_started_body', 'local_campus', (object)['firstname'=>$first]),
            FORMAT_HTML
        );
        [$html, $text] = MailRenderer::layout(
            $subject, $bodyhtml,
            get_string('mail_trial_cta_subscribe','local_campus'), $suburl
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
        [$html, $text] = MailRenderer::layout_with_extra_button(
            $subject,
            $bodyhtml,
            get_string('mail_trial_cta_continue','local_campus'), $conturl,  // bouton secondaire (dans le corps)
            get_string('mail_trial_cta_subscribe','local_campus'), $suburl   // bouton principal (celui de layout)
        );

        self::deliver(self::pseudo_user($to, $first, ''), $subject, $html, $text);
    }

    public static function send_trial_expired(array $p): void {
        $to      = (string)$p['toemail'];
        $first   = (string)$p['firstname'];
        $course  = (string)($p['course_fullname'] ?? '');
        $suburl  = (string)$p['subscribe_url'];

        $subject  = get_string('mail_trial_expired_subject_generic','local_campus', $course);
        $bodyhtml = format_text(
            get_string('mail_trial_expired_body','local_campus', (object)['firstname'=>$first]),
            FORMAT_HTML
        );
        [$html, $text] = MailRenderer::layout(
            $subject, $bodyhtml,
            get_string('mail_trial_cta_subscribe','local_campus'), $suburl
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

    /** Format monétaire major units (EUR, etc.). */
    private static function money(float $amount, ?string $cur): string {
        $cur = $cur ? strtoupper($cur) : '';
        return format_float($amount, 2).' '.s($cur);
    }

    /** Envoi robuste (support user, try/catch). */
    public static function deliver(\stdClass $user, string $subject, string $html, string $text): void {

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

        // $user = destinataire principal ; $support = \core_user::get_support_user()
        // $subject, $plain, $html : ton contenu déjà préparé.
        $copy = trim(get_config('local_subscriptions', 'email_copy_to') ?? '');
        if ($copy !== '') {
            $list = preg_split('/[,;\s]+/', $copy, -1, PREG_SPLIT_NO_EMPTY);

            $primaryemail = (string)($user->email ?? '');
            $support = \core_user::get_support_user();              // user réel (id non vide)
            $support = self::ensure_full_user($support);            // ta méthode, pour éviter les champs manquants

            foreach ($list as $addr) {
                $addr = trim($addr);
                if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                if ($primaryemail !== '' && strcasecmp($addr, $primaryemail) === 0) {
                    continue; // pas de doublon vers l'apprenant
                }

                // Clone “safe” depuis le support user, en overridant uniquement l'adresse
                $recipient = clone $support;
                $recipient->email      = $addr;
                $recipient->mailformat = 1;           // texte+HTML
                $recipient->emailstop  = 0;           // par prudence
                $recipient->suspended  = 0;
                $recipient->deleted    = 0;
                $recipient->confirmed  = 1;

                // Envoi de la copie (préfixe optionnel)
                $copysubject = '[COPY] ' . $subject.' - '.$user->username;
                $ok = @email_to_user($recipient, $from, $copysubject, $text, $html);

                // Option: petit log si besoin
                if (!$ok) {
                    debugging('local_subscriptions: admin copy failed to '.$addr, DEBUG_DEVELOPER);
                }
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
            self::send_welcome($user, $tmppassword ?? '', $plan, $pr);
        } else {
            self::send_subscription_update($user, $plan, $pr, $sub);
        }
    }


}
