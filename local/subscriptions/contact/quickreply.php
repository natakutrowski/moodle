<?php
declare(strict_types=1);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/formslib.php');

require_login();
$sysctx = context_system::instance();
require_capability('moodle/site:config', $sysctx); // admin strict

// Params
$to   = required_param('to',   PARAM_RAW_TRIMMED);
$name = required_param('name', PARAM_RAW_TRIMMED);
$orig = required_param('orig', PARAM_RAW);
$mid  = optional_param('mid', 0, PARAM_INT);
$h    = required_param('h',    PARAM_ALPHANUMEXT);

// HMAC
$secret  = get_config('local_subscriptions','email_link_secret') ?: ($CFG->passwordsaltmain ?? 'secret');
$payload = http_build_query(['to'=>$to,'name'=>$name,'orig'=>$orig,'mid'=>$mid]);
$check   = hash_hmac('sha256', $payload, $secret);
if (!hash_equals($check, $h)) {
    throw new moodle_exception('invalidaccess', 'error');
}

$PAGE->set_context($sysctx);
$PAGE->set_url(new moodle_url('/local/subscriptions/contact/quickreply.php', ['to'=>$to,'name'=>$name,'orig'=>$orig,'mid'=>$mid,'h'=>$h]));
$PAGE->set_title(get_string('reply_in_admin','local_subscriptions'));
$PAGE->set_heading(get_string('reply_in_admin','local_subscriptions'));

// Langues proposées
$supportedlangs = [
    'fr' => 'Français',
    'en' => 'English',
    'ru' => 'Русский',
];
$deflang = current_language();

// === Form ===
class local_subscriptions_quickreply_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $cd    = $this->_customdata;

        // Choix de langue (change la phrase de salutation à la volée en JS)
        $mform->addElement('select', 'lang', get_string('language'), $cd['langs']);
        $mform->setType('lang', PARAM_ALPHANUMEXT);
        $mform->setDefault('lang', $cd['deflang']);

        // Salutation affichée (statique, mise à jour en JS)
        $greet = get_string('contact_reply_greeting','local_subscriptions', s($cd['name'] ?: ''));
        $mform->addElement('static', 'greet', '', html_writer::tag('p', $greet, ['id'=>'ls-greet']));

        // Éditeur HTML pour la réponse (2 lignes vides par défaut)
        $editoroptions = ['maxfiles'=>0, 'maxbytes'=>0, 'trusttext'=>false, 'context'=>context_system::instance()];
        $mform->addElement('editor', 'reply', get_string('reply_text','local_subscriptions'), null, $editoroptions);
        $mform->setDefault('reply', ['text'=>"", 'format'=>FORMAT_HTML]);

        // Hidden
        foreach (['to','name','orig','mid','h'] as $key) {
            $mform->addElement('hidden', $key, $cd[$key]);
            $mform->setType($key, $key==='mid' ? PARAM_INT : ($key==='h' ? PARAM_ALPHANUMEXT : PARAM_RAW_TRIMMED));
        }

        $this->add_action_buttons(true, get_string('send','block_edly_contact_form'));
    }
}

$mform = new local_subscriptions_quickreply_form(null, [
    'to'=>$to,'name'=>$name,'orig'=>$orig,'mid'=>$mid,'h'=>$h,
    'langs'=>$supportedlangs,'deflang'=>$deflang
]);

$sent = false;
if ($data = $mform->get_data()) {
    // Forcer la langue sélectionnée pour composer le message
    $oldlang = current_language();
    try {
        if (!empty($data->lang) && array_key_exists($data->lang, $supportedlangs)) {
            force_current_language($data->lang);
        }

        $subject = get_string('contact_reply_subject','local_subscriptions');
        $greet   = get_string('contact_reply_greeting','local_subscriptions', s($name ?: ''));
        $remind  = get_string('contact_reply_reminder','local_subscriptions');

        $replyhtml = format_text($data->reply['text'] ?? '', FORMAT_HTML, ['context'=>$sysctx]);
        $orightml  = html_writer::tag('blockquote', html_writer::tag('em', nl2br(s($orig))));

        $bodyhtml  = html_writer::tag('p', $greet) . $replyhtml
                . html_writer::empty_tag('hr', ['style'=>'border:none;border-top:1px solid #eee;margin:16px 0;'])
                . html_writer::tag('p', html_writer::tag('strong', $remind))
                . $orightml;

        [$html, $text] = \local_subscriptions\mail\MailRenderer::layout($subject, $bodyhtml);

        // === FROM = support@… (pris depuis la demande initiale via $mid) ===
        $fromsupport = null;
        if (!empty($mid)) {
            $rec = $DB->get_record('block_edly_contact_msg', ['id' => (int)$mid], 'recipient', IGNORE_MISSING);
            if ($rec && !empty($rec->recipient) && filter_var($rec->recipient, FILTER_VALIDATE_EMAIL)) {
                $fromsupport = trim((string)$rec->recipient);
            }
        }
        // Fallback minimal sur la conf du site si pas de mid/recipient
        if (!$fromsupport) {
            $fromsupport = (string)($CFG->supportemail ?? $CFG->noreplyaddress);
        }

        // Destinataire (utilisateur) et expéditeur override (support)
        $rcpt = \local_subscriptions\mailer::pseudo_user($to, $name ?: '', '');
        $from = \local_subscriptions\mailer::pseudo_from($fromsupport, 'CampusFR support');

        // Envoi: From = support@…, Reply-To = support@…
        \local_subscriptions\mailer::deliver_from($rcpt, $from, $subject, $html, $text, $fromsupport, 'CampusFR support');

        // Journalisation
        $log = (object)[
            'timecreated' => time(),
            'adminid'     => $USER->id,
            'messageid'   => (int)$data->mid,
            'toemail'     => core_text::substr($data->to,   0, 255),
            'toname'      => core_text::substr($data->name, 0, 255),
            'lang'        => core_text::substr($data->lang, 0, 20),
            'subject'     => core_text::substr($subject,    0, 255),
            'bodyhtml'    => $html,
            'bodytext'    => $text,
            'ip'          => core_text::substr(getremoteaddr(), 0, 64),
        ];
        $DB->insert_record('local_subs_contact_reply', $log);

        $sent = true;
    } finally {
        // Toujours restaurer, même si deliver()/DB jettent une exception
        force_current_language($oldlang);
    }
}

echo $OUTPUT->header();

// Message de succès + no form
if ($sent) {
    echo $OUTPUT->notification(get_string('sendsuccess','block_edly_contact_form'), 'notifysuccess');
    echo html_writer::tag('p', get_string('contact_reply_sent_hint','local_subscriptions'));
} else {
    // Formulaire
    echo $OUTPUT->box_start();
    $mform->display();

    // Rappel du message original
    echo html_writer::empty_tag('hr');
    echo html_writer::tag('p', html_writer::tag('strong', get_string('contact_reply_reminder','local_subscriptions')));
    echo html_writer::tag('blockquote', html_writer::tag('em', nl2br(s($orig))));
    echo $OUTPUT->box_end();

    // 1) Construire côté PHP les salutations dans chaque langue, en basculant temporairement la langue
    $oldlang = current_language();
    try {
        $gmap = [];
        foreach (['fr','en','ru'] as $lng) {
            force_current_language($lng);
            $gmap[$lng] = get_string('contact_reply_greeting', 'local_subscriptions', ($name ?: ''));
        }
    } finally {
        force_current_language($oldlang);
    }

    // 2) Encodage sûr pour JS (UNICODE + échappes HTML)
    $mapjson = json_encode(
        $gmap,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );

    // 3) JS robuste : cible id_lang (moodleform) sinon le name=lang ; met à jour #ls-greet
    $PAGE->requires->js_amd_inline("
        (function(){
        var map = {$mapjson};
        var sel = document.getElementById('id_lang') || document.querySelector('select[name=lang]');
        var box = document.getElementById('ls-greet');
        if (!sel || !box) return;

        function update(){
            var v = sel.value || '".addslashes($deflang)."';
            // textContent = pas d'HTML, sûr (on a déjà mis le prénom dans la string côté PHP)
            box.textContent = map[v] || map['".addslashes($deflang)."'];
        }

        sel.addEventListener('change', update);
        // Init immédiat (au cas où le select a déjà une valeur)
        update();
        })();
    ");

}

// tout à la fin, avant $OUTPUT->footer();
register_shutdown_function(function(){
    global $SESSION;
    if (!empty($SESSION->forcelang)) {
        unset($SESSION->forcelang);
    }
});


echo $OUTPUT->footer();
