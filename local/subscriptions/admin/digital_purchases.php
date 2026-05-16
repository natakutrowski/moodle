<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$download = optional_param('download', 0, PARAM_BOOL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/digital_purchases.php'));
$PAGE->set_title(get_string('digital_purchases_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_purchases_title', 'local_subscriptions'));

$lang = strtolower(substr(current_language(), 0, 2));

$sql = "
    SELECT
        pr.id,
        pr.productid,
        COALESCE(NULLIF(tcur.title, ''), NULLIF(tfr.title, ''), p.name) AS productname,
        p.slug,
        p.filename,

        pr.firstname,
        pr.lastname,
        pr.email,
        pr.buyer_lang,

        pr.price,
        pr.currency,
        pr.payment_provider,
        pr.status,
        pr.transactionid,
        pr.sessionid,

        pr.emailsent,
        pr.receipt_sent,

        pr.creation_date,
        pr.payment_date,
        pr.last_update,

        pr.download_token,
        pr.download_token_expires
    FROM {subscription_digital_payment_request} pr
    JOIN {subscription_digital_product} p ON p.id = pr.productid
    LEFT JOIN {subscription_digital_product_lang} tcur
        ON tcur.productid = p.id
        AND tcur.lang = :lang
    LEFT JOIN {subscription_digital_product_lang} tfr
        ON tfr.productid = p.id
        AND tfr.lang = 'fr'
    ORDER BY pr.creation_date DESC, pr.id DESC
";

$records = $DB->get_records_sql($sql, ['lang' => $lang]);

if ($download) {
    $filename = 'achats_pdf_campusfr_' . date('Y-m-d_H-i') . '.xlsx';

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = $workbook->add_worksheet('Achats PDF');

    $headerformat = $workbook->add_format([
        'bold' => 1,
        'bg_color' => '#D9EAF7',
    ]);

    $moneyformat = $workbook->add_format([
        'num_format' => '#,##0.00',
    ]);

    $headers = [
        'ID',
        'Produit',
        'Slug',
        'Fichier',
        'Prénom',
        'Nom',
        'Email',
        'Langue',
        'Prix',
        'Devise',
        'Provider',
        'Statut',
        'Transaction ID',
        'Session ID',
        'Email PDF envoyé',
        'Reçu envoyé',
        'Date création',
        'Date paiement',
        'Dernière mise à jour',
        'Expiration lien',
        'Lien téléchargement',
    ];

    foreach ($headers as $col => $header) {
        $worksheet->write_string(0, $col, $header, $headerformat);
    }

    $row = 1;

    foreach ($records as $r) {
        $downloadurl = '';
        if (!empty($r->download_token)) {
            $downloadurl = (new moodle_url('/download/pdf/' . $r->download_token))->out(false);
        }

        $worksheet->write_number($row, 0, (int)$r->id);
        $worksheet->write_string($row, 1, $r->productname ?? '');
        $worksheet->write_string($row, 2, $r->slug ?? '');
        $worksheet->write_string($row, 3, $r->filename ?? '');
        $worksheet->write_string($row, 4, $r->firstname ?? '');
        $worksheet->write_string($row, 5, $r->lastname ?? '');
        $worksheet->write_string($row, 6, $r->email ?? '');
        $worksheet->write_string($row, 7, $r->buyer_lang ?? '');

        if ($r->price !== null) {
            $worksheet->write_number($row, 8, (float)$r->price, $moneyformat);
        } else {
            $worksheet->write_string($row, 8, '');
        }

        $worksheet->write_string($row, 9, $r->currency ?? '');
        $worksheet->write_string($row, 10, $r->payment_provider ?? '');
        $worksheet->write_string($row, 11, $r->status ?? '');
        $worksheet->write_string($row, 12, $r->transactionid ?? '');
        $worksheet->write_string($row, 13, $r->sessionid ?? '');
        $worksheet->write_string($row, 14, !empty($r->emailsent) ? 'Oui' : 'Non');
        $worksheet->write_string($row, 15, !empty($r->receipt_sent) ? 'Oui' : 'Non');
        $worksheet->write_string($row, 16, !empty($r->creation_date) ? userdate((int)$r->creation_date, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 17, !empty($r->payment_date) ? userdate((int)$r->payment_date, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 18, !empty($r->last_update) ? userdate((int)$r->last_update, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 19, !empty($r->download_token_expires) ? userdate((int)$r->download_token_expires, '%d/%m/%Y %H:%M') : 'Sans expiration');
        $worksheet->write_string($row, 20, $downloadurl);

        $row++;
    }

    $widths = [8, 35, 28, 35, 18, 18, 35, 10, 12, 10, 14, 14, 35, 35, 16, 14, 22, 22, 22, 22, 80];

    foreach ($widths as $col => $width) {
        $worksheet->set_column($col, $col, $width);
    }

    $workbook->close();
    exit;
}

echo $OUTPUT->header();

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/digital_purchases.php', ['download' => 1]),
        get_string('digital_purchases_export_xlsx', 'local_subscriptions'),
        ['class' => 'btn btn-primary']
    ),
    'mb-4'
);

echo html_writer::tag('p',
    get_string('digital_purchases_count', 'local_subscriptions', count($records)),
    ['class' => 'text-muted']
);

$table = new html_table();
$table->head = [
    'ID',
    get_string('digital_success_product', 'local_subscriptions'),
    get_string('digital_success_email', 'local_subscriptions'),
    get_string('digital_success_amount', 'local_subscriptions'),
    get_string('digital_success_provider', 'local_subscriptions'),
    get_string('digital_success_status', 'local_subscriptions'),
    get_string('digital_purchases_payment_date', 'local_subscriptions'),
    get_string('digital_purchases_emails', 'local_subscriptions'),
    get_string('digital_success_download', 'local_subscriptions'),
];

$table->attributes['class'] = 'generaltable table table-striped';

foreach ($records as $r) {
    $downloadlink = '—';

    if (!empty($r->download_token) && in_array($r->status, ['paid', 'completed'], true)) {
        $downloadlink = html_writer::link(
            new moodle_url('/download/pdf/' . $r->download_token),
            get_string('digital_success_download', 'local_subscriptions'),
            ['target' => '_blank']
        );
    }

    $emails = [];
    $emails[] = get_string('digital_purchases_access_email_short', 'local_subscriptions') . ': ' . (!empty($r->emailsent) ? '✅' : '—');
    $emails[] = get_string('digital_purchases_receipt_email_short', 'local_subscriptions') . ': ' . (!empty($r->receipt_sent) ? '✅' : '—');

    $table->data[] = [
        (int)$r->id,
        s($r->productname ?? ''),
        s(trim(($r->firstname ?? '') . ' ' . ($r->lastname ?? ''))) . html_writer::empty_tag('br') . s($r->email ?? ''),
        number_format((float)$r->price, 2, ',', ' ') . ' ' . s($r->currency ?? ''),
        s($r->payment_provider ?? ''),
        s($r->status ?? ''),
        !empty($r->payment_date) ? userdate((int)$r->payment_date, '%d/%m/%Y %H:%M') : '—',
        implode(html_writer::empty_tag('br'), $emails),
        $downloadlink,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();