<?php
// local/subscriptions/cli/mail/send_preview_mail.php
// Envoi d'un e-mail de test pour vérifier SPF/DKIM/DMARC (ex: vers Mail-Tester).
// Usage : php local/subscriptions/cli/mail/send_preview_mail.php --to="xxxx@mail-tester.com" [--lang=fr] [--subject="..."] [--preview]

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

use local_subscriptions\mail\MailRenderer;

// ---------- Parse arguments ----------
list($options, $unrecognized) = cli_get_params([
    'to'      => null,      // adresse destinataire (ex: fournie par mail-tester)
    'lang'    => null,      // ex: fr / en / ru
    'subject' => null,      // subject custom
    'preview' => false,     // ne pas envoyer : affiche le rendu
    'help'    => false,
], [
    'h' => 'help'
]);

if (!empty($options['help']) || empty($options['to'])) {
    $help = "Envoie un e-mail de test HTML+texte via le mailer Moodle (SMTP configuré).
Vérifie la délivrabilité SPF/DKIM/DMARC côté Mail-Tester ou boîte cible.

Options:
  --to=EMAIL         Adresse de destination (ex: fournie par Mail-Tester)
  --lang=fr|en|ru    Force la langue pour les strings/date (facultatif)
  --subject='...'    Sujet personnalisé (facultatif)
  --preview          N'envoie pas, affiche le HTML/texte (facultatif)
  -h, --help         Aide

Exemples :
  php local/subscriptions/cli/mail/send_preview_mail.php --to='xxxx@mail-tester.com'
  php local/subscriptions/cli/mail/send_preview_mail.php --to='xxxx@mail-tester.com' --lang=fr
  php local/subscriptions/cli/mail/send_preview_mail.php --to='xxxx@mail-tester.com' --subject='Test SPF/DKIM CampusFR'
  php local/subscriptions/cli/mail/send_preview_mail.php --to='xxxx@mail-tester.com' --preview

";
    echo $help;
    exit(0);
}

// ---------- Forcer langue si demandée ----------
$oldlang = current_language();
if (!empty($options['lang'])) {
    force_current_language($options['lang']);
}

// ---------- Préparer destinataire ----------
$to = trim($options['to']);
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    cli_error("Adresse invalide : {$to}");
}

$recipient = (object)[
    'id' => -1,
    'email' => $to,
    'firstname' => 'Mail',
    'lastname' => 'Tester',
    'firstnamephonetic' => '',
    'lastnamephonetic' => '',
    'middlename' => '',
    'alternatename' => '',
    'mailformat' => 1,
    'emailstop' => 0,
    'suspended' => 0,
    'deleted' => 0,
    'confirmed' => 1,
];

// ---------- Construire le contenu ----------
$brand = $SITE->fullname ?? 'CampusFR';
$subject = $options['subject'] ?? "[{$brand}] Test SPF/DKIM/DMARC";

$nowhtml = userdate(time(), '%d %B %Y %H:%M');
$body  = html_writer::tag('p', "Ceci est un e-mail de test émis par Moodle pour vérifier la délivrabilité (SPF/DKIM/DMARC).");
$body .= html_writer::tag('p', "Domaine d’expédition : ".s($CFG->wwwroot));
$body .= MailRenderer::open();
$body .= MailRenderer::tr('Sujet', s($subject)); // libellé non i18né exprès ici
$body .= MailRenderer::tr('Date', s($nowhtml), true);
if (!empty($CFG->noreplyaddress)) {
    $body .= MailRenderer::tr('From (noreply)', s($CFG->noreplyaddress), true);
}
if (!empty($CFG->supportemail)) {
    $body .= MailRenderer::tr('Support', s($CFG->supportemail), true);
}
$body .= MailRenderer::tr('Instance', s(parse_url($CFG->wwwroot, PHP_URL_HOST) ?? ''), true);
$body .= MailRenderer::close();

$body .= html_writer::tag('p',
    'Astuce : dans Mail-Tester, vérifie que SPF=pass, DKIM=pass et DMARC=pass. ' .
    'Si "via <host>" apparaît encore côté client, revois SPF/DKIM/DMARC et $CFG->emailfromvia.'
);

// ---------- Mise en page HTML + texte ----------
list($html, $text) = MailRenderer::layout($subject, $body, null, null);

// ---------- Envoi ou preview ----------
if (!empty($options['preview'])) {
    echo "----- SUBJECT -----\n{$subject}\n\n";
    echo "----- TEXT -----\n{$text}\n\n";
    echo "----- HTML -----\n{$html}\n";
} else {
    $from = \core_user::get_support_user(); // nom/adresse définis dans l’admin (ou via $CFG->supportname/email)
    $ok = email_to_user($recipient, $from, $subject, $text, $html);
    if (!$ok) {
        cli_error("Échec d'envoi (vérifie la config SMTP).");
    }
    echo "OK: envoyé à {$to}\n";
}

// ---------- Restaurer langue ----------
force_current_language($oldlang);
