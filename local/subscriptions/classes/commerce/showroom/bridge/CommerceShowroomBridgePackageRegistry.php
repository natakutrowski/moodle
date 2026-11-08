<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/**
 * Trusted, filesystem-deployed Lovable Bridge designs.
 *
 * Only server-deployed, compiled packages are visible to the CMS. Executable
 * resources are NEVER uploaded through an admin form or a showroom text field.
 */
final class CommerceShowroomBridgePackageRegistry {
    public const TEMPLATE = 'local_subscriptions/showroom/bridge';
    private const MAX_PACKAGES = 100;
    private const MAX_MANIFEST_BYTES = 8192;
    private const MAX_CATALOG_BYTES = 524288;

    /** @return array<string,string> */
    public static function options(): array {
        return self::discover_from_directory(self::design_root());
    }

    /**
     * Discover compiled packages inside an explicit trusted directory.
     * Exposed for hermetic PHPUnit tests; public CMS always calls options().
     *
     * @return array<string,string> id => human label
     */
    public static function discover_from_directory(string $root): array {
        if (!is_dir($root) || is_link($root)) {
            return [];
        }
        $items = scandir($root);
        if ($items === false) {
            return [];
        }
        $result = [];
        foreach ($items as $id) {
            if (!self::valid_id($id)) {
                continue;
            }
            $base = $root . '/' . $id;
            if (!is_dir($base) || is_link($base)) {
                continue;
            }
            try {
                $manifest = self::read_manifest($base, $id, true);
                $result[$id] = $manifest['label'];
            } catch (\Throwable $ignored) {
                // A partially deployed or malformed package must never appear.
                continue;
            }
            if (count($result) >= self::MAX_PACKAGES) {
                break;
            }
        }
        natcasesort($result);
        return $result;
    }

    private static function design_root(): string {
        return dirname(__DIR__, 4) . '/designs';
    }

    private static function valid_id(string $id): bool {
        return (bool)preg_match('/\A[a-z][a-z0-9_]{2,47}\z/D', $id);
    }

    public static function require_id(string $id): void {
        if (!self::valid_id($id) || !array_key_exists($id, self::options())) {
            throw new \invalid_parameter_exception('Unsupported Showroom bridge design: ' . $id);
        }
    }

    /**
     * @return array{id:string,label:string,version:string,entry:string,css:string,content:string}
     */
    public static function require_manifest(string $id, bool $requirebuild = true): array {
        if (!self::valid_id($id)) {
            throw new \invalid_parameter_exception('Unsupported Showroom bridge design id.');
        }
        $base = self::design_root() . '/' . $id;
        if (!is_dir($base) || is_link($base)) {
            throw new \invalid_parameter_exception('Missing or unsafe Bridge design directory.');
        }
        return self::read_manifest($base, $id, $requirebuild);
    }

    /**
     * Validate a single package without consulting global options() (avoids
     * recursion during discovery).
     *
     * @return array{id:string,label:string,version:string,entry:string,css:string,content:string}
     */
    private static function read_manifest(string $base, string $id, bool $requirebuild): array {
        $path = $base . '/manifest.json';
        if (!is_file($path) || is_link($path) || filesize($path) > self::MAX_MANIFEST_BYTES) {
            throw new \invalid_parameter_exception('Missing/unsafe Bridge manifest.');
        }
        $manifest = json_decode((string)file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || ($manifest['id'] ?? '') !== $id) {
            throw new \invalid_parameter_exception('Invalid Bridge manifest id.');
        }
        $version = $manifest['version'] ?? null;
        if (!is_string($version) || !preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/D', $version)) {
            throw new \invalid_parameter_exception('Invalid Bridge package version.');
        }
        $label = $manifest['label'] ?? ($id === 'articles_alf'
            ? 'Articles / Alf' : str_replace('_', ' ', $id));
        if (!is_string($label) || $label === '' || strlen($label) > 150 || preg_match('/[\x00-\x1f\x7f<>]/', $label)) {
            throw new \invalid_parameter_exception('Unsafe Bridge label.');
        }
        $result = ['id' => $id, 'label' => $label, 'version' => $version];
        $expected = [
            'entry' => 'dist/main.js',
            'css' => 'dist/style.css',
            'content' => 'content/ru.json',
        ];
        foreach ($expected as $field => $relative) {
            if (($manifest[$field] ?? '') !== $relative) {
                throw new \invalid_parameter_exception('Invalid Bridge manifest path: ' . $field);
            }
            $file = $base . '/' . $relative;
            if (($requirebuild || $field === 'content') && (!is_file($file) || is_link($file))) {
                throw new \invalid_parameter_exception('Missing/unsafe Bridge artifact: ' . $relative);
            }
            $result[$field] = $relative;
        }
        // All languages must be provided by the package before it is selectable.
        // This is required by CommerceShowroomBridgeContentEditor X2.
        if ($requirebuild) {
            foreach (['ru', 'fr', 'en'] as $lang) {
                $catalog = $base . '/content/' . $lang . '.json';
                if (!is_file($catalog) || is_link($catalog) || filesize($catalog) > self::MAX_CATALOG_BYTES) {
                    throw new \invalid_parameter_exception('Missing/unsafe Bridge translation: ' . $lang);
                }
            }
        }
        return $result;
    }

    public static function base_directory(string $id): string {
        self::require_id($id);
        return self::design_root() . '/' . $id;
    }

    public static function asset_url(string $id, string $relative, string $version = ''): string {
        self::require_id($id);
        if (!preg_match('~\Adist/[a-zA-Z0-9_.-]+\z~', $relative)) {
            throw new \invalid_parameter_exception('Invalid Bridge artifact path.');
        }
        $manifest = self::require_manifest($id);
        if ($relative !== $manifest['entry'] && $relative !== $manifest['css']) {
            throw new \invalid_parameter_exception('Artifact not declared by Bridge package.');
        }
        $url = new \moodle_url('/local/subscriptions/designs/' . $id . '/' . $relative);
        if ($version !== '') {
            $url->param('v', $version);
        }
        return $url->out(false);
    }
}
