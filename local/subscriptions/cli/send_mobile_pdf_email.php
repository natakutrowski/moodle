<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

global $DB, $CFG;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'slug' => 'verbes-3e-groupe',
        'email' => '',
        'dry-run' => true,
        'execute' => false,
        'limit' => 0,
    ],
    [
        'h' => 'help',
    ]
);

if ($options['help']) {
    echo "Send mobile PDF link to customers who already paid.\n\n";
    echo "Usage:\n";
    echo "  php local/subscriptions/cli/send_mobile_pdf_email.php --slug=verbed-3e-groupe\n";
    echo "  php local/subscriptions/cli/send_mobile_pdf_email.php --slug=verbes-3e-groupe --execute\n";
    echo "  php local/subscriptions/cli/send_mobile_pdf_email.php --email=test@example.com --execute\n";
    echo "  php local/subscriptions/cli/send_mobile_pdf_email.php --limit=5\n\n";
    echo "Options:\n";
    echo "  --execute       Actually send emails. Without it, dry-run only.\n";
    echo "  --email         Send only to one buyer email.\n";
    echo "  --limit         Limit number of records.\n";
    exit(0);
}

$execute = !empty($options['execute']);
$slug = trim((string)$options['slug']);
$emailfilter = trim((string)$options['email']);
$limit = max(0, (int)$options['limit']);

$product = $DB->get_record('subscription_digital_product', ['slug' => $slug], '*', MUST_EXIST);

if (empty($product->mobile_filename)) {
    cli_error("Product '{$slug}' has no mobile_filename.");
}

$mobilepath = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $product->mobile_filename;

if (!is_readable($mobilepath)) {
    cli_error("Mobile PDF file not readable: {$mobilepath}");
}

$params = [
    'productid' => $product->id,
    'status1' => 'paid',
    'status2' => 'completed',
];

$where = "
    pr.productid = :productid
    AND pr.status IN (:status1, :status2)
    AND pr.download_token IS NOT NULL
    AND pr.download_token <> ''
";

if ($emailfilter !== '') {
    $where .= " AND pr.email = :email";
    $params['email'] = $emailfilter;
}

$sql = "
    SELECT pr.*
      FROM {subscription_digital_payment_request} pr
     WHERE {$where}
  ORDER BY pr.payment_date ASC, pr.id ASC
";

$records = $DB->get_records_sql($sql, $params, 0, $limit ?: 0);

mtrace("Product: {$product->name}");
mtrace("Mobile file: {$product->mobile_filename}");
mtrace($execute ? "MODE: EXECUTE - emails will be sent" : "MODE: DRY-RUN - no email will be sent");
mtrace("Found records: " . count($records));
mtrace(str_repeat('-', 80));

foreach ($records as $pr) {
    $mobileurl = (new moodle_url('/download/pdf/' . $pr->download_token, [
        'version' => 'mobile',
    ]))->out(false);

    $firstname = trim((string)($pr->firstname ?? ''));
    $recipientname = trim($firstname . ' ' . ($pr->lastname ?? ''));

    $user = new stdClass();
    $user->id = -1;
    $user->email = $pr->email;
    $user->username = $pr->email;
    $user->firstname = $firstname;
    $user->lastname = $pr->lastname ?? '';
    $user->firstnamephonetic = '';
    $user->lastnamephonetic = '';
    $user->middlename = '';
    $user->alternatename = '';
    $user->mailformat = 1;
    $user->deleted = 0;
    $user->suspended = 0;
    $user->confirmed = 1;
    $user->auth = 'manual';

    $subject = 'Мобильная версия вашего PDF CampusFR уже доступна';

    // TODO: remplace ce texte par ton texte final.
    $plain = "Bonjour {$firstname} 🙂,\n\n";
    $plain .= "Мы сделали для вашего удобства еще одну версию карточек специально для телефона.\n";
    $plain .= "Таким образом, на экране у вас будет отображаться только один глагол.\n\n";
    $plain .= "Вы можете скачать ее по ссылке ниже ⬇️\n";
    $plain .= "{$mobileurl}\n\n";
    $plain .= "Ната и команда CampusFR\n";

    $html = '<p>Bonjour ' . s($firstname) . ' 🙂,</p>';
    $html .= '<p>Мы сделали для вашего удобства еще одну версию карточек специально для телефона.</p>';
    $html .= '<p>Таким образом, на экране у вас будет отображаться только один глагол.<br></p>';
    $html .= '<p>Вы можете скачать ее по ссылке ниже ⬇️</p>';
    $html .= '<p><a href="' . s($mobileurl) . '">Скачать мобильную версию</a></p>';
    $html .= '<p>Ната и команда Campus<small><sup>FR</sup></small></p>';

    mtrace("#{$pr->id} {$pr->email} -> {$mobileurl}");

    if ($execute) {
        $ok = email_to_user($user, core_user::get_support_user(), $subject, $plain, $html);

        if ($ok) {
            mtrace("  SENT ✅");
        } else {
            mtrace("  FAILED ❌");
        }
    }
}

mtrace(str_repeat('-', 80));
mtrace("Done.");