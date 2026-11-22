<?php
// CampusFR D2.12.1 — PDF 16:9; optional trusted immutable image provider (CRM path unchanged).
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderStoryLayoutPdf {
    public const WIDTH = 320.0;
    public const HEIGHT = 180.0;

    public static function validate_pages(array $pages): void {
        if (!$pages || count($pages) > 60) {
            throw new \moodle_exception('invalidparameter', 'error', '',
                'PDF de travail limité aux histoires de 1 à 60 pages');
        }
        foreach (array_values($pages) as $i => $page) {
            if ((int)$page->pageindex !== $i || !in_array((string)$page->kind,
                ['cover', 'text', 'image', 'mixed', 'back'], true)) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Pages incohérentes');
            }
            $paras = json_decode((string)$page->paragraphsjson, true);
            if (!is_array($paras) || count($paras) > 8) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Texte invalide');
            }
            $count = 0;
            foreach ($paras as $text) {
                if (!is_string($text)) { throw new \moodle_exception('invalidparameter'); }
                $count += \core_text::strlen($text);
            }
            // Fail visibly; never silently truncate the text of a story page.
            $limit = match ((string)$page->kind) {
                'cover', 'back' => 500,
                'mixed', 'image' => 850,
                default => 1250,
            };
            if ($count > $limit) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'Texte trop dense pour un feuillet 16:9 (page ' . ($i + 1) . ')');
            }
        }
    }

    /** The exact bound image, never an arbitrary path or filename from the HTTP request. */
    private static function image(int $bookid, object $page, ?callable $imageprovider = null): ?\stored_file {
        if ($imageprovider !== null) {
            $result = $imageprovider($bookid, $page);
            if ($result !== null && !($result instanceof \stored_file)) {
                throw new \moodle_exception('invalidparameter');
            }
            return $result;
        }
        $fs = get_file_storage();
        $ctx = \context_system::instance();
        $index = (int)$page->pageindex;
        $area = 'pageimage';
        $path = '/p' . $index . '/';
        if (empty($page->imagefile)) {
            if ($index !== 0) { return null; }
            $area = 'cover';
            $path = '/';
        }
        $result = [];
        foreach ($fs->get_area_files((int)$ctx->id, 'local_campusreader', $area, $bookid, 'id ASC', false) as $file) {
            if (!$file->is_directory() && $file->get_filepath() === $path) { $result[] = $file; }
        }
        if (count($result) === 0) {
            if (!empty($page->imagefile)) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Image référencée absente');
            }
            return null;
        }
        if (count($result) !== 1 || !in_array($result[0]->get_mimetype(),
                ['image/jpeg', 'image/png', 'image/webp'], true) ||
                (int)$result[0]->get_filesize() > 8388608 || (int)$result[0]->get_filesize() < 1 ||
                (!empty($page->imagefile) && $result[0]->get_filename() !== (string)$page->imagefile)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Illustration privée incohérente');
        }
        return $result[0];
    }

    private static function draw_image(\pdf $pdf, \stored_file $file, float $x, float $y,
            float $width, float $height): void {
        $content = $file->get_content();
        if (!is_string($content) || $content === '' || strlen($content) > 8388608) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Image illisible');
        }
        $info = @getimagesizefromstring($content);
        if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 18000000) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Dimensions image non conformes');
        }
        $mime = $file->get_mimetype();
        if ($mime === 'image/webp') {
            if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
                throw new \moodle_exception('invalidparameter', 'error', '', 'WebP non pris en charge par GD');
            }
            $gd = @imagecreatefromstring($content);
            if (!$gd) { throw new \moodle_exception('invalidparameter', 'error', '', 'WebP invalide'); }
            ob_start();
            imagepng($gd);
            $converted = ob_get_clean();
            imagedestroy($gd);
            if (!is_string($converted) || $converted === '') {
                throw new \moodle_exception('invalidparameter', 'error', '', 'Conversion WebP échouée');
            }
            $content = $converted;
            $type = 'PNG';
        } else {
            $type = $mime === 'image/jpeg' ? 'JPG' : 'PNG';
        }
        $scale = min($width / $info[0], $height / $info[1]);
        $iw = $info[0] * $scale;
        $ih = $info[1] * $scale;
        $pdf->Image('@' . $content, $x + ($width - $iw)/2, $y + ($height - $ih)/2,
            $iw, $ih, $type, '', '', false, 300);
    }

    private static function text(\pdf $pdf, object $page, float $x, float $y,
            float $w, float $h, bool $light): void {
        $para = json_decode((string)$page->paragraphsjson, true) ?: [];
        $text = implode("\n\n", array_map('strval', $para));
        $chars = \core_text::strlen($text);
        // Fixed visual layout with bounded text. MultiCell's fitcell shrinks where needed.
        $size = $chars > 750 ? 11 : ($chars > 450 ? 13 : 16);
        $pdf->SetTextColor($light ? 255 : 52, $light ? 255 : 40, $light ? 255 : 66);
        $pdf->SetFont('freesans', 'B', 20);
        $pdf->MultiCell($w, 24, (string)$page->title, 0, 'L', false, 1, $x, $y,
            true, 0, false, true, 24, 'T', true);
        $pdf->SetFont('freesans', '', $size);
        $pdf->MultiCell($w, $h - 28, $text, 0, 'L', false, 1, $x, $y + 28,
            true, 0, false, true, $h - 28, 'T', true);
    }

    /** Human-readable mini-book page, not a shrunk screenshot of the 320×180 PDF. */
    private static function compact_text(\pdf $pdf, string $title, string $content,
            float $x, float $y, float $w, float $h, bool $light = false,
            bool $center = false): void {
        $pdf->SetTextColor($light ? 255 : 52, $light ? 255 : 40, $light ? 255 : 66);
        $pdf->setCellHeightRatio(1.28);
        $pdf->SetFont('freesans', 'B', 11.5);
        $th = $pdf->getStringHeight($w, $title);
        if ($th > $h * 0.45) {
            throw new \moodle_exception('invalidparameter', 'error', '',
                'Titre trop long pour le mini-livre : ' . $title);
        }
        $remaining = $h - $th - 2.5;
        if ($remaining < 5) { throw new \moodle_exception('invalidparameter'); }
        // Minimum 8.5 pt for an actual 136×76.5 mm printed page. No silent clipping.
        $selected = 0.0;
        if (trim($content) === '') { $selected = 9.5; }
        else {
            foreach ([10.5, 10.0, 9.5, 9.0, 8.5] as $size) {
                $pdf->SetFont('freesans', '', $size);
                if ($pdf->getStringHeight($w, $content) <= $remaining - 1) {
                    $selected = $size;
                    break;
                }
            }
        }
        if ($selected < 8.5) {
            throw new \moodle_exception('invalidparameter', 'error', '',
                'Texte trop dense pour une petite page imprimée : ' . $title .
                ' ; diviser cette page dans le CRM avant export');
        }
        $align = $center ? 'C' : 'L';
        $pdf->SetFont('freesans', 'B', 11.5);
        $pdf->MultiCell($w, $th + .6, $title, 0, $align, false, 0, $x, $y,
            true, 0, false, true, $th + .6, 'T', false);
        if (trim($content) !== '') {
            $pdf->SetFont('freesans', '', $selected);
            $pdf->MultiCell($w, $remaining, $content, 0, $align, false, 0,
                $x, $y + $th + 2.5, true, 0, false, true, $remaining, 'T', false);
        }
    }

    /** Four-point sparkle, drawn with TCPDF primitives only (no external fonts/assets). */
    private static function sparkle(\pdf $pdf, float $cx, float $cy, float $r,
            bool $onDark = false): void {
        $pdf->SetDrawColor($onDark ? 234 : 181, $onDark ? 202 : 142, $onDark ? 143 : 119);
        $pdf->SetLineWidth(.20);
        $pdf->Line($cx, $cy - $r, $cx, $cy + $r);
        $pdf->Line($cx - $r * .65, $cy, $cx + $r * .65, $cy);
        $pdf->Line($cx - $r * .30, $cy - $r * .30, $cx + $r * .30, $cy + $r * .30);
        $pdf->Line($cx + $r * .30, $cy - $r * .30, $cx - $r * .30, $cy + $r * .30);
    }

    /** Intrinsic ornamental border, strictly inside crop marks and text safe area. */
    private static function story_frame(\pdf $pdf, float $x, float $y,
            float $w, float $h, bool $onDark = false): void {
        // These insets leave at least 7.5 mm of printable content clear of trim.
        $m = min($w, $h) > 110 ? 7.4 : 4.2;
        $pdf->SetLineWidth(.28);
        $pdf->SetDrawColor($onDark ? 229 : 194, $onDark ? 195 : 158, $onDark ? 139 : 160);
        $pdf->Rect($x + $m, $y + $m, $w - $m * 2, $h - $m * 2, 'D');
        $pdf->SetLineWidth(.12);
        $pdf->SetDrawColor($onDark ? 172 : 228, $onDark ? 143 : 204, $onDark ? 119 : 215);
        $in = $m + 1.55;
        $pdf->Rect($x + $in, $y + $in, $w - $in * 2, $h - $in * 2, 'D');
        // Short ornamental corner lines give an illuminated manuscript feel.
        $pdf->SetLineWidth(.28);
        $k = $m + 3.2;
        $l = min(6.5, $h * .085);
        foreach ([[$x + $k, $y + $k, 1, 1],
                  [$x + $w - $k, $y + $k, -1, 1],
                  [$x + $k, $y + $h - $k, 1, -1],
                  [$x + $w - $k, $y + $h - $k, -1, -1]] as $a) {
            [$cx, $cy, $dx, $dy] = $a;
            $pdf->Line($cx, $cy, $cx + $dx * $l, $cy);
            $pdf->Line($cx, $cy, $cx, $cy + $dy * $l);
        }
        // Top and bottom filigree remain outside the paragraph rectangle.
        $cx = $x + $w / 2;
        foreach ([$y + $m + 2.1, $y + $h - $m - 2.1] as $cy) {
            self::sparkle($pdf, $cx, $cy, min(1.75, $h * .024), $onDark);
            $pdf->SetLineWidth(.17);
            $pdf->Line($cx - 9.5, $cy, $cx - 3.8, $cy);
            $pdf->Line($cx + 3.8, $cy, $cx + 9.5, $cy);
        }
    }

    /** Text-only exterior covers are real illustrated editorial covers, not blank pages. */
    private static function story_cover(\pdf $pdf, string $title, string $subtitle,
            float $x, float $y, float $w, float $h): void {
        $scale = min($w / 136.0, $h / 76.5);
        $pdf->SetFillColor(54, 38, 77);
        $pdf->Rect($x, $y, $w, $h, 'F');
        $pdf->SetFillColor(67, 47, 92);
        $pdf->Rect($x + $w * .038, $y + $h * .052,
            $w * .924, $h * .896, 'F');
        self::story_frame($pdf, $x, $y, $w, $h, true);
        $pdf->SetTextColor(237, 211, 164);
        $pdf->SetFont('freesans', '', min(11.0, 7.2 * $scale));
        $pdf->MultiCell($w * .72, 5.0 * $scale, 'CAMPUSFR  ·  MES HISTOIRES',
            0, 'C', false, 0, $x + $w * .14, $y + $h * .13,
            true, 0, false, true, 5.0 * $scale, 'M', false);
        self::sparkle($pdf, $x + $w * .5, $y + $h * .29, 2.6 * $scale, true);
        $tx = $x + $w * .13;
        $tw = $w * .74;
        $ty = $y + $h * .35;
        $th = $h * .27;
        $chosen = 0.0;
        foreach ([18.0, 16.5, 15.0, 13.5, 12.0, 10.5, 9.0] as $font) {
            $pdf->SetFont('freesans', 'B', min(34, $font * $scale));
            if ($pdf->getStringHeight($tw, $title) < $th - 1.3 * $scale) {
                $chosen = min(34, $font * $scale);
                break;
            }
        }
        if ($chosen === 0.0) {
            throw new \moodle_exception('invalidparameter', 'error', '',
                'Titre trop long pour la couverture imprimée');
        }
        $pdf->SetTextColor(255, 251, 245);
        $pdf->SetFont('freesans', 'B', $chosen);
        $pdf->MultiCell($tw, $th, $title, 0, 'C', false, 0, $tx, $ty,
            true, 0, false, true, $th, 'M', false);
        if (trim($subtitle) !== '') {
            $sw = $w * .68;
            $sh = $h * .14;
            $pdf->SetTextColor(246, 222, 201);
            $pdf->SetFont('freesans', '', min(16, 9.1 * $scale));
            if ($pdf->getStringHeight($sw, $subtitle) > $sh) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'Sous-titre trop long pour la couverture imprimée');
            }
            $pdf->MultiCell($sw, $sh, $subtitle, 0, 'C', false, 0,
                $x + $w * .16, $y + $h * .69,
                true, 0, false, true, $sh, 'T', false);
        }
    }

    private static function story_back(\pdf $pdf, string $title, string $content,
            float $x, float $y, float $w, float $h): void {
        $pdf->SetFillColor(250, 247, 242);
        $pdf->Rect($x, $y, $w, $h, 'F');
        self::story_frame($pdf, $x, $y, $w, $h);
        $scale = min($w / 136.0, $h / 76.5);
        self::sparkle($pdf, $x + $w * .5, $y + $h * .30, 3.3 * $scale);
        $pdf->SetTextColor(61, 43, 82);
        $pdf->SetFont('freesans', 'B', min(30, 17 * $scale));
        $pdf->MultiCell($w * .70, $h * .22, $title, 0, 'C', false, 0,
            $x + $w * .15, $y + $h * .39,
            true, 0, false, true, $h * .22, 'M', true);
        if (trim($content) !== '') {
            $pdf->SetFont('freesans', '', min(16, 9.2 * $scale));
            if ($pdf->getStringHeight($w * .72, $content) > $h * .18) {
                throw new \moodle_exception('invalidparameter', 'error', '',
                    'Texte trop long pour la quatrième de couverture');
            }
            $pdf->MultiCell($w * .72, $h * .18, $content, 0, 'C', false, 0,
                $x + $w * .14, $y + $h * .65,
                true, 0, false, true, $h * .18, 'T', false);
        }
    }

    private static function compact_page(\pdf $pdf, ?\stored_file $image,
            object $page, float $x, float $y, float $w, float $h): void {
        $kind = (string)$page->kind;
        $paragraphs = json_decode((string)$page->paragraphsjson, true) ?: [];
        $content = implode("\n\n", array_map('strval', $paragraphs));
        if (!$image && $kind === 'cover') {
            self::story_cover($pdf, (string)$page->title, $content, $x, $y, $w, $h);
            return;
        }
        if (!$image && $kind === 'back') {
            self::story_back($pdf, (string)$page->title, $content, $x, $y, $w, $h);
            return;
        }
        $pdf->SetFillColor(250, 247, 242);
        $pdf->Rect($x, $y, $w, $h, 'F');
        if ($image && in_array($kind, ['cover', 'back'], true)) {
            self::draw_image($pdf, $image, $x + 1, $y + 1, $w - 2, $h - 2);
            if (method_exists($pdf, 'SetAlpha')) { $pdf->SetAlpha(.92); }
            $pdf->SetFillColor(28, 22, 41);
            $pdf->Rect($x + 2, $y + $h * .63, $w - 4, $h * .35, 'F');
            if (method_exists($pdf, 'SetAlpha')) { $pdf->SetAlpha(1); }
            self::compact_text($pdf, (string)$page->title, $content,
                $x + 9, $y + $h * .68, $w - 18, $h * .27, true, true);
            self::story_frame($pdf, $x, $y, $w, $h, true);
        } else if ($image) {
            // Leave a comfortable 7 mm inner gutter to keep the book fold out of the picture.
            self::draw_image($pdf, $image, $x + 7, $y + 9, $w * .44 - 8, $h - 18);
            $pdf->SetDrawColor(215, 196, 207);
            $pdf->SetLineWidth(.15);
            $pdf->Line($x + $w * .50, $y + 12, $x + $w * .50, $y + $h - 12);
            self::compact_text($pdf, (string)$page->title, $content,
                $x + $w * .54, $y + 11, $w * .37, $h - 22);
            self::story_frame($pdf, $x, $y, $w, $h);
        } else {
            self::compact_text($pdf, (string)$page->title, $content,
                $x + 12, $y + 15, $w - 24, $h - 30,
                false, in_array($kind, ['cover', 'back'], true));
            self::story_frame($pdf, $x, $y, $w, $h);
        }
    }

    private static function synthetic_page(\pdf $pdf, string $kind, string $title,
            float $x, float $y, float $w, float $h): void {
        if ($kind === 'generated_cover') {
            self::story_cover($pdf, $title, 'Une histoire à découvrir · CampusFR',
                $x, $y, $w, $h);
            return;
        }
        if ($kind === 'generated_back') {
            self::story_back($pdf, 'Fin', 'À bientôt pour une nouvelle histoire.',
                $x, $y, $w, $h);
            return;
        }
        $pdf->SetFillColor(250, 247, 242);
        $pdf->Rect($x, $y, $w, $h, 'F');
        self::story_frame($pdf, $x, $y, $w, $h);
        if ($kind === 'blank') { return; }
        if ($kind === 'owner') {
            self::sparkle($pdf, $x + $w * .5, $y + $h * .26, 3.0);
            $pdf->SetTextColor(61, 43, 82);
            $pdf->SetFont('freesans', 'B', 12);
            $pdf->MultiCell($w - 25, 12, 'Ce livre appartient à…', 0, 'C',
                false, 0, $x + 12.5, $y + 27);
            $pdf->SetDrawColor(190, 164, 185);
            $pdf->SetLineWidth(.28);
            $pdf->Line($x + 29, $y + 53, $x + $w - 29, $y + 53);
            $pdf->SetFont('freesans', '', 8);
            $pdf->SetTextColor(119, 104, 131);
            $pdf->MultiCell($w - 25, 8, 'Mon livre, mon aventure', 0, 'C',
                false, 0, $x + 12.5, $y + 59);
        }
    }

    private static function booklet_sheet(\pdf $pdf, array $side, array $items,
            int $bookid, string $title, int $sheet, int $sheets, string $face,
            ?callable $imageprovider = null): void {
        // A4 landscape 297×210. Two 136×76.5 mm pages, folded at x=148.5.
        // Outer margins 12.5 mm protect crop marks from normal printer edges.
        $pw = 136.0;
        $ph = 76.5;
        $py = (210.0 - $ph) / 2;
        $left = 12.5;
        $right = 148.5;
        $pdf->AddPage('L', 'A4');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(0, 0, 297, 210, 'F');
        $pdf->SetTextColor(80, 65, 86);
        $pdf->SetFont('freesans', '', 8.5);
        $pdf->Text(13, 16, 'CampusFR · Mini-livre 16:9 · feuille ' . $sheet . '/' . $sheets .
            ' · ' . ($face === 'front' ? 'RECTO' : 'VERSO'));
        $pdf->SetFont('freesans', '', 7.3);
        $pdf->Text(13, 24, 'A4 paysage · recto verso (bords courts) · taille réelle 100 %');
        $pdf->Text(13, 31, 'Plier au milieu, assembler, agrafer et découper selon les repères.');
        foreach ([[$left, $side[0]], [$right, $side[1]]] as [$px, $number]) {
            $item = $items[$number - 1];
            if ($item['kind'] === 'source') {
                $page = $item['page'];
                self::compact_page($pdf, self::image($bookid, $page, $imageprovider), $page, $px, $py, $pw, $ph);
            } else {
                self::synthetic_page($pdf, $item['kind'], $title, $px, $py, $pw, $ph);
            }
        }
        // Printer-safe trim marks outside the printable book, not across text.
        $pdf->SetDrawColor(95, 87, 99);
        $pdf->SetLineWidth(.2);
        foreach ([$left, $right, $right + $pw] as $mx) {
            $pdf->Line($mx, $py - 7, $mx, $py - 1.3);
            $pdf->Line($mx, $py + $ph + 1.3, $mx, $py + $ph + 7);
        }
        foreach ([$py, $py + $ph] as $my) {
            $pdf->Line($left - 7, $my, $left - 1.3, $my);
            $pdf->Line($right + $pw + 1.3, $my, $right + $pw + 7, $my);
        }
        $pdf->SetDrawColor(224, 57, 124);
        $pdf->Line($right, $py - 17, $right, $py - 9);
        $pdf->Line($right, $py + $ph + 9, $right, $py + $ph + 17);
    }

    public static function download(int $bookid, object $book, array $pages,
            string $mode = 'reading', ?callable $imageprovider = null): void {
        global $CFG;
        self::validate_pages($pages);
        if (!in_array($mode, ['reading', 'booklet'], true)) {
            throw new \moodle_exception('invalidparameter');
        }
        require_once($CFG->libdir . '/pdflib.php');
        if (!class_exists(\pdf::class)) {
            throw new \moodle_exception('invalidparameter', 'error', '', 'Bibliothèque PDF indisponible');
        }
        $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower((string)$book->slug));
        if (!$slug) { $slug = 'histoire-' . $bookid; }
        if ($mode === 'booklet') {
            require_once(__DIR__ . '/ReaderBookletImposition.php');
            $plan = ReaderBookletImposition::plan($pages);
            $items = [];
            foreach ($plan['items'] as $item) {
                if ($item['kind'] === 'source') { $item['page'] = $pages[$item['index']]; }
                $items[] = $item;
            }
            $pdf = new \pdf('L', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetCreator('CampusFR Atelier Bibliothèque');
            $pdf->SetTitle((string)$book->title . ' — Mini-livre A4');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetAutoPageBreak(false, 0);
            $sheets = count($plan['sheets']);
            foreach ($plan['sheets'] as $i => $sheet) {
                self::booklet_sheet($pdf, $sheet['front'], $items, $bookid,
                    (string)$book->title, $i + 1, $sheets, 'front', $imageprovider);
                self::booklet_sheet($pdf, $sheet['back'], $items, $bookid,
                    (string)$book->title, $i + 1, $sheets, 'back', $imageprovider);
            }
            $pdf->Output('campusfr-mini-livre-' . $slug . '-a4.pdf', 'D');
            return;
        }
        $pdf = new \pdf('L', 'mm', [self::WIDTH, self::HEIGHT], true, 'UTF-8');
        $pdf->SetCreator('CampusFR Atelier Bibliothèque');
        $pdf->SetTitle((string)$book->title . ' — PDF Lecture 16:9');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        foreach ($pages as $i => $page) {
            $img = self::image($bookid, $page, $imageprovider);
            $kind = (string)$page->kind;
            $pdf->AddPage('L', [self::WIDTH, self::HEIGHT]);
            $pdf->SetFillColor(250, 247, 242);
            $pdf->Rect(0, 0, self::WIDTH, self::HEIGHT, 'F');
            $isc = $img && in_array($kind, ['cover', 'back'], true);
            $paragraphs = json_decode((string)$page->paragraphsjson, true) ?: [];
            $storytext = implode("\n\n", array_map('strval', $paragraphs));
            if (!$img && $kind === 'cover') {
                self::story_cover($pdf, (string)$page->title, $storytext,
                    0, 0, self::WIDTH, self::HEIGHT);
                continue;
            }
            if (!$img && $kind === 'back') {
                self::story_back($pdf, (string)$page->title, $storytext,
                    0, 0, self::WIDTH, self::HEIGHT);
                continue;
            }
            if ($isc) {
                self::draw_image($pdf, $img, 0, 0, self::WIDTH, self::HEIGHT);
                if (method_exists($pdf, 'SetAlpha')) { $pdf->SetAlpha(.78); }
                $pdf->SetFillColor(28, 22, 41);
                $pdf->Rect(0, 107, self::WIDTH, 73, 'F');
                if (method_exists($pdf, 'SetAlpha')) { $pdf->SetAlpha(1); }
                self::text($pdf, $page, 18, 112, 284, 64, true);
            } else if ($img) {
                self::draw_image($pdf, $img, 0, 0, 157, self::HEIGHT);
                // Subtle fold/gutter for the familiar digital open-book layout.
                $pdf->SetFillColor(234, 226, 232);
                $pdf->Rect(157, 3, 1.0, self::HEIGHT - 6, 'F');
                self::text($pdf, $page, 170, 19, 133, 149, false);
            } else {
                self::text($pdf, $page, 32, 30, 256, 119, false);
            }
            // Finishing details follow the illustration/text: do not obstruct content.
            self::story_frame($pdf, 0, 0, self::WIDTH, self::HEIGHT, $isc);
            $pdf->SetTextColor($isc ? 238 : 115, $isc ? 221 : 103, $isc ? 195 : 127);
            $pdf->SetFont('freesans', '', 8);
            $pdf->Text(292, 164, (string)($i + 1));
        }
        $pdf->Output('campusfr-lecture-' . $slug . '-16x9.pdf', 'D');
    }

}
