<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\audit\nativeux;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only static auditor for the 7.95 Native UX foundation.
 */
final class CommerceNativeUxStaticAuditor {
    private const UI_PATHS = [
        'admin/commerce',
        'admin/digital',
        'ajax/user_timeline.php',
        'checkout.php',
        'digital_catalog.php',
        'digital_product.php',
        'digital_success.php',
        'my_purchases.php',
        'my_subscriptions.php',
        'payment_cancel.php',
        'payment_error.php',
        'payment_success.php',
        'subscribe.php',
    ];

    private const LEGACY_PATTERNS = [
        '/\\\\commerce\\\\legacy\\\\/i' => 'commerce legacy namespace',
        '/\\\\commerce\\\\[^;\n]*\\\\legacy\\\\/i' => 'nested legacy namespace',
        '/\bLegacy[A-Z][A-Za-z0-9_]*\b/' => 'Legacy class',
        '/\bsubscription_manager\b/i' => 'subscription_manager',
        '/\bpricing_manager\b/i' => 'pricing_manager',
        '/\bDigitalProductManager\b/i' => 'DigitalProductManager',
        '/\bDigitalPurchaseManager\b/i' => 'DigitalPurchaseManager',
        '/\bSubscriptionPurchaseFactory\b/' => 'SubscriptionPurchaseFactory',
        '/\bDigitalPurchaseFactory\b/' => 'DigitalPurchaseFactory',
        '/\bLegacySubscriptionPlanRepository\b/' => 'LegacySubscriptionPlanRepository',
        '/\bLegacyDigitalProductRepository\b/' => 'LegacyDigitalProductRepository',
    ];

    private const DB_WRITE_METHODS = [
        'insert_record',
        'insert_records',
        'update_record',
        'update_records',
        'delete_records',
        'delete_records_select',
        'set_field',
        'set_field_select',
        'execute',
    ];

    public function __construct(
        private readonly string $pluginroot
    ) {
    }

    public static function from_plugin_root(string $pluginroot): self {
        $resolved = realpath($pluginroot);
        if ($resolved === false || !is_dir($resolved)) {
            throw new \RuntimeException('Unable to resolve the local_subscriptions plugin root.');
        }
        return new self(rtrim(str_replace('\\', '/', $resolved), '/'));
    }

    public function audit_feature_flags(): array {
        $files = $this->php_files(['settings.php', 'classes', 'cli', 'db', 'admin', 'ajax', '.']);
        $flags = [];

        foreach ($files as $relativepath => $absolutepath) {
            $content = $this->read($absolutepath);
            if ($content === '') {
                continue;
            }

            $patterns = [
                '/get_config\(\s*[\'"]local_subscriptions[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/' => 'read',
                '/set_config\(\s*[\'"]([^\'"]+)[\'"]\s*,[^,]+,\s*[\'"]local_subscriptions[\'"]\s*\)/' => 'write',
                '/unset_config\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]local_subscriptions[\'"]\s*\)/' => 'delete',
                '/new\s+admin_setting_[a-zA-Z0-9_]+\s*\(\s*[\'"]local_subscriptions\/([^\'"]+)[\'"]/' => 'definition',
            ];

            foreach ($patterns as $pattern => $operation) {
                if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                foreach ($matches[1] as [$key, $offset]) {
                    if (!$this->looks_like_commerce_flag($key)) {
                        continue;
                    }
                    $flags[$key] ??= [
                        'key' => $key,
                        'category' => $this->setting_category($key),
                        'usages' => [],
                    ];
                    $flags[$key]['usages'][] = [
                        'file' => $relativepath,
                        'line' => $this->line_number($content, $offset),
                        'operation' => $operation,
                    ];
                }
            }
        }

        ksort($flags);
        foreach ($flags as &$flag) {
            $operations = array_column($flag['usages'], 'operation');
            $flag['defined'] = in_array('definition', $operations, true);
            $flag['read'] = in_array('read', $operations, true);
            $flag['written'] = in_array('write', $operations, true);
            $flag['recommendedstatus'] = $this->recommend_flag_status($flag['key'], $flag['read']);
        }
        unset($flag);

        return array_values($flags);
    }

    public function audit_settings(): array {
        $settingspath = $this->pluginroot . '/settings.php';
        $content = $this->read($settingspath);
        $settings = [];

        if ($content === '') {
            return $settings;
        }

        $pattern = '/new\s+(admin_setting_[a-zA-Z0-9_]+)\s*\(\s*[\'\"]local_subscriptions\/([^\'\"]+)[\'\"]/m';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $key = $match[2][0];
            $settings[] = [
                'key' => $key,
                'type' => $match[1][0],
                'category' => $this->setting_category($key),
                'line' => $this->line_number($content, $match[0][1]),
                'commerce' => $this->looks_like_commerce_flag($key),
                'recommendedstatus' => $this->recommend_flag_status($key, true),
            ];
        }

        usort($settings, static fn(array $left, array $right): int => [$left['category'], $left['key']] <=> [$right['category'], $right['key']]);
        return $settings;
    }

    public function audit_entrypoints(): array {
        $files = $this->php_files(self::UI_PATHS);
        $results = [];

        foreach ($files as $relativepath => $absolutepath) {
            $content = $this->read($absolutepath);
            $uses = $this->use_statements($content);
            $legacy = $this->legacy_matches($content);
            $directdb = $this->database_calls($content);
            $nativeuses = array_values(array_filter($uses, static function(string $use): bool {
                return str_contains(strtolower($use), '\\commerce\\')
                    && !str_contains(strtolower($use), '\\legacy\\');
            }));

            $status = match (true) {
                $legacy !== [] && $nativeuses !== [] => 'mixed',
                $legacy !== [] => 'legacy',
                $nativeuses !== [] => 'native',
                default => 'unclassified',
            };

            $results[] = [
                'file' => $relativepath,
                'nativeuses' => $nativeuses,
                'legacyfindings' => $legacy,
                'directdbcalls' => $directdb,
                'status' => $status,
            ];
        }

        return $results;
    }

    public function audit_legacy_dependencies(array $paths = []): array {
        $paths = $paths === [] ? ['classes', ...self::UI_PATHS] : $paths;
        $files = $this->php_files($paths);
        $results = [];

        foreach ($files as $relativepath => $absolutepath) {
            $findings = $this->legacy_matches($this->read($absolutepath));
            if ($findings === []) {
                continue;
            }

            $results[] = [
                'file' => $relativepath,
                'layer' => $this->classify_layer($relativepath),
                'severity' => $this->classify_layer($relativepath) === 'ui' ? 'error' : 'accepted-until-7.99',
                'findings' => $findings,
            ];
        }

        return $results;
    }

    public function audit_data_access(array $paths = []): array {
        $paths = $paths === [] ? self::UI_PATHS : $paths;
        $files = $this->php_files($paths);
        $results = [];

        foreach ($files as $relativepath => $absolutepath) {
            $calls = $this->database_calls($this->read($absolutepath));
            if ($calls === []) {
                continue;
            }

            $commercecalls = array_values(array_filter(
                $calls,
                static fn(array $call): bool => $call['classification'] === 'commerce'
            ));
            $dynamiccalls = array_values(array_filter(
                $calls,
                static fn(array $call): bool => $call['classification'] === 'dynamic'
            ));

            $results[] = [
                'file' => $relativepath,
                'layer' => $this->classify_layer($relativepath),
                'calls' => $calls,
                'commercecalls' => $commercecalls,
                'dynamiccalls' => $dynamiccalls,
                'status' => match (true) {
                    $this->classify_layer($relativepath) !== 'ui' => 'repository-allowed',
                    $commercecalls !== [] => 'commerce-db-access',
                    $dynamiccalls !== [] => 'dynamic-review',
                    default => 'external-db-access',
                },
            ];
        }

        return $results;
    }

    public function native_architecture_map(): array {
        return [
            'ui' => [
                'admin/commerce/products/*',
                'future universal catalog',
                'future unified checkout',
                'future premium purchases page',
                'future User 360 Commerce timeline',
            ],
            'presentation' => [
                'CatalogItemViewModel',
                'CheckoutSummaryViewModel',
                'PurchaseCardViewModel',
                'TimelineCommerceEventViewModel',
            ],
            'native_services' => [
                'commerce/catalog/service',
                'commerce/read/service',
                'commerce/checkout',
                'commerce/payment/orchestration',
                'commerce/fulfillment/native',
                'commerce/bundle',
            ],
            'native_domain' => [
                'commerce/catalog/domain',
                'commerce/purchase',
                'commerce/payment',
                'commerce/bundle/domain',
                'commerce/fulfillment/native',
            ],
            'repositories' => [
                'commerce/catalog/repository',
                'commerce/read/repository',
                'commerce/persistence/sql',
                'commerce/fulfillment/native/persistence',
            ],
            'compatibility_until_7_99' => [
                'commerce/legacy',
                'commerce/payment/legacy',
                'commerce/purchase/*/Legacy*',
                'commerce/fulfillment/*/Legacy*',
                'commerce/migration',
            ],
        ];
    }

    public function native_gate(array $relativepaths): array {
        $checks = [
            'NG-1' => ['label' => 'No new UI dependency on Legacy', 'errors' => []],
            'NG-2' => ['label' => 'No new direct Commerce DB access in UI', 'errors' => []],
            'NG-3' => ['label' => 'New Commerce UI uses Native services/repositories', 'errors' => []],
            'NG-4' => ['label' => 'Static PHP syntax validation', 'errors' => []],
        ];

        foreach ($relativepaths as $relativepath) {
            $relativepath = $this->normalize_relative_path($relativepath);
            $absolutepath = $this->pluginroot . '/' . $relativepath;
            if (!is_file($absolutepath) || pathinfo($absolutepath, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $content = $this->read($absolutepath);
            $layer = $this->classify_layer($relativepath);

            if ($layer === 'ui') {
                foreach ($this->legacy_matches($content) as $finding) {
                    $checks['NG-1']['errors'][] = $relativepath . ':' . $finding['line'] . ' ' . $finding['label'];
                }

                foreach ($this->database_calls($content) as $call) {
                    if (in_array($call['classification'], ['commerce', 'dynamic'], true)) {
                        $checks['NG-2']['errors'][] = $relativepath . ':' . $call['line'] . ' $DB->' . $call['method']
                            . ($call['table'] !== '' ? ' (' . $call['table'] . ')' : ' (<dynamic>)');
                    }
                }

                if ($this->is_commerce_ui_file($relativepath)
                        && !str_contains($content, 'local_subscriptions\\commerce\\')
                        && !str_contains($content, 'local_subscriptions\\output\\')) {
                    $checks['NG-3']['errors'][] = $relativepath . ' has no visible Native Commerce or presentation dependency.';
                }
            }

            $syntaxerror = $this->php_syntax_error($absolutepath);
            if ($syntaxerror !== null) {
                $checks['NG-4']['errors'][] = $relativepath . ': ' . $syntaxerror;
            }
        }

        foreach ($checks as &$check) {
            $check['passed'] = $check['errors'] === [];
        }
        unset($check);

        return [
            'files' => array_values($relativepaths),
            'checks' => $checks,
            'passed' => !in_array(false, array_column($checks, 'passed'), true),
        ];
    }

    public function changed_files(string $baseline): array {
        $reporoot = $this->git_repository_root();
        $pluginprefix = $this->plugin_repository_prefix($reporoot);
        $commands = [
            'git -C ' . escapeshellarg($reporoot)
                . ' diff --name-only --diff-filter=ACMR ' . escapeshellarg($baseline . '...HEAD') . ' --',
            'git -C ' . escapeshellarg($reporoot)
                . ' diff --name-only --diff-filter=ACMR --',
            'git -C ' . escapeshellarg($reporoot)
                . ' diff --cached --name-only --diff-filter=ACMR --',
            'git -C ' . escapeshellarg($reporoot)
                . ' ls-files --others --exclude-standard --',
        ];

        $files = [];
        foreach ($commands as $command) {
            $output = [];
            exec($command . ' 2>&1', $output, $exitcode);
            if ($exitcode !== 0) {
                throw new \RuntimeException(
                    'Unable to inspect Git changes from baseline ' . $baseline . ': ' . implode("\n", $output)
                );
            }

            foreach ($output as $path) {
                $path = $this->normalize_repository_path(trim($path), $pluginprefix);
                if ($path === null || pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }
                $files[$path] = $path;
            }
        }

        ksort($files);
        return array_values($files);
    }

    public function all_php_files(): array {
        return array_keys($this->php_files(['.']));
    }

    private function php_files(array $paths): array {
        $files = [];
        foreach (array_unique($paths) as $path) {
            $absolute = $this->pluginroot . '/' . ltrim($path, '/');
            if (is_file($absolute) && pathinfo($absolute, PATHINFO_EXTENSION) === 'php') {
                $files[$this->relative_path($absolute)] = $absolute;
                continue;
            }
            if (!is_dir($absolute)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileinfo) {
                if (!$fileinfo->isFile() || $fileinfo->getExtension() !== 'php') {
                    continue;
                }
                $absolutepath = $fileinfo->getPathname();
                $files[$this->relative_path($absolutepath)] = $absolutepath;
            }
        }
        ksort($files);
        return $files;
    }

    private function use_statements(string $content): array {
        preg_match_all('/^use\s+([^;]+);/m', $content, $matches);
        return array_values(array_unique(array_map('trim', $matches[1] ?? [])));
    }

    private function legacy_matches(string $content): array {
        $findings = [];
        foreach (self::LEGACY_PATTERNS as $pattern => $label) {
            if (!preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($matches[0] as [$text, $offset]) {
                $key = $label . ':' . $offset;
                $findings[$key] = [
                    'label' => $label,
                    'match' => $text,
                    'line' => $this->line_number($content, $offset),
                ];
            }
        }
        return array_values($findings);
    }

    private function database_calls(string $content): array {
        $calls = [];
        $methods = 'get_record|get_records|get_record_sql|get_records_sql|get_field|get_field_sql|record_exists|record_exists_sql|'
            . implode('|', self::DB_WRITE_METHODS);
        $pattern = '/\$DB->(' . $methods . ')\s*\(\s*[\'\"]?([a-zA-Z0-9_{}]*)/';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        foreach ($matches as $match) {
            $method = $match[1][0];
            $table = trim($match[2][0], '{}');
            $calls[] = [
                'method' => $method,
                'table' => $table,
                'classification' => $this->classify_database_table($table),
                'write' => in_array($method, self::DB_WRITE_METHODS, true),
                'line' => $this->line_number($content, $match[0][1]),
            ];
        }
        return $calls;
    }

    private function classify_layer(string $relativepath): string {
        $relativepath = $this->normalize_relative_path($relativepath);
        foreach (self::UI_PATHS as $uipath) {
            if ($relativepath === $uipath || str_starts_with($relativepath, rtrim($uipath, '/') . '/')) {
                return 'ui';
            }
        }
        if (str_starts_with($relativepath, 'classes/commerce/legacy/')
                || str_contains($relativepath, '/legacy/')
                || str_starts_with($relativepath, 'classes/commerce/migration/')) {
            return 'compatibility';
        }
        if (str_starts_with($relativepath, 'classes/')) {
            return 'service-domain-or-repository';
        }
        return 'other';
    }

    private function setting_category(string $key): string {
        $key = strtolower($key);
        return match (true) {
            str_contains($key, 'shadow') => 'shadow',
            str_contains($key, 'runtime') || str_contains($key, 'native_read') => 'runtime',
            str_contains($key, 'fulfillment') => 'fulfillment',
            str_contains($key, 'checkout') => 'checkout',
            str_contains($key, 'dual_write') => 'dual-write',
            str_contains($key, 'reconciliation') || str_contains($key, 'repair') => 'operations',
            str_contains($key, 'stripe') || str_contains($key, 'alfa') || str_contains($key, 'provider') => 'payments',
            default => 'general',
        };
    }

    private function recommend_flag_status(string $key, bool $read): string {
        $key = strtolower($key);
        if (!$read) {
            return 'candidate-7.99-review';
        }
        if (str_contains($key, 'shadow') || str_contains($key, 'dual_write')) {
            return 'maintenance-only';
        }
        if (str_contains($key, 'legacy_fallback')) {
            return 'safety-fallback-until-7.99';
        }
        if (str_contains($key, 'runtime') || str_contains($key, 'native')
                || str_contains($key, 'checkout') || str_contains($key, 'fulfillment')) {
            return 'keep-and-document';
        }
        return 'review';
    }

    private function looks_like_commerce_flag(string $key): bool {
        $key = strtolower($key);
        foreach (['commerce', 'native', 'shadow', 'runtime', 'fulfillment', 'checkout', 'dual_write', 'reconciliation', 'repair'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function classify_database_table(string $table): string {
        if ($table === '') {
            return 'dynamic';
        }

        foreach (['commerce_', 'subscriptions', 'subscription_', 'digital_', 'payment_request', 'plan_', 'access_scope'] as $prefix) {
            if (str_starts_with($table, $prefix) || $table === $prefix) {
                return 'commerce';
            }
        }

        return 'external';
    }

    private function git_repository_root(): string {
        $output = [];
        exec('git -C ' . escapeshellarg($this->pluginroot) . ' rev-parse --show-toplevel 2>&1', $output, $exitcode);
        if ($exitcode !== 0 || !isset($output[0]) || trim($output[0]) === '') {
            throw new \RuntimeException('Unable to locate the Git repository containing the plugin.');
        }
        return rtrim(str_replace('\\', '/', trim($output[0])), '/');
    }

    private function plugin_repository_prefix(string $reporoot): string {
        $pluginroot = rtrim(str_replace('\\', '/', $this->pluginroot), '/');
        if (!str_starts_with($pluginroot . '/', $reporoot . '/')) {
            throw new \RuntimeException('Plugin root is outside the detected Git repository.');
        }
        return trim(substr($pluginroot, strlen($reporoot)), '/');
    }

    private function normalize_repository_path(string $path, string $pluginprefix): ?string {
        $path = $this->normalize_relative_path($path);
        $pluginprefix = $this->normalize_relative_path($pluginprefix);
        if ($pluginprefix !== '' && $path !== $pluginprefix && !str_starts_with($path, $pluginprefix . '/')) {
            return null;
        }
        if ($pluginprefix !== '') {
            $path = ltrim(substr($path, strlen($pluginprefix)), '/');
        }
        return $this->normalize_relative_path($path);
    }

    private function normalize_relative_path(string $relativepath): string {
        $relativepath = str_replace('\\', '/', trim($relativepath));
        while (str_starts_with($relativepath, './')) {
            $relativepath = substr($relativepath, 2);
        }
        return ltrim($relativepath, '/');
    }

    private function is_commerce_ui_file(string $relativepath): bool {
        return $this->classify_layer($relativepath) === 'ui';
    }

    private function php_syntax_error(string $absolutepath): ?string {
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($absolutepath) . ' 2>&1';
        exec($command, $output, $exitcode);
        return $exitcode === 0 ? null : implode(' ', $output);
    }

    private function read(string $path): string {
        $content = @file_get_contents($path);
        return is_string($content) ? $content : '';
    }

    private function line_number(string $content, int $offset): int {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function relative_path(string $absolutepath): string {
        return $this->normalize_relative_path(substr($absolutepath, strlen($this->pluginroot)));
    }
}
