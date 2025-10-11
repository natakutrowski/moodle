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


    public static function layout(string $title, string $bodyhtml, ?string $buttonlabel = null, ?string $buttonurl = null): array {
        global $SITE;

        // ── Branding configurable (fallbacks sûrs) ──────────────────────────────────
        $brandname  = $SITE->fullname;
        $brandcolor = get_config('local_subscriptions', 'brand_color') ?: '#005f73';
        $brandcolorDark = get_config('local_subscriptions', 'brand_color_dark') ?: '#013140';
        $logo      = get_config('local_subscriptions', 'brand_logo_url') ?: '';

        // ── Bouton (HTML) ───────────────────────────────────────────────────────────
        $btn = '';
        if ($buttonlabel && $buttonurl) {
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
        }

        // ── En-tête logo / marque ───────────────────────────────────────────────────
        $logoHtml = $logo
            ? '<img src="'.s($logo).'" height="32" alt="'.s($brandname).'" style="display:block;border:0;outline:none;text-decoration:none;">'
            : '<strong style="font-size:16px;color:#111;">'.s($brandname).'</strong>';

        // ── i18n: pied de page et disclaimer ────────────────────────────────────────
        $year = (int)date('Y');
        $copyright = get_string('email_footer_copyright', 'local_subscriptions',
            (object)['year'=>$year, 'brand'=>$brandname]);
        $unexpected = get_string('email_footer_unexpected', 'local_subscriptions');

        // (optionnel) note personnalisée admin
        $footernote = (string)(get_config('local_subscriptions', 'email_footer_note') ?: '');

        // ── HTML (avec dark-mode) ───────────────────────────────────────────────────
        $html = '<!doctype html><html><head><meta charset="utf-8">
                    <meta name="x-apple-disable-message-reformatting">
                    <meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark">
                    <style>
                    @media (prefers-color-scheme: dark) {
                    body { background:#0b1220 !important; }
                    .ls-card { background:#111827 !important; box-shadow:none !important; }
                    .ls-border { border-color:#1f2937 !important; }
                    .ls-text { color:#e5e7eb !important; }
                    .ls-muted { color:#9ca3af !important; }
                    }
                    </style>
                    </head>
                    <body style="margin:0;padding:0;background:#f6f9fc;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr><td align="center" style="padding:24px 12px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="800"
                            class="ls-card"
                            style="max-width:800px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.06);">
                        <tr>
                            <td style="padding:16px 20px;background:#ffffff;border-bottom:1px solid #eee;" class="ls-border">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td style="width:80%;padding:0;vertical-align:middle;">'.$logoHtml.'</td>
                                        <td style="width:20%;padding:0;vertical-align:middle;text-align:right;">
                                        <div style="text-align:right;white-space:nowrap;font-size:12px;color:#6b7280;">'
                                            . userdate(time(), get_string('strftimedate', 'langconfig')) .
                                        '</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:24px 24px 8px 24px;">
                            <h1 style="margin:0 0 12px 0;font-size:20px;line-height:1.4;color:#111111;" class="ls-text">'.s($title).'</h1>
                            <div style="font-size:14px;line-height:1.7;color:#374151;" class="ls-text">'.$bodyhtml.'</div>
                            '.$btn.'
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 24px 20px 24px;border-top:1px solid #eee;" class="ls-border">
                            <div style="font-size:12px;color:#6b7280;" class="ls-muted">'
                                . s($copyright)
                                . ($footernote ? '<div style="margin-top:4px">'.format_text($footernote, FORMAT_HTML).'</div>' : '')
                            . '</div>
                            </td>
                        </tr>
                        </table>
                        <div style="font-size:11px;color:#94a3b8;margin-top:10px;" class="ls-muted">'.s($unexpected).'</div>
                    </td></tr>
                    </table>
                    </body></html>';

        // ── TEXTE (fallback) ────────────────────────────────────────────────────────
        $btnline = ($buttonlabel && $buttonurl) ? ("\n\n".$buttonlabel.": ".$buttonurl) : '';
        $text = $title."\n\n"
            . html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/li)\s*\/?>/i', "\n", $bodyhtml)), ENT_QUOTES, 'UTF-8')
            . $btnline."\n\n".$copyright."\n".$unexpected;

        return [$html, trim($text)];
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
