<?php
namespace local_subscriptions\mail;

use html_writer;
use local_subscriptions\payment\Provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Aide à composer des tableaux récap pour e-mails (simples et robustes).
 * Usage:
 *   $tbl = MailRenderer::table()
 *            ->plan($planname)
 *            ->amount($price)
 *            ->period_ts($sub->start_date, $sub->end_date)
 *            ->provider($pr->payment_provider ?? '')
 *            ->txid($pr->transactionid ?? null)
 *            ->row_str('receipt_invoice', $invoiceid, true)
 *            ->render();
 */
class MailRenderer {

    /** Builder fluide. */
    public static function table(): MailTableBuilder {
        return new MailTableBuilder();
    }

    /** Ligne simple <tr><td>label</td><td>value</td></tr> (valeur déjà html-échappée si besoin). */
    public static function tr(string $label, string $valuehtml, bool $lined = false): string {
        $tdstyle = 'padding:4px 8px;color:#6b7280;vertical-align:top;';
        $td2style = 'padding:4px 8px;vertical-align:top;';
        if ($lined) {
            $tdstyle  .= 'border-top:1px solid #eee;';
            $td2style .= 'border-top:1px solid #eee;';
        }
        return '<tr><td style="'.$tdstyle.'">'.$label.'</td>'
            . '<td style="'.$td2style.'">'.$valuehtml.'</td></tr>';
    }

    /** Rend un <code>…</code> pour afficher une valeur technique. */
    public static function code(string $text): string {
        return '<code style="background:#f3f4f6;padding:2px 6px;border-radius:6px;'
            . 'user-select:all;-webkit-user-select:all;cursor:text;">'
            . s($text) . '</code>';
    }


    /** Ouvre/ferme le tableau (style e-mail safe). */
    public static function open(): string {
        return html_writer::start_tag('table', [
            'role' => 'presentation', 'cellspacing' => '0', 'cellpadding' => '0', 'border' => '0',
            'style' => 'margin:12px 0;font-size:14px;'
        ]);
    }
    public static function close(): string {
        return html_writer::end_tag('table');
    }

    public static function period_two_lines(?int $start, ?int $end, ?string $fmt = null): string {
        // format court : ex. "11/10/25, 15:19" (selon le pack de langue)
        $fmt = $fmt ?: get_string('strftimedatetimeshort','langconfig');

        $s = $start ? userdate((int)$start, $fmt) : '—';
        $e = $end   ? userdate((int)$end,   $fmt) : '—';

        // ligne 1 : START + flèche ; ligne 2 : END
        // (on garde <code> pour chaque ligne)
        return self::code($s.' →').'<br>'.self::code($e);
    }


    public static function layout(string $title, string $bodyhtml, ?string $buttonlabel = null, ?string $buttonurl = null, array $options = []): array {
        global $CFG;
        global $SITE;

        // ── Branding configurable (fallbacks sûrs) ──────────────────────────────────
        $brandname      = $SITE->fullname;
        $brandnameHTML  = 'Campus<small><sup>FR</sup></small>';   // utilisé *uniquement* dans le HTML
        $brandPlain     = strip_tags(html_entity_decode($brandnameHTML, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $brandcolor     = get_config('local_subscriptions', 'brand_color') ?: '#005f73';
        $brandcolorDark = get_config('local_subscriptions', 'brand_color_dark') ?: '#013140';
        $logo           = trim((string)($options['headerimageurl'] ?? '')) ?: (get_config('local_subscriptions', 'brand_logo_url') ?: '');
        $preheader      = trim((string)($options['preheader'] ?? ''));
        $buttonvariant  = strtolower(trim((string)($options['buttonvariant'] ?? 'standard')));
        $buttonicon     = strtolower(trim((string)($options['buttonicon'] ?? '')));
        $afterbuttonhtml = trim((string)($options['afterbuttonhtml'] ?? ''));
        // Optional email-specific progressive-enhancement CSS, injected in <head>.
        $headcss = trim((string)($options['headcss'] ?? ''));

        // ── Bouton (HTML) ───────────────────────────────────────────────────────────
        $btn = '';
        if ($buttonlabel && $buttonurl) {
            if ($buttonvariant === 'premium') {
                // Email-client-safe premium CTA: gold outer frame + dark CampusFR core.
                // The premium effect does not depend on gradients or hover support.
                $btn = '
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:28px auto;">
                <tr>
                    <td bgcolor="#d7b65a" style="padding:2px;border-radius:13px;background:#d7b65a;box-shadow:0 10px 24px rgba(90,62,10,.20);">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td bgcolor="#fff7df" style="border-radius:11px;background:#fff7df;">
                                <a href="'.s($buttonurl).'" target="_blank" rel="noopener noreferrer"
                                    style="display:inline-block;padding:14px 28px;color:#624817;text-decoration:none;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;line-height:1.2;border-radius:11px;background:#fff7df;letter-spacing:.01em;text-shadow:0 1px 0 rgba(255,255,255,.55);"
                                ><span style="color:#b08424;">✦</span>&nbsp; '.s($buttonlabel).' &nbsp;<span style="color:#b08424;">✦</span></a>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>';
            } else {
                $btn = '
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:26px auto;">
                <tr>
                    <td bgcolor="#d91f73" style="padding:1px;border-radius:12px;background:#d91f73;box-shadow:0 9px 22px rgba(247,37,133,.20);">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td bgcolor="#f72585" style="border-radius:11px;background:#f72585;">
                                <a href="'.s($buttonurl).'" target="_blank" rel="noopener noreferrer"
                                    style="display:inline-block;padding:14px 26px;color:#ffffff;text-decoration:none;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;line-height:1.2;border-radius:11px;background:#f72585;letter-spacing:.01em;"
                                >'.($buttonicon === 'key'
                                    ? '<img src="'.s(rtrim((string)$CFG->wwwroot, '/') . '/local/subscriptions/pix/email/key-white.png').'" alt="" width="14" height="14" style="display:inline-block;width:14px;height:14px;vertical-align:-2px;margin-right:8px;border:0;">'
                                    : ($buttonicon === 'receipt'
                                        ? '<img src="'.s(rtrim((string)$CFG->wwwroot, '/') . '/local/subscriptions/pix/email/receipt-white.png').'" alt="" width="14" height="14" style="display:inline-block;width:14px;height:14px;vertical-align:-2px;margin-right:8px;border:0;">'
                                        : '')).s($buttonlabel).($buttonicon === 'external'
                                    ? ' &nbsp;<img src="'.s(rtrim((string)$CFG->wwwroot, '/') . '/local/subscriptions/pix/email/external-white.png').'" alt="" width="12" height="12" style="display:inline-block;width:12px;height:12px;vertical-align:-1px;border:0;">'
                                    : (in_array($buttonicon, ['key', 'receipt'], true)
                                        ? ''
                                        : ' &nbsp;<span style="font-size:12px;color:#ffd5e8;">›</span>')).'</a>
                            </td>
                        </tr>
                        </table>
                    </td>
                </tr>
                </table>';
            }
        }

        // ── En-tête bandeau / marque ────────────────────────────────────────────────
        if ($logo) {
            // Bandeau image pleine largeur (hauteur limitée)
            $logoHtml = '<img src="'.s($logo).'"
                            alt="'.s($brandPlain).'"
                            class="ls-header-image" style="display:block;width:100%;max-height:220px;object-fit:cover;border:0;outline:none;text-decoration:none;">';
        } else {
            // Fallback texte "CampusFR" avec FR en exposant
            $logoHtml = '<div style="font-size:20px;font-weight:600;color:#111;">'.$brandnameHTML.'</div>';
        }

        // Date pour le footer (texte + HTML)
        $datestr = userdate(time(), get_string('strftimedate', 'langconfig'));

        // ── i18n: pied de page et disclaimer ────────────────────────────────────────
        $year = (int)date('Y');
        $copyright = get_string(
            'email_footer_copyright',
            'local_subscriptions',
            (object)['year' => $year, 'brand' => $brandnameHTML]
        );
        $unexpected = get_string('email_footer_unexpected', 'local_subscriptions');

        // Note personnalisée admin éventuelle
        $footernote = (string)(get_config('local_subscriptions', 'email_footer_note') ?: '');

        // ── HTML (avec dark-mode) ───────────────────────────────────────────────────
        $html = '<!doctype html><html><head><meta charset="utf-8">
                    <meta name="x-apple-disable-message-reformatting">
                    <meta name="viewport" content="width=device-width,initial-scale=1">
                    <meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark">
                    <style>
                    html, body, table, td, div, p, a, h1, h2, h3, h4, h5, h6 {
                        font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;
                    }
                    @media only screen and (max-width:600px) {
                        .ls-shell { width:100% !important; max-width:100% !important; }
                        .ls-body { padding:20px 16px 6px 16px !important; }
                        .ls-footer { padding:14px 16px 18px 16px !important; }
                        .ls-footer-table,
                        .ls-footer-table tbody,
                        .ls-footer-table tr,
                        .ls-footer-table td {
                            display:block !important;
                            width:100% !important;
                        }
                        .ls-footer-date { text-align:left !important; padding-top:8px !important; }
                        .ls-header-image { max-height:none !important; height:auto !important; }
                        .po-desktop { display:none !important; mso-hide:all !important; }
                        .po-mobile { display:table-row !important; mso-hide:none !important; }
                        .po-mobile table { max-width:100% !important; }
                        .po-mobile img { max-width:100% !important; }
                    }
                    @media (prefers-color-scheme: dark) {
                        body { background:#0b1220 !important; }
                        .ls-card { background:#111827 !important; box-shadow:none !important; }
                        .ls-border { border-color:#1f2937 !important; }
                        .ls-text { color:#e5e7eb !important; }
                        .ls-muted { color:#9ca3af !important; }
                    }
                    '.$headcss.'
                    </style>
                    </head>
                    <body style="margin:0;padding:0;background:#f6f9fc;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;">
                    '.($preheader !== '' ? '<div style="display:none!important;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'.s($preheader).'</div>' : '').'
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr><td align="center" style="padding:24px 12px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="800"
                            class="ls-card ls-shell"
                            style="max-width:800px;background:#ffffff;border:1px solid #e6e1ec;border-radius:16px;overflow:hidden;box-shadow:0 10px 32px rgba(56,35,93,0.09);">

                        <!-- En-tête : bandeau image ou texte de marque -->
                        <tr>
                            <td style="padding:0;background:#ffffff;border-bottom:1px solid #eee;" class="ls-border">
                                '.$logoHtml.'
                            </td>
                        </tr>

                        <!-- Corps -->
                        <tr>
                            <td class="ls-body" style="padding:24px 24px 8px 24px;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;">
                            <div style="font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;font-size:14px;line-height:1.7;color:#374151;" class="ls-text">'.$bodyhtml.'</div>
                            '.$btn.'
                            '.$afterbuttonhtml.'
                            </td>
                        </tr>

                        <!-- Footer : copyright + date à droite -->
                        <tr>
                            <td style="padding:16px 24px 20px 24px;border-top:1px solid #eee;" class="ls-border ls-footer">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="ls-footer-table">
                                    <tr>
                                        <td style="font-size:12px;color:#6b7280;vertical-align:top;" class="ls-muted">
                                            '.$copyright.
                                            ($footernote ? '<div style="margin-top:4px">'.format_text($footernote, FORMAT_HTML).'</div>' : '').
                                        '</td>
                                        <td style="font-size:12px;color:#6b7280;text-align:right;white-space:nowrap;vertical-align:top;" class="ls-muted ls-footer-date">
                                            '.s($datestr).'
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        </table>
                        <div style="font-size:11px;color:#94a3b8;margin-top:10px;" class="ls-muted">'.s($unexpected).'</div>
                    </td></tr>
                    </table>
                    </body></html>';

        // ── TEXTE (fallback) ────────────────────────────────────────────────────────
        $btnline = ($buttonlabel && $buttonurl) ? ("\n\n".$buttonlabel.": ".$buttonurl) : '';

        // 1) Titre en texte brut
        $plainTitle = strip_tags(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        // 2) Corps
        $plainBody = html_entity_decode($bodyhtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainBody = preg_replace('/<(br|\/p|\/li)\s*\/?>/i', "\n", $plainBody);
        $plainBody = strip_tags($plainBody);

        // 3) Copyright / unexpected / date
        $plainCopyright  = strip_tags(html_entity_decode($copyright, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plainUnexpected = strip_tags(html_entity_decode($unexpected, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plainDate       = strip_tags(html_entity_decode($datestr, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $text = $plainTitle . "\n\n"
            . $plainBody
            . $btnline . "\n\n"
            . $plainCopyright . ' - ' . $plainDate . "\n"
            . $plainUnexpected;

        return [$html, trim($text)];
    }


    public static function layout_with_extra_button(
        string $title,
        string $bodyhtml,
        string $secondaryLabel, string $secondaryUrl,   // bouton 1 (dans le corps)
        ?string $primaryLabel = null, ?string $primaryUrl = null // bouton principal (celui de layout)
    ): array {
        // Reprend la même couleur que layout() (fallback garanti)
        $brandcolor = get_config('local_subscriptions', 'brand_color') ?: '#005f73';

        // Bouton secondaire “outline” inséré dans le corps avant l’appel à layout()
        $btn2 = '
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:18px auto;">
        <tr>
            <td style="border-radius:8px;border:2px solid '.s($brandcolor).';">
            <a href="'.s($secondaryUrl).'"
                style="display:inline-block;padding:12px 20px;color:'.s($brandcolor).';text-decoration:none;font-weight:600;border-radius:8px;background:#ffffff;">
                '.s($secondaryLabel).'
            </a>
            </td>
        </tr>
        </table>';

        $bodyhtml2 = $bodyhtml . $btn2;

        // On délègue tout le reste à layout() (CTA principal)
        return self::layout($title, $bodyhtml2, $primaryLabel, $primaryUrl);
    }


}

/** Builder fluide pour composer un tableau sans répéter du HTML. */
class MailTableBuilder {
    private array $rows = [];

    private bool $lined = false;
    public function lined(bool $lined = true): self { $this->lined = $lined; return $this; }

    public function row(string $langkey, string $valueHtml): self {
        $label = get_string($langkey, 'local_subscriptions');
        $lined = $this->lined && !empty($this->rows); // applique dès la 2e ligne
        $this->rows[] = MailRenderer::tr($label, $valueHtml, $lined);
        return $this;
    }

    /** Ajoute une ligne si $value non vide (texte simple). */
    public function row_if(string $langkey, ?string $value): self {
        if ($value !== null && $value !== '') {
            $this->row($langkey, s($value));
        }
        return $this;
    }

    /** Ajoute une ligne avec valeur encodée dans <code>. */
    public function row_code(string $langkey, string $raw): self {
        return $this->row($langkey, MailRenderer::code($raw));
    }

    /** Plan (texte simple, passe déjà par format_string côté appel). */
    public function plan(string $planname): self {
        return $this->row('receipt_plan', s($planname));
    }

    /** Montant (ex: "19.90 EUR"). */
    public function amount(string $price): self {
        return $this->row('receipt_amount', s($price));
    }

    /** Période à partir d’un libellé prêt (ex: "1 jan → 31 déc"). */
    public function period_str(string $period): self {
        return $this->row('receipt_period', MailRenderer::code($period));
    }

    /** Période depuis timestamps. */
    public function period_ts(?int $start, ?int $end): self {
        $s = $start ? userdate((int)$start) : '—';
        $e = $end   ? userdate((int)$end)   : '—';
        return $this->period_str($s.' → '.$e);
    }

    /** Provider (email safe : emoji/texte). N’ajoute la ligne que si code non vide. */
    public function provider(?string $code): self {
        $code = (string)$code;
        if ($code === '') { return $this; }
        $label = Provider::label_with_icon($code, 'email');
        $this->rows[] = MailRenderer::tr(
            get_string('subfield_provider','local_subscriptions').':', 
            $label,
            $this->lined && !empty($this->rows)
        );
        return $this;
    }

    /** Transaction ID (ligne affichée uniquement si non vide). */
    public function txid(?string $tx): self {
        $tx = trim((string)$tx);
        if ($tx !== '') {
            $this->rows[] = MailRenderer::tr(
                get_string('receipt_tx','local_subscriptions'), 
                MailRenderer::code($tx),
                $this->lined && !empty($this->rows)
        );
        }
        return $this;
    }

    public function period_ts_short_2l(?int $start, ?int $end, ?string $fmt = null): self {
        $value = MailRenderer::period_two_lines($start, $end, $fmt);
        $this->rows[] = MailRenderer::tr(
            get_string('receipt_period','local_subscriptions'),
            $value,
            $this->lined && !empty($this->rows)
        );
        return $this;
    }


    /** Rendu final du tableau complet. */
    public function render(): string {
        if (!$this->rows) { return ''; }
        return MailRenderer::open() . implode('', $this->rows) . MailRenderer::close();
    }
}
