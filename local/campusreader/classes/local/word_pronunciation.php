<?php
// CampusFR D2.14.2 — bounded neural pronunciation and Reader-only validated MP3 cache.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class word_pronunciation {
    /** Per-request result: safe for the authenticated endpoint to use as a retry hint. */
    private static ?string $requestfailure = null;
    private static int $requesthttp = 0;
    private static string $urlclass = 'none';
    private static string $urlissue = 'none';
    // D2.20.1: bounded diagnostics, never persist lexical content or remote locations.
    private static float $startedat = 0.0;
    private static int $tokenms = 0;
    private static int $providerms = 0;
    private static int $downloadms = 0;
    private static string $awsform = 'none';
    private static string $awsfile = 'none';
    private static string $awsquery = 'none';
    // D2.20.3: at most three consultations with time-based progressive waits, never follow rejected AWS URLs.
    private const AWS_LOOKUP_MAX = 3;
    private const AWS_LOOKUP_DELAYS_US = [900000, 1700000];
    private const AWS_LOOKUP_BUDGET_MS = 6500;
    private static int $lookupcount = 0;
    private static int $awsrejected = 0;
    private static int $providerwaitms = 0;

    private static function elapsed_ms(float $start): int {
        if ($start <= 0.0) { return 0; }
        return max(0, min(30000, (int)round((microtime(true) - $start) * 1000)));
    }

    /** Only fixed-size categories. No hostname, path, query, URL or hash is recorded. */
    private static function note_aws_structure(string $url): void {
        if (self::$urlclass !== 'aws') { return; }
        $p = parse_url($url);
        if (!is_array($p)) { return; }
        $host = strtolower((string)($p['host'] ?? ''));
        self::$awsform = preg_match('/\A(?:s3|s3[.-][a-z0-9-]+)\.amazonaws\.com\z/D', $host) === 1
            ? 's3_service' : (preg_match('/\.s3(?:[.-][a-z0-9-]+)?\.amazonaws\.com\z/D', $host) === 1
                ? 'bucket_s3' : 'aws_other');
        self::$awsfile = preg_match('/\.mp3\z/iD', (string)($p['path'] ?? '')) === 1
            ? 'mp3_suffix' : 'other_suffix';
        self::$awsquery = isset($p['query']) ? 'present' : 'absent';
    }

    private static function safe_metrics(): array {
        return ['total_ms' => self::elapsed_ms(self::$startedat),
            'token_ms' => self::$tokenms, 'provider_ms' => self::$providerms,
            'download_ms' => self::$downloadms,
            'aws_form' => self::$awsform, 'aws_file' => self::$awsfile,
            'aws_query' => self::$awsquery,
            'provider_lookups' => self::$lookupcount, 'aws_rejected' => self::$awsrejected,
            'provider_wait_ms' => self::$providerwaitms];
    }

    /** Retry fresh provider requests only; rejected URLs are never downloaded. */
    public static function retry_after_seconds(): int {
        // AWS/path responses are already retried inside one server request.
        // No Retry-After here: prevent 3 server lookups x 3 client requests amplification.
        if (self::$requestfailure === 'URL_REJECTED' &&
                self::$urlclass === 'aws' && self::$urlissue === 'path') {
            return 0;
        }
        if (in_array(self::$requestfailure, ['WORD_LOCK_BUSY', 'NO_AUDIO_URL'], true)) {
            return 2;
        }
        if (self::$requestfailure === 'DOWNLOAD_HTTP' &&
                in_array(self::$requesthttp, [404, 429, 500, 502, 503, 504], true)) {
            return 2;
        }
        return 0;
    }
    public static function audio(string $word, int $userid, string $voice = 'Lea'): ?string {
        global $CFG;
        self::$requestfailure = null;
        self::$requesthttp = 0;
        self::$urlclass = 'none';
        self::$urlissue = 'none';
        self::$startedat = microtime(true);
        self::$tokenms = self::$providerms = self::$downloadms = 0;
        self::$awsform = self::$awsfile = self::$awsquery = 'none';
        self::$lookupcount = self::$awsrejected = self::$providerwaitms = 0;
        // D2.8.4: explicit Poodll neural voices, never silently substitute a different voice.
        if (!self::supported_voice($voice)) { return null; }
        return self::poodll_audio($word, $userid, $voice);
    }

    public static function supported_voice(string $voice): bool {
        return in_array($voice, ['Lea', 'Remi'], true);
    }

    public static function server_available(): bool {
        return class_exists(\mod_minilesson\utils::class) &&
            class_exists(\mod_minilesson\constants::class) &&
            (string)get_config('mod_minilesson', 'apiuser') !== '' &&
            (string)get_config('mod_minilesson', 'apisecret') !== '' &&
            in_array('Lea', \mod_minilesson\constants::M_NEURALVOICES, true) &&
            in_array('Remi', \mod_minilesson\constants::M_NEURALVOICES, true);
    }

    /** Record only a bounded failure category; never a word, URL, token or secret. */
    private static function note_failure(string $reason, string $voice, int $http = 0, string $contenttype = ''): void {
        global $CFG;
        self::$requestfailure = $reason;
        self::$requesthttp = $http;
        $dir = $CFG->dataroot . '/local_campusreader_d281_poodll';
        if (!is_dir($dir) || !self::supported_voice($voice)) { return; }
        $type = str_contains($contenttype, 'audio') ? 'audio' :
            (str_contains($contenttype, 'json') ? 'json' :
            (str_contains($contenttype, 'html') ? 'html' :
            ($contenttype === '' ? 'unknown' : 'other')));
        $payload = json_encode(['time_utc' => gmdate('c'), 'time_epoch' => microtime(true), 'reason' => $reason,
            'voice' => $voice, 'http' => ($http >= 100 && $http <= 599) ? $http : 0,
            'content_type_class' => $type, 'url_class' => self::$urlclass,
            'url_issue' => self::$urlissue] + self::safe_metrics(), JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) { return; }
        $file = $dir . '/last-tts-failure.json';
        self::write_diagnostic($file, $payload);
        self::append_diagnostic($dir, $payload);
    }

    /** Success is recorded separately: a historical failure must not look like the current state. */
    private static function note_success(string $voice, string $source): void {
        global $CFG;
        $dir = $CFG->dataroot . '/local_campusreader_d281_poodll';
        if (!is_dir($dir) || !self::supported_voice($voice)) { return; }
        $payload = json_encode(['time_utc' => gmdate('c'), 'time_epoch' => microtime(true),
            'status' => 'OK', 'voice' => $voice, 'source' => $source,
            'http' => $source === 'new' ? 200 : 0, 'content_type_class' => 'audio',
            'url_class' => self::$urlclass, 'url_issue' => self::$urlissue] + self::safe_metrics(), JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) { return; }
        $file = $dir . '/last-tts-success.json';
        self::write_diagnostic($file, $payload);
        self::append_diagnostic($dir, $payload);
    }

    /** Atomic summaries and a bounded, private journal. Never record lexical or URL data. */
    private static function write_diagnostic(string $file, string $payload): void {
        $tmp = @tempnam(dirname($file), 'diag-');
        if (!$tmp) { return; }
        @chmod($tmp, 0600);
        if (@file_put_contents($tmp, $payload) !== strlen($payload) || !@rename($tmp, $file)) {
            @unlink($tmp);
        }
    }

    private static function append_diagnostic(string $dir, string $payload): void {
        $file = @fopen($dir . '/recent-tts.jsonl', 'c+');
        if (!$file) { return; }
        @chmod($dir . '/recent-tts.jsonl', 0600);
        try {
            if (!flock($file, LOCK_EX)) { return; }
            $raw = stream_get_contents($file, 16385);
            $lines = is_string($raw) && strlen($raw) <= 16384 ? explode("\n", trim($raw)) : [];
            $lines = array_values(array_filter($lines, static fn($line) => $line !== ''));
            $lines[] = $payload;
            $next = implode("\n", array_slice($lines, -30)) . "\n";
            rewind($file); ftruncate($file, 0); fwrite($file, $next); fflush($file);
        } finally { @flock($file, LOCK_UN); fclose($file); }
    }

    /** Same CloudPoodll REST contract as the collected MiniLesson helper.
     * Do not read/write MiniLesson's shared URL caches: only accepted MP3 bytes persist in Reader.
     */
    private static function request_polly_url(string $token, string $word, string $voice): ?string {
        global $USER;
        if (!\mod_minilesson\utils::can_speak_neural($voice, 'useast1')) { return null; }
        $ssml = '<speak><break time="1000ms"></break>' .
            \mod_minilesson\utils::clean_ssml_chars($word) . '</speak>';
        $params = [
            'wstoken' => $token, 'wsfunction' => 'local_cpapi_fetch_polly_url',
            'moodlewsrestformat' => 'json', 'text' => urlencode($ssml), 'texttype' => 'ssml',
            'voice' => $voice, 'appid' => \mod_minilesson\constants::M_COMPONENT,
            'owner' => hash('md5', (string)$USER->username), 'region' => 'useast1', 'engine' => 'neural',
        ];
        $endpoint = \mod_minilesson\utils::get_cloud_poodll_server() . '/webservice/rest/server.php';
        $response = \mod_minilesson\utils::curl_fetch($endpoint, $params, 'get', 12);
        if (!is_string($response) || strlen($response) > 65536) { self::$requestfailure = 'PROVIDER_RESPONSE'; return null; }
        $payload = json_decode($response);
        if (!is_object($payload) || !isset($payload->returnCode)) { self::$requestfailure = 'PROVIDER_RESPONSE'; return null; }
        if ($payload->returnCode !== 0) { self::$requestfailure = 'PROVIDER_REJECTED'; return null; }
        if (!is_string($payload->returnMessage ?? null) || $payload->returnMessage === '') { return null; }
        return $payload->returnMessage;
    }

    /** Reuse MiniLesson credentials, Reader per-word locking, quota and durable MP3 cache. */
    private static function poodll_audio(string $word, int $userid, string $voice): ?string {
        global $CFG;
        if (!class_exists(\mod_minilesson\utils::class) ||
                !class_exists(\mod_minilesson\constants::class)) { return null; }
        $apiuser = (string)get_config('mod_minilesson', 'apiuser');
        $apisecret = (string)get_config('mod_minilesson', 'apisecret');
        if ($apiuser === '' || $apisecret === '') { return null; }
        if (!in_array($voice, \mod_minilesson\constants::M_NEURALVOICES, true)) { return null; }
        $dir = $CFG->dataroot . '/local_campusreader_d281_poodll';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) { return null; }
        @chmod($dir, 0700);
        $hash = hash('sha256', 'poodll-v1|useast1|' . $voice . '|' . $word);
        $cachefile = $dir . '/' . $hash . '.mp3';
        $lock = @fopen($dir . '/' . $hash . '.lock', 'c');
        if (!$lock) { return null; }
        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) { self::note_failure('WORD_LOCK_BUSY', $voice); return null; }
            if (is_file($cachefile) && filesize($cachefile) >= 100) {
                $cached = @file_get_contents($cachefile);
                if (is_string($cached) && self::is_mp3($cached)) { self::note_success($voice, 'cache'); return $cached; }
            }
            // Charge quota per uncached provider attempt, never per successful cache hit.
            if (!self::allow_generation($dir, $userid)) { self::note_failure('GENERATION_QUOTA', $voice); return null; }
            $t0 = microtime(true);
            $token = \mod_minilesson\utils::fetch_token($apiuser, $apisecret, false, 8);
            self::$tokenms = self::elapsed_ms($t0);
            if (!is_string($token) || $token === '') { self::note_failure('TOKEN_FAILED', $voice); return null; }
            // CloudPoodll sometimes returns a signed AWS staging URL before a final pollyfile.
            // It must NEVER be fetched, cached or allowed through the SSRF gate.
            // Retry only this specific AWS/path result using the same authenticated API,
            // behind the existing per-word lock and once-per-request generation quota.
            // All other rejection types remain fail-closed with no internal retry.
            $providerstart = microtime(true);
            for ($lookup = 1; $lookup <= self::AWS_LOOKUP_MAX; $lookup++) {
                self::$lookupcount = $lookup;
                $url = self::request_polly_url($token, $word, $voice);
                self::$providerms = self::elapsed_ms($providerstart);
                if (!is_string($url) || $url === '') {
                    self::note_failure(self::$requestfailure ?? 'NO_AUDIO_URL', $voice); return null;
                }
                [self::$urlclass, self::$urlissue] = self::url_diagnostic($url);
                self::note_aws_structure($url);
                if (self::$urlissue === 'none') {
                    // A Polly presigned link must be for the requested neural voice.
                    // Every other family retains the exact existing URL policy.
                    if (strtolower((string)parse_url($url, PHP_URL_HOST)) === 'polly.us-east-1.amazonaws.com') {
                        $params = [];
                        parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
                        if (($params['VoiceId'] ?? '') !== $voice) {
                            self::note_failure('URL_REJECTED', $voice); return null;
                        }
                    }
                    break; // Only validated URL may proceed to the pinned-DNS HTTPS downloader.
                }
                // An invalid/expired Polly presigned URL must fail closed immediately;
                // do not amplify a malformed AWS request with two provider retries.
                if (self::$urlclass === 'aws' && self::$urlissue === 'path' &&
                        strtolower((string)parse_url($url, PHP_URL_HOST)) !== 'polly.us-east-1.amazonaws.com') {
                    ++self::$awsrejected;
                    if ($lookup < self::AWS_LOOKUP_MAX) {
                        $pauseus = self::AWS_LOOKUP_DELAYS_US[$lookup - 1];
                        // Do not initiate another provider consultation when the next wait
                        // already consumes the server-side polling budget. Individual
                        // CloudPoodll network calls retain their own existing timeout.
                        if (self::$providerms + intdiv($pauseus, 1000) <= self::AWS_LOOKUP_BUDGET_MS) {
                            usleep($pauseus);
                            self::$providerwaitms += intdiv($pauseus, 1000);
                            continue;
                        }
                    }
                }
                self::note_failure('URL_REJECTED', $voice); return null;
            }
            if (!function_exists('curl_init')) { self::note_failure('CURL_UNAVAILABLE', $voice); return null; }
            $hostname = strtolower((string)parse_url($url, PHP_URL_HOST));
            $ip = self::public_address($hostname);
            if ($ip === null) { self::note_failure('DNS_REJECTED', $voice); return null; }
            $resolve = $hostname . ':443:' . (str_contains($ip, ':') ? '[' . $ip . ']' : $ip);
            $bytes = '';
            $curl = curl_init($url);
            if ($curl === false) { self::note_failure('DOWNLOAD_CURL_ERROR', $voice); return null; }
            $configured = curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => false, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROXY => '', CURLOPT_RESOLVE => [$resolve],
                CURLOPT_FRESH_CONNECT => true, CURLOPT_FORBID_REUSE => true,
                CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 12,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPGET => true,
                CURLOPT_USERAGENT => 'CampusFR Reader D2.14.2',
                CURLOPT_WRITEFUNCTION => static function($handle, string $chunk) use (&$bytes): int {
                    if (strlen($bytes) + strlen($chunk) > 800000) { return 0; }
                    $bytes .= $chunk;
                    return strlen($chunk);
                },
            ]);
            $configured = $configured && (defined('CURLOPT_PROTOCOLS_STR')
                ? curl_setopt($curl, CURLOPT_PROTOCOLS_STR, 'https')
                : curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS));
            if (!$configured) { curl_close($curl); self::note_failure('CURL_CONFIGURATION', $voice); return null; }
            $t0 = microtime(true);
            $ok = curl_exec($curl);
            self::$downloadms = self::elapsed_ms($t0);
            $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $ct = strtolower((string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
            curl_close($curl);
            if ($ok !== true) { self::note_failure('DOWNLOAD_CURL_ERROR', $voice, $status, $ct); return null; }
            if ($status !== 200) { self::note_failure('DOWNLOAD_HTTP', $voice, $status, $ct); return null; }
            if (!self::is_mp3($bytes)) { self::note_failure('INVALID_MP3', $voice, $status, $ct); return null; }
            if (!($ct === '' || str_contains($ct, 'audio/') || str_contains($ct, 'octet-stream'))) {
                self::note_failure('INVALID_CONTENT_TYPE', $voice, $status, $ct); return null;
            }
            $tmp = @tempnam($dir, 'new-');
            if (!$tmp) { self::note_failure('CACHE_TEMP_FAILED', $voice); return null; }
            if (file_put_contents($tmp, $bytes) !== strlen($bytes)) { @unlink($tmp); self::note_failure('CACHE_WRITE_FAILED', $voice); return null; }
            @chmod($tmp, 0600);
            if (!@rename($tmp, $cachefile)) { @unlink($tmp); self::note_failure('CACHE_RENAME_FAILED', $voice); return null; }
            self::note_success($voice, 'new');
            return $bytes;
        } catch (\Throwable $e) {
            // Never disclose remote payload, URL, token or word.
            self::note_failure('POODLL_EXCEPTION', $voice);
            return null;
        } finally {
            @flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function is_mp3(string $bytes): bool {
        return strlen($bytes) >= 100 && strlen($bytes) <= 800000 &&
            (str_starts_with($bytes, 'ID3') || str_starts_with($bytes, "\xff\xfb") ||
                str_starts_with($bytes, "\xff\xf3") || str_starts_with($bytes, "\xff\xf2"));
    }

    public static function safe_poodll_url(string $url): bool {
        return self::url_diagnostic($url)[1] === 'none';
    }

    /** D2.20.5: only the exact AWS Polly speech operation from CloudPoodll.
     * The signature is verified by AWS over HTTPS, NOT by this application.
     * Reject malformed/duplicate parameters, other AWS services and stale signatures.
     * Never log, transform, cache or print a presigned URL (may contain SSML + credentials).
     */
    private static function valid_presigned_polly(array $parsed): bool {
        if (($parsed['path'] ?? '') !== '/v1/speech' || !isset($parsed['query'])) { return false; }
        $rawquery = (string)$parsed['query'];
        if ($rawquery === '' || strlen($rawquery) > 7900 ||
                preg_match('/%(?![0-9a-fA-F]{2})/', $rawquery)) { return false; }
        $allowed = array_fill_keys([
            'X-Amz-Algorithm', 'X-Amz-Credential', 'X-Amz-Date', 'X-Amz-Expires',
            'X-Amz-SignedHeaders', 'X-Amz-Signature', 'X-Amz-Security-Token',
            'OutputFormat', 'VoiceId', 'Engine', 'TextType', 'Text',
            'SampleRate', 'LanguageCode',
        ], true);
        $q = [];
        foreach (explode('&', $rawquery) as $part) {
            if ($part === '' || !str_contains($part, '=')) { return false; }
            [$key, $val] = explode('=', $part, 2);
            $key = rawurldecode($key);
            $val = rawurldecode($val);
            if (!isset($allowed[$key]) || isset($q[$key]) || strlen($val) > 4096) { return false; }
            $q[$key] = $val;
        }
        if (($q['X-Amz-Algorithm'] ?? '') !== 'AWS4-HMAC-SHA256' ||
                ($q['X-Amz-SignedHeaders'] ?? '') !== 'host' ||
                preg_match('/\A[a-f0-9]{64}\z/iD', $q['X-Amz-Signature'] ?? '') !== 1 ||
                ($q['OutputFormat'] ?? '') !== 'mp3' ||
                !in_array($q['VoiceId'] ?? '', ['Lea', 'Remi'], true) ||
                ($q['Engine'] ?? '') !== 'neural' ||
                ($q['TextType'] ?? '') !== 'ssml' ||
                !isset($q['Text']) || $q['Text'] === '') {
            return false;
        }
        if (isset($q['LanguageCode']) && $q['LanguageCode'] !== 'fr-FR') { return false; }
        if (isset($q['SampleRate']) && !in_array($q['SampleRate'],
                ['8000', '16000', '22050', '24000', '44100', '48000'], true)) { return false; }
        $date = (string)($q['X-Amz-Date'] ?? '');
        $expires = (string)($q['X-Amz-Expires'] ?? '');
        if (preg_match('/\A[0-9]{8}T[0-9]{6}Z\z/D', $date) !== 1 ||
                preg_match('/\A[0-9]{1,4}\z/D', $expires) !== 1 ||
                (int)$expires < 1 || (int)$expires > 3600) { return false; }
        $dateobj = \DateTimeImmutable::createFromFormat('!Ymd\THis\Z', $date, new \DateTimeZone('UTC'));
        if ($dateobj === false || $dateobj->format('Ymd\THis\Z') !== $date) { return false; }
        $now = time(); $signedat = $dateobj->getTimestamp();
        if ($signedat > $now + 300 || $signedat + (int)$expires + 60 < $now) { return false; }
        $credential = (string)($q['X-Amz-Credential'] ?? '');
        if (preg_match('#\A[A-Za-z0-9_+\-]{8,128}/' . substr($date, 0, 8) .
                '/us-east-1/polly/aws4_request\z#D', $credential) !== 1) { return false; }
        return true;
    }

    /** Only fixed categories, never the actual URL, hostname, path or query. */
    public static function url_diagnostic(string $url): array {
        if ($url === '' || strlen($url) > 8192 || preg_match('/[\x00-\x20\x7f]/', $url) || str_contains($url, "\\")) {
            return ['invalid', 'syntax'];
        }
        $parsed = parse_url($url);
        if (!is_array($parsed)) { return ['invalid', 'syntax']; }
        $hostname = strtolower((string)($parsed['host'] ?? ''));
        $class = match (true) {
            $hostname === 'pollyfile.poodll.net' => 'pollyfile',
            preg_match('/\A[a-z0-9-]+(?:\.[a-z0-9-]+)*\.poodll\.com\z/D', $hostname) === 1 => 'poodll',
            preg_match('/\A[a-z0-9-]+(?:\.[a-z0-9-]+)*\.amazonaws\.com\z/D', $hostname) === 1 => 'aws',
            preg_match('/\A[a-z0-9-]+(?:\.[a-z0-9-]+)*\.cloudfront\.net\z/D', $hostname) === 1 => 'cloudfront',
            default => 'other',
        };
        if (strtolower((string)($parsed['scheme'] ?? '')) !== 'https') { return [$class, 'scheme']; }
        if (isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['port']) || isset($parsed['fragment'])) {
            return [$class, 'authority_or_fragment'];
        }
        if ($class === 'other') { return [$class, 'host']; }
        // Explicit allowlist: the only AWS API operation permitted is Polly us-east-1
        // signed neural MP3 synthesis; all other AWS destinations stay blocked.
        if ($hostname === 'polly.us-east-1.amazonaws.com') {
            return [$class, self::valid_presigned_polly($parsed) ? 'none' : 'path'];
        }
        $path = (string)($parsed['path'] ?? '');
        if ($class === 'pollyfile') {
            // Retain the exact-host policy; do not admit signed URLs or encoded paths without evidence.
            if (array_key_exists('query', $parsed)) { return [$class, 'query']; }
            if (strlen($path) > 2048 || str_contains($path, '..') ||
                    preg_match('#\A/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]+\.mp3\z#iD', $path) !== 1) {
                return [$class, 'path'];
            }
        } elseif (!str_contains(strtolower($path), 'pollyfile')) {
            // Legacy approved families retain the previous path restriction.
            return [$class, 'path'];
        }
        return [$class, 'none'];
    }

    /** Validate and pin a public address so DNS cannot change the destination after validation. */
    private static function is_public_address(string $ip): bool {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) { return false; }
        $packed = inet_pton($ip);
        if ($packed === false) { return false; }
        if (strlen($packed) === 4) { return ord($packed[0]) < 224; }
        // Native global unicast only; reject mapped, translation, multicast and local addresses.
        return strlen($packed) === 16 && (ord($packed[0]) & 0xe0) === 0x20 &&
            !str_starts_with(strtolower($ip), '2002:');
    }

    private static function public_address(string $hostname): ?string {
        $addresses = @gethostbynamel($hostname);
        if (!is_array($addresses) || !$addresses) {
            $records = @dns_get_record($hostname, DNS_AAAA);
            $addresses = [];
            foreach (is_array($records) ? $records : [] as $record) {
                if (isset($record['ipv6'])) { $addresses[] = $record['ipv6']; }
            }
        }
        if (!$addresses) { return null; }
        foreach ($addresses as $ip) {
            if (!is_string($ip) || !self::is_public_address($ip)) { return null; }
        }
        return $addresses[0];
    }

    private static function allow_generation(string $dir, int $userid): bool {
        $file = @fopen($dir . '/quota-' . $userid . '.json', 'c+');
        if (!$file || !flock($file, LOCK_EX)) { if ($file) fclose($file); return false; }
        try {
            $now = time();
            $raw = stream_get_contents($file);
            $data = json_decode($raw ?: '{}', true);
            if (!is_array($data)) { $data = []; }
            $minute = $now - (int)($data['minute'] ?? 0) < 60 ? (int)($data['minuteCount'] ?? 0) : 0;
            $daily = $now - (int)($data['day'] ?? 0) < 86400 ? (int)($data['dayCount'] ?? 0) : 0;
            if ($minute >= 15 || $daily >= 150) { return false; }
            $update = [
                'minute' => $minute ? (int)$data['minute'] : $now,
                'minuteCount' => $minute + 1,
                'day' => $daily ? (int)$data['day'] : $now,
                'dayCount' => $daily + 1,
            ];
            rewind($file); ftruncate($file, 0);
            fwrite($file, json_encode($update)); fflush($file);
            return true;
        } finally { flock($file, LOCK_UN); fclose($file); }
    }
}
