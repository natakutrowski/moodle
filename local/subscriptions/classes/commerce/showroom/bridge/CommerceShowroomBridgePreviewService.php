<?php
// CampusFR Gustave Builder: private, sandboxed visual review of a non-publishable ZIP.
declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** A strictly read-only renderer. Nothing is extracted into the webroot. */
final class CommerceShowroomBridgePreviewService {
    private const MAX_ZIP_BYTES = 20971520;
    private const MAX_ENTRIES = 200;
    private const MAX_TOTAL_UNCOMPRESSED = 18874368;

    /** Only a ready, session-authorised archive may be rendered. */
    public static function approved_archive(string $dir, string $id): string {
        if (!CommerceShowroomBridgeCompileService::valid_id($id)) {
            throw new \invalid_parameter_exception('Identifiant du design invalide.');
        }
        $state = CommerceShowroomBridgeCompileService::get_status($dir);
        if (!$state || ($state['state'] ?? '') !== 'ready'
                || ($state['designId'] ?? '') !== $id) {
            throw new \invalid_parameter_exception('Aucun aperçu compilé et validé dans ce projet.');
        }
        $path = $dir . '/campusfr-lovable-' . $id . '-apercu-non-publiable.zip';
        if (is_link($path) || !is_file($path) || !is_readable($path)
                || filesize($path) < 256 || filesize($path) > self::MAX_ZIP_BYTES) {
            throw new \invalid_parameter_exception('Archive privée absente ou non conforme.');
        }
        return $path;
    }

    /** Checks package bounds and manifest, then generates a self-contained opaque-origin preview. */
    public static function render_frame(string $archive, string $id, ?array $replacementcontents = null, ?array $commerce = null): string {
        if (!CommerceShowroomBridgeCompileService::valid_id($id) || !class_exists(\ZipArchive::class)) {
            throw new \invalid_parameter_exception('Archive ou extension ZIP invalide.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($archive, \ZipArchive::RDONLY) !== true) {
            throw new \invalid_parameter_exception('Impossible de lire l’archive compilée.');
        }
        try {
            if ($zip->numFiles < 7 || $zip->numFiles > self::MAX_ENTRIES) {
                throw new \invalid_parameter_exception('Archive compilée trop grande ou incomplète.');
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $info = $zip->statIndex($i);
                if (!$info || !isset($info['name'], $info['size'])
                        || !is_string($info['name']) || str_contains($info['name'], '\\')
                        || str_contains($info['name'], '..') || str_starts_with($info['name'], '/')
                        || str_contains($info['name'], "\0")) {
                    throw new \invalid_parameter_exception('Chemin ZIP dangereux.');
                }
                $total += (int)$info['size'];
                if ($total > self::MAX_TOTAL_UNCOMPRESSED) {
                    throw new \invalid_parameter_exception('Décompression ZIP trop volumineuse.');
                }
            }
            $prefix = 'draft/' . $id . '/';
            $manifest = json_decode(self::read($zip, $prefix . 'manifest.json', 10000), true, 16,
                JSON_THROW_ON_ERROR);
            if (!is_array($manifest) || ($manifest['id'] ?? '') !== $id
                    || ($manifest['entry'] ?? '') !== 'dist/main.js'
                    || ($manifest['css'] ?? '') !== 'dist/style.css'
                    || ($manifest['content'] ?? '') !== 'content/ru.json') {
                throw new \invalid_parameter_exception('Manifeste du design non conforme.');
            }
            if (trim(self::read($zip, 'NON_PUBLIABLE.txt', 4096)) === '') {
                throw new \invalid_parameter_exception('Marqueur non publiable manquant.');
            }
            $contents = json_decode(self::read($zip, $prefix . 'content/ru.json', 600000), true, 32,
                JSON_THROW_ON_ERROR);
            if (!is_array($contents) || !isset($contents['page']) || !is_array($contents['page'])) {
                throw new \invalid_parameter_exception('Catalogue de textes absent ou incorrect.');
            }
            if ($replacementcontents !== null) {
                if (!isset($replacementcontents['page']) || !is_array($replacementcontents['page'])) {
                    throw new \invalid_parameter_exception('Catalogue de traduction invalide.');
                }
                // No arbitrary JSX/HTML is executable from text fields: React renders data.
                $expectedkeys = array_keys($contents['page']);
                if (array_keys($replacementcontents['page']) !== $expectedkeys) {
                    throw new \invalid_parameter_exception('Catalogue de traduction incomplet.');
                }
                foreach ($replacementcontents['page'] as $key => $value) {
                    if (!is_string($value) || strlen($value) > 3000) {
                        throw new \invalid_parameter_exception('Traduction trop longue.');
                    }
                }
                $contents = $replacementcontents;
            }
            $css = self::read($zip, $prefix . 'dist/style.css', 2000000);
            // The Lovable CSS was authored for the document body. In our isolated
            // ShadowRoot, selectors targeting `body` cannot match the host. Recreate
            // only the basic inherited document typography on the host; do not inject
            // styles into the Moodle page. Google Fonts is explicitly allowed by preview CSP.
            $css .= "\n:host{display:block;width:100%;min-height:100%;"
                . "font-family:'Nunito',system-ui,sans-serif;line-height:1.5;"
                . "color:var(--foreground);background:var(--background);}";

            $js = self::read($zip, $prefix . 'dist/main.js', 6000000);
            if (!str_contains($js, 'data-campusfr-bridge') || !str_contains($js, 'campusfr-showroom-bridge/v1')) {
                throw new \invalid_parameter_exception('Le JavaScript ne respecte pas le contrat Bridge.');
            }
            // Vite emits `new URL("assets/file",import.meta.url)`. A data: module has no
            // relative base: replace ONLY strictly named compiled assets by inline data URLs.
            $assets = 0;
            $js = preg_replace_callback(
                '~new URL\(\s*(["\'])(assets/[a-zA-Z0-9_.-]+\.(?:png|jpg|jpeg|webp|gif|svg))\1\s*,\s*import\.meta\.url\s*\)~',
                static function (array $m) use ($zip, $prefix, &$assets): string {
                    $extension = strtolower(pathinfo($m[2], PATHINFO_EXTENSION));
                    $mime = match ($extension) {
                        'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
                        'gif' => 'image/gif', 'svg' => 'image/svg+xml',
                    };
                    $binary = self::read($zip, $prefix . 'dist/' . $m[2], 4000000);
                    $assets++;
                    return 'new URL("data:' . $mime . ';base64,' . base64_encode($binary) . '",import.meta.url)';
                },
                $js
            );
            if ($js === null || $assets === 0
                    || preg_match('~new URL\(["\']assets/~', $js)) {
                throw new \invalid_parameter_exception('Références graphiques Vite non résolues.');
            }
            // Script is a data: module; it cannot break out of the page's HTML or load
            // anything from Moodle or the Internet. The frame itself has a CSP sandbox.
            $module = 'data:text/javascript;base64,' . base64_encode($js);
            $stylesheet = 'data:text/css;base64,' . base64_encode($css);
            $payload = json_encode([
                'api' => 'campusfr-showroom-bridge/v1',
                'design' => ['id' => $id],
                'content' => $contents,
                'commerce' => $commerce ?? ['currency' => 'EUR', 'offers' => []],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
                | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
            $encodedid = htmlspecialchars($id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $encodedcss = htmlspecialchars($stylesheet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $encodedjs = htmlspecialchars($module, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
                . '<meta name="viewport" content="width=device-width,initial-scale=1">'
                . '<title>Gustave Builder — aperçu privé</title>'
                . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&amp;family=Nunito:wght@400;500;600;700;800;900&amp;display=swap">'
                . '<style>html,body{padding:0;margin:0;width:100%;min-height:100%;background:#101020}'
                . 'body{overflow-x:hidden}.campusfr-bridge-prerender{padding:24px;color:white;font:16px system-ui}</style>'
                . '</head><body>'
                . '<script id="campusfr-preview-payload" type="application/json">' . $payload . '</script>'
                . '<div data-campusfr-bridge="' . $encodedid . '" '
                . 'data-bridge-payload="campusfr-preview-payload" data-bridge-css="' . $encodedcss . '">'
                . '<div class="campusfr-bridge-prerender">Chargement de l’aperçu…</div></div>'
                . '<script type="module" src="' . $encodedjs . '"></script>'
                . '</body></html>';
        } finally {
            $zip->close();
        }
    }

    /** ZIP member reads are bounded even if archive metadata is misleading. */
    private static function read(\ZipArchive $zip, string $name, int $maximum): string {
        $stat = $zip->statName($name);
        if (!$stat || (int)$stat['size'] > $maximum || (int)$stat['size'] < 1) {
            throw new \invalid_parameter_exception('Entrée ZIP absente ou trop volumineuse : ' . $name);
        }
        $contents = $zip->getFromName($name, 0);
        if (!is_string($contents) || strlen($contents) > $maximum
                || strlen($contents) !== (int)$stat['size']) {
            throw new \invalid_parameter_exception('Entrée ZIP illisible : ' . $name);
        }
        return $contents;
    }
}
