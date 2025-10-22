<?php
// blocks/edly_contact_form/ajax.php
define('AJAX_SCRIPT', true);
require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->libdir.'/dml/moodle_database.php');

$PAGE->set_context(\context_system::instance());                 // ✅ important
$PAGE->set_url(new \moodle_url('/blocks/edly_contact_form/ajax.php'));

header('Content-Type: application/json; charset=utf-8');

$blockid  = required_param('blockid',  PARAM_INT);
$sesskeyv = required_param('sesskey',  PARAM_RAW_TRIMMED);
if (!confirm_sesskey($sesskeyv)) {
    echo json_encode(['ok'=>false,'error'=>'invalidsesskey']); die;
}

// Données utilisateur (HTML non autorisé -> nettoyage strict)
$fullname = clean_param(required_param('ecf_fullname', PARAM_TEXT), PARAM_TEXT);
$email    = required_param('ecf_email', PARAM_RAW_TRIMMED);
$message  = clean_param(required_param('ecf_message', PARAM_RAW_TRIMMED), PARAM_TEXT);
$accept   = required_param('ecf_accept', PARAM_BOOL);

if (!validate_email($email)) {
    echo json_encode(['ok'=>false,'error'=>'invalidemail']); die;
}
if (!$accept) {
    echo json_encode(['ok'=>false,'error'=>'mustaccept']); die;
}

// --- Récup config du bloc (résout aussi __PHP_Incomplete_Class) ---
$bi = $DB->get_record('block_instances', ['id' => $blockid], 'id, configdata', MUST_EXIST);
$raw = base64_decode($bi->configdata, true);

// NB: autoriser stdClass/objets simples ; sinon __PHP_Incomplete_Class possible
$config = ($raw !== false) ? @unserialize($raw, ['allowed_classes' => true]) : (object)[];

// -> La façon la plus sûre de lire une config potentiellement "__PHP_Incomplete_Class"
$vars = is_object($config) ? get_object_vars($config) : (array)$config;

// 1) destinataire depuis la config du bloc
$recipient = '';
if (!empty($vars['recipient']) && is_string($vars['recipient'])) {
    $recipient = trim($vars['recipient']);
} else if (!empty($vars['config_recipient']) && is_string($vars['config_recipient'])) {
    // filet legacy si jamais
    $recipient = trim($vars['config_recipient']);
}

// 2) fallback: supportemail site, puis admin
if ($recipient === '') { $recipient = (string)($CFG->supportemail ?? ''); }
if ($recipient === '') { $recipient = get_admin()->email; }


// META techniques
$ip  = getremoteaddr();
$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Enregistrer en BD (table custom du bloc)
$record = (object)[
    'timecreated'     => time(),
    'userid'          => isloggedin() ? $USER->id : 0,
    'blockinstanceid' => $blockid,
    'fullname'        => core_text::substr($fullname, 0, 255),
    'email'           => core_text::substr($email, 0, 255),
    'message'         => core_text::substr($message, 0, 5000),
    'recipient'       => core_text::substr($recipient, 0, 255),
    'ip'              => core_text::substr($ip, 0, 64),
    'useragent'       => core_text::substr($ua, 0, 512),
];
$msgid  = $DB->insert_record('block_edly_contact_msg', $record, true);
$secret = get_config('local_subscriptions','email_link_secret') ?: ($CFG->passwordsaltmain ?? 'secret');

$payload = http_build_query(['to'=>$email,'name'=>$fullname,'orig'=>$message,'mid'=>$msgid]);
$hmac    = hash_hmac('sha256', $payload, $secret);

$replyurl = (new moodle_url('/local/subscriptions/contact/quickreply.php', [
  'to'   => $email,
  'name' => $fullname,
  'orig' => $message,
  'mid'  => $msgid,
  'h'    => $hmac
]))->out(false);

// ========= Envoi emails =========

// === Utiliser le mailer de local_subscriptions si dispo ===
\local_subscriptions\mailer::dispatch(
    \local_subscriptions\mailer::T_CONTACT_ADMIN,
    [
        'toemail'   => $recipient,
        'fullname'  => $fullname,
        'fromemail' => $email,
        'message'   => $message,        
        'meta'      => [
            'ip'        => $ip,
            'useragent' => $ua,
            'replyurl'  => $replyurl,
        ],
        // 'lang'    => 'fr', // (optionnel) forcer une langue
    ]
);
\local_subscriptions\mailer::dispatch(
    \local_subscriptions\mailer::T_CONTACT_ACK,
    [
        'toemail'  => $email,
        'fullname' => $fullname,
        'message'  => $message, 
        'fromsupport'=>$recipient,
    ]
);

// Réponse Ajax
echo json_encode(['ok'=>true,'message'=>get_string('sendsuccess','block_edly_contact_form')]);
