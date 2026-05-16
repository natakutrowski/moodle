<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/clilib.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$download = optional_param('download', 0, PARAM_BOOL);
$checkprovider = optional_param('checkprovider', 0, PARAM_BOOL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/digital_purchases.php', [
    'checkprovider' => $checkprovider,
]));
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
        p.mobile_filename,

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
        pr.download_token_expires,
        pr.last_error
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

$providerstatuses = [];

if ($checkprovider) {
    foreach ($records as $r) {
        try {
            $providerstatuses[$r->id] = local_subscriptions_check_digital_provider_status($r);
        } catch (Throwable $e) {
            $providerstatuses[$r->id] = [
                'status' => 'ERROR',
                'reason' => $e->getMessage(),
            ];
        }
    }
}

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
        'Fichier classique',
        'Fichier mobile',
        'Prénom',
        'Nom',
        'Email',
        'Langue',
        'Prix',
        'Devise',
        'Provider',
        'Statut DB',
        'Statut provider live',
        'Raison provider',
        'Transaction ID',
        'Session ID',
        'Email PDF envoyé',
        'Reçu envoyé',
        'Date création',
        'Date paiement',
        'Dernière mise à jour',
        'Expiration lien',
        'Lien téléchargement classique',
        'Lien téléchargement mobile',
        'Dernière erreur DB',
    ];

    foreach ($headers as $col => $header) {
        $worksheet->write_string(0, $col, $header, $headerformat);
    }

    $row = 1;

    foreach ($records as $r) {
        $downloadurl = '';
        $downloadurlmobile = '';

        if (!empty($r->download_token)) {
            $downloadurl = (new moodle_url('/download/pdf/' . $r->download_token))->out(false);

            if (!empty($r->mobile_filename)) {
                $downloadurlmobile = (new moodle_url('/download/pdf/' . $r->download_token, [
                    'version' => 'mobile',
                ]))->out(false);
            }
        }

        $providerstatus = $providerstatuses[$r->id] ?? [
            'status' => $checkprovider ? 'UNKNOWN' : '',
            'reason' => '',
        ];

        $worksheet->write_number($row, 0, (int)$r->id);
        $worksheet->write_string($row, 1, $r->productname ?? '');
        $worksheet->write_string($row, 2, $r->slug ?? '');
        $worksheet->write_string($row, 3, $r->filename ?? '');
        $worksheet->write_string($row, 4, $r->mobile_filename ?? '');
        $worksheet->write_string($row, 5, $r->firstname ?? '');
        $worksheet->write_string($row, 6, $r->lastname ?? '');
        $worksheet->write_string($row, 7, $r->email ?? '');
        $worksheet->write_string($row, 8, $r->buyer_lang ?? '');

        if ($r->price !== null) {
            $worksheet->write_number($row, 9, (float)$r->price, $moneyformat);
        } else {
            $worksheet->write_string($row, 9, '');
        }

        $worksheet->write_string($row, 10, $r->currency ?? '');
        $worksheet->write_string($row, 11, $r->payment_provider ?? '');
        $worksheet->write_string($row, 12, $r->status ?? '');
        $worksheet->write_string($row, 13, $providerstatus['status'] ?? '');
        $worksheet->write_string($row, 14, $providerstatus['reason'] ?? '');
        $worksheet->write_string($row, 15, $r->transactionid ?? '');
        $worksheet->write_string($row, 16, $r->sessionid ?? '');
        $worksheet->write_string($row, 17, !empty($r->emailsent) ? 'Oui' : 'Non');
        $worksheet->write_string($row, 18, !empty($r->receipt_sent) ? 'Oui' : 'Non');
        $worksheet->write_string($row, 19, !empty($r->creation_date) ? userdate((int)$r->creation_date, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 20, !empty($r->payment_date) ? userdate((int)$r->payment_date, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 21, !empty($r->last_update) ? userdate((int)$r->last_update, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 22, !empty($r->download_token_expires) ? userdate((int)$r->download_token_expires, '%d/%m/%Y %H:%M') : 'Sans expiration');
        $worksheet->write_string($row, 23, $downloadurl);
        $worksheet->write_string($row, 24, $downloadurlmobile);
        $worksheet->write_string($row, 25, $r->last_error ?? '');

        $row++;
    }

    $widths = [
        8, 35, 28, 35, 35, 18, 18, 35, 10, 12, 10, 14, 14,
        18, 50, 35, 35, 16, 14, 22, 22, 22, 22, 80, 80, 60,
    ];

    foreach ($widths as $col => $width) {
        $worksheet->set_column($col, $col, $width);
    }

    $workbook->close();
    exit;
}

echo $OUTPUT->header();

echo html_writer::start_div('mb-4 d-flex gap-2 flex-wrap');

echo html_writer::link(
    new moodle_url('/local/subscriptions/admin/digital_purchases.php', [
        'download' => 1,
        'checkprovider' => $checkprovider,
    ]),
    get_string('digital_purchases_export_xlsx', 'local_subscriptions'),
    ['class' => 'btn btn-primary']
);

if ($checkprovider) {
    echo html_writer::link(
        new moodle_url('/local/subscriptions/admin/digital_purchases.php'),
        'Masquer les statuts provider live',
        ['class' => 'btn btn-outline-secondary']
    );
} else {
    echo html_writer::link(
        new moodle_url('/local/subscriptions/admin/digital_purchases.php', ['checkprovider' => 1]),
        'Vérifier les statuts provider live',
        ['class' => 'btn btn-outline-primary']
    );
}

echo html_writer::end_div();

if ($checkprovider) {
    echo $OUTPUT->notification(
        'Les statuts provider live sont vérifiés en lecture seule. Aucune modification en base et aucun email ne sont envoyés.',
        'info'
    );
}

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
    'Statut DB',
    'Statut provider',
    'Raison / détail provider',
    get_string('digital_purchases_payment_date', 'local_subscriptions'),
    get_string('digital_purchases_emails', 'local_subscriptions'),
    get_string('digital_success_download', 'local_subscriptions'),
];

$table->attributes['class'] = 'generaltable table table-striped';

foreach ($records as $r) {
    $downloadlink = '—';

    if (!empty($r->download_token) && in_array($r->status, ['paid', 'completed'], true)) {
        $links = [];

        $links[] = html_writer::link(
            new moodle_url('/download/pdf/' . $r->download_token),
            'Classique',
            ['target' => '_blank']
        );

        if (!empty($r->mobile_filename)) {
            $links[] = html_writer::link(
                new moodle_url('/download/pdf/' . $r->download_token, ['version' => 'mobile']),
                'Mobile',
                ['target' => '_blank']
            );
        }

        $downloadlink = implode(html_writer::empty_tag('br'), $links);
    }

    $emails = [];
    $emails[] = get_string('digital_purchases_access_email_short', 'local_subscriptions') . ': ' . (!empty($r->emailsent) ? '✅' : '—');
    $emails[] = get_string('digital_purchases_receipt_email_short', 'local_subscriptions') . ': ' . (!empty($r->receipt_sent) ? '✅' : '—');

    $providerstatus = $providerstatuses[$r->id] ?? [
        'status' => $checkprovider ? 'UNKNOWN' : '—',
        'reason' => '',
    ];

    $statusbadge = local_subscriptions_render_provider_status_badge($providerstatus['status']);

    $table->data[] = [
        (int)$r->id,
        s($r->productname ?? ''),
        s(trim(($r->firstname ?? '') . ' ' . ($r->lastname ?? ''))) . html_writer::empty_tag('br') . s($r->email ?? ''),
        number_format((float)$r->price, 2, ',', ' ') . ' ' . s($r->currency ?? ''),
        s($r->payment_provider ?? ''),
        s($r->status ?? ''),
        $statusbadge,
        s($providerstatus['reason'] ?? ''),
        !empty($r->payment_date) ? userdate((int)$r->payment_date, '%d/%m/%Y %H:%M') : '—',
        implode(html_writer::empty_tag('br'), $emails),
        $downloadlink,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();


function local_subscriptions_render_provider_status_badge(string $status): string {
    $status = strtoupper($status);

    $class = 'badge bg-secondary';

    if ($status === 'PAID') {
        $class = 'badge bg-success';
    } else if ($status === 'DECLINED') {
        $class = 'badge bg-danger';
    } else if ($status === 'PENDING') {
        $class = 'badge bg-warning text-dark';
    } else if ($status === 'ERROR') {
        $class = 'badge bg-dark';
    }

    return html_writer::tag('span', s($status), ['class' => $class]);
}


function local_subscriptions_check_digital_provider_status(stdClass $pr): array {
    if (empty($pr->sessionid)) {
        return [
            'status' => 'UNKNOWN',
            'reason' => 'No sessionid/orderId in database.',
        ];
    }

    if ($pr->payment_provider === 'stripe') {
        return local_subscriptions_check_stripe_provider_status($pr);
    }

    if ($pr->payment_provider === 'alfa') {
        return local_subscriptions_check_alfa_provider_status($pr);
    }

    return [
        'status' => 'UNKNOWN',
        'reason' => 'Unsupported provider: ' . ($pr->payment_provider ?? ''),
    ];
}


function local_subscriptions_check_stripe_provider_status(stdClass $pr): array {
    global $CFG;

    $env = get_config('local_subscriptions', 'stripe_env') ?: 'test';
    $env = ($env === 'live') ? 'live' : 'test';

    $secret = get_config('local_subscriptions', "stripe_{$env}_secret") ?: '';

    if ($secret === '') {
        return [
            'status' => 'ERROR',
            'reason' => 'Missing Stripe secret key for env: ' . $env,
        ];
    }

    $autoload = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return [
            'status' => 'ERROR',
            'reason' => 'Stripe SDK autoload not found.',
        ];
    }

    require_once($autoload);

    \Stripe\Stripe::setApiKey($secret);

    $session = \Stripe\Checkout\Session::retrieve($pr->sessionid);

    $paymentstatus = $session->payment_status ?? '';
    $status = $session->status ?? '';

    if ($paymentstatus === 'paid') {
        return [
            'status' => 'PAID',
            'reason' => '',
        ];
    }

    if ($status === 'expired') {
        return [
            'status' => 'DECLINED',
            'reason' => 'Stripe Checkout session expired.',
        ];
    }

    return [
        'status' => 'PENDING',
        'reason' => 'Stripe status: ' . $status . ' / payment_status: ' . $paymentstatus,
    ];
}


function local_subscriptions_check_alfa_provider_status(stdClass $pr): array {
    $env = get_config('local_subscriptions', 'alfa_env') ?: 'test';
    $env = ($env === 'live') ? 'live' : 'test';

    $base = rtrim((string)(get_config('local_subscriptions', "alfa_{$env}_api_base") ?: ''), '/');
    $username = get_config('local_subscriptions', "alfa_{$env}_username") ?: '';
    $password = get_config('local_subscriptions', "alfa_{$env}_password") ?: '';
    $token = get_config('local_subscriptions', "alfa_{$env}_token") ?: '';

    if ($base === '') {
        return [
            'status' => 'ERROR',
            'reason' => 'Missing Alfa API base for env: ' . $env,
        ];
    }

    $payload = [
        'orderId' => $pr->sessionid,
    ];

    if ($token !== '') {
        $payload['token'] = $token;
    } else {
        if ($username !== '') {
            $payload['userName'] = $username;
        }

        if ($password !== '') {
            $payload['password'] = $password;
        }
    }

    $url = $base . '/payment/rest/getOrderStatusExtended.do';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'status' => 'ERROR',
            'reason' => 'CURL error: ' . $err,
        ];
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        parse_str($raw, $data);
    }

    if (!is_array($data)) {
        return [
            'status' => 'ERROR',
            'reason' => "Invalid Alfa response. HTTP {$httpcode}",
        ];
    }

    $orderstatus = isset($data['orderStatus']) ? (int)$data['orderStatus'] : null;

    $reason = $data['actionCodeDescription']
        ?? $data['errorMessage']
        ?? $data['error']
        ?? '';

    if ($orderstatus === 2) {
        return [
            'status' => 'PAID',
            'reason' => '',
        ];
    }

    if ($orderstatus === 6) {
        return [
            'status' => 'DECLINED',
            'reason' => $reason !== '' ? $reason : 'Payment declined.',
        ];
    }

    if ($orderstatus === 0) {
        return [
            'status' => 'PENDING',
            'reason' => $reason !== '' ? $reason : 'Registered but not paid.',
        ];
    }

    return [
        'status' => 'PENDING',
        'reason' => $reason !== '' ? $reason : 'Alfa orderStatus: ' . var_export($orderstatus, true),
    ];
}