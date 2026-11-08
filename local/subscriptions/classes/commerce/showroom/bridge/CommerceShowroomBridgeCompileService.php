<?php
// CampusFR X2.3A: compile draft, never deploy, via an isolated OCI runtime only.
declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

/** Validates the authenticated CRM job and schedules an isolated draft build. */
final class CommerceShowroomBridgeCompileService {
    public const STATUS_FILE = 'campusfr-compile-status.json';
    public const MAX_JOB_AGE = 7200;
    private const MAX_LOG_BYTES = 180000;

    public static function valid_key(string $key): bool {
        return preg_match('/\A[a-f0-9]{32}\z/D', $key) === 1;
    }

    public static function valid_id(string $id): bool {
        return preg_match('/\A[a-z][a-z0-9_]{2,47}\z/D', $id) === 1;
    }

    public static function job_dir(int $userid, string $key): string {
        if ($userid < 1 || !self::valid_key($key)) {
            throw new \invalid_parameter_exception('Identifiant du travail invalide.');
        }
        return make_temp_directory('local_subscriptions/lovable_source_imports') . '/' . $userid . '_' . $key;
    }

    /** @param array<string,mixed> $sessionjob */
    public static function verify_session(array $sessionjob, int $userid, string $key): array {
        if (!self::valid_key($key)
                || !hash_equals((string)($sessionjob['key'] ?? ''), $key)
                || (int)($sessionjob['userid'] ?? 0) !== $userid
                || !self::valid_id((string)($sessionjob['id'] ?? ''))
                || (int)($sessionjob['created'] ?? 0) > time()
                || time() - (int)($sessionjob['created'] ?? 0) > self::MAX_JOB_AGE) {
            throw new \invalid_parameter_exception('Projet expiré ou non autorisé.');
        }
        $dir = self::job_dir($userid, $key);
        $archive = $dir . '/campusfr-lovable-' . $sessionjob['id'] . '-a-revoir.zip';
        if (!is_dir($dir) || is_link($dir) || !is_file($archive) || is_link($archive)) {
            throw new \invalid_parameter_exception('Projet préparé introuvable.');
        }
        return ['dir' => $dir, 'archive' => $archive, 'id' => $sessionjob['id']];
    }

    /** The worker is a fixed, root-owned, no-argument sudo target; www-data cannot invoke Podman. */
    public static function availability(): array {
        $script = dirname(__DIR__, 4) . '/cli/lovable_builder/compile_preview.py';
        $worker = '/usr/local/libexec/campusfr-lovable-build';
        if (!is_file($script) || !is_readable($script)) {
            return ['available' => false, 'message' => 'Le moteur de préparation est absent.'];
        }
        if (!is_executable('/usr/bin/sudo') || !is_executable('/usr/bin/timeout')
                || !is_executable($worker)
                || !is_dir('/var/spool/campusfr-lovable/inbox')
                || !is_dir('/var/spool/campusfr-lovable/outbox')
                || !is_writable('/var/spool/campusfr-lovable/inbox')
                || !is_readable('/var/spool/campusfr-lovable/outbox')) {
            return ['available' => false, 'message' => 'Le worker isolé n’est pas encore raccordé au CRM.'];
        }
        if (!function_exists('proc_open')) {
            return ['available' => false, 'message' => 'L’exécution des tâches est désactivée.'];
        }
        return ['available' => true, 'message' => 'Moteur de compilation isolé disponible. '
            . 'Le build produit exclusivement un aperçu non publiable.'];
    }

    /** @return array<string,mixed>|null */
    public static function get_status(string $dir): ?array {
        $file = $dir . '/' . self::STATUS_FILE;
        if (!is_file($file) || is_link($file) || filesize($file) > 12000) {
            return null;
        }
        $value = json_decode((string)file_get_contents($file), true);
        return is_array($value) && in_array(($value['state'] ?? ''),
            ['queued', 'running', 'ready', 'error'], true) ? $value : null;
    }

    /** @param array<string,mixed> $status */
    public static function write_status(string $dir, array $status): void {
        if (!is_dir($dir) || is_link($dir)) {
            throw new \RuntimeException('Dossier de travail indisponible.');
        }
        $path = $dir . '/' . self::STATUS_FILE;
        if (is_link($path)) {
            throw new \RuntimeException('Fichier de statut dangereux.');
        }
        $temp = $dir . '/.compile-' . bin2hex(random_bytes(8));
        $json = json_encode($status, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($temp, $json, LOCK_EX) === false) {
            throw new \RuntimeException('Impossible d’écrire le journal de compilation.');
        }
        @chmod($temp, 0600);
        // Moodle cron may run as root; keep the status readable by PHP-FPM (www-data).
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            @chown($temp, 'www-data');
        }
        if (!rename($temp, $path)) {
            @unlink($temp);
            throw new \RuntimeException('Impossible de finaliser le statut de compilation.');
        }
    }

    /** Queue an adhoc Moodle task; no untrusted code runs inside the HTTP request. */
    public static function queue(int $userid, string $key, string $id, string $dir): void {
        if (!self::valid_key($key) || !self::valid_id($id) || $dir !== self::job_dir($userid, $key)) {
            throw new \invalid_parameter_exception('Paramètres de compilation invalides.');
        }
        if (self::get_status($dir) !== null) {
            throw new \invalid_parameter_exception('Compilation déjà demandée pour ce projet.');
        }
        $availability = self::availability();
        if (!$availability['available']) {
            throw new \RuntimeException($availability['message']);
        }
        // Exclusive creation also prevents a double-click from queuing two builds.
        $lockpath = $dir . '/campusfr-compile.lock';
        $lock = @fopen($lockpath, 'x');
        if ($lock === false) {
            throw new \invalid_parameter_exception('Compilation déjà en cours ou déjà demandée.');
        }
        fclose($lock);
        @chmod($lockpath, 0600);
        self::write_status($dir, [
            'state' => 'queued', 'started' => time(), 'updated' => time(),
            'userid' => $userid, 'designId' => $id, 'message' => 'En attente du traitement Moodle.',
        ]);
        try {
            $task = new \local_subscriptions\task\lovable_build_task();
            $task->set_custom_data(['userid' => $userid, 'key' => $key, 'id' => $id]);
            \core\task\manager::queue_adhoc_task($task);
        } catch (\Throwable $e) {
            self::write_status($dir, ['state' => 'error', 'userid' => $userid, 'designId' => $id,
                'updated' => time(), 'message' => 'Impossible de mettre la compilation en file d’attente.']);
            throw $e;
        }
    }

    /**
     * Dispatch a single source archive through a group-readable inbox to the isolated account.
     * No untrusted JavaScript runs in PHP or as www-data. The sudo rule authorizes only a
     * root-owned, no-argument worker; the project identifiers go over standard input.
     */
    public static function run(int $userid, string $key, string $id): void {
        if (!self::valid_key($key) || !self::valid_id($id) || $userid < 1) {
            throw new \invalid_parameter_exception('Tâche de compilation invalide.');
        }
        // Running this as root is deliberately refused: the expected DEV cron is www-data.
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            throw new \RuntimeException('Refus : la tâche doit être exécutée par le cron www-data.');
        }
        $dir = self::job_dir($userid, $key);
        $status = self::get_status($dir);
        if (!$status || ($status['state'] ?? '') !== 'queued'
                || (int)($status['userid'] ?? 0) !== $userid
                || (string)($status['designId'] ?? '') !== $id
                || time() - (int)($status['started'] ?? 0) > self::MAX_JOB_AGE) {
            throw new \RuntimeException('Tâche expirée ou non conforme.');
        }
        $archive = $dir . '/campusfr-lovable-' . $id . '-a-revoir.zip';
        if (!is_file($archive) || is_link($archive) || filesize($archive) > 60 * 1024 * 1024) {
            throw new \RuntimeException('Archive source absente, dangereuse ou trop volumineuse.');
        }
        $availability = self::availability();
        if (!$availability['available']) {
            throw new \RuntimeException($availability['message']);
        }
        $stem = $userid . '_' . $key;
        $inbox = '/var/spool/campusfr-lovable/inbox/' . $stem . '.zip';
        $outbox = '/var/spool/campusfr-lovable/outbox/' . $stem . '.zip';
        $output = $dir . '/campusfr-lovable-' . $id . '-apercu-non-publiable.zip';
        $logfile = $dir . '/campusfr-build.log';
        $code = 1;
        $problem = '';
        $staging = '/var/spool/campusfr-lovable/inbox/.' . $stem . '.part';
        try {
            if (file_exists($inbox) || is_link($inbox) || file_exists($staging) || is_link($staging)
                    || file_exists($outbox) || is_link($outbox)) {
                throw new \RuntimeException('Une compilation existe déjà pour cet identifiant.');
            }
            $input = @fopen($archive, 'rb');
            $dest = @fopen($staging, 'xb');
            if ($input === false || $dest === false) {
                if (is_resource($input)) { fclose($input); }
                if (is_resource($dest)) { fclose($dest); }
                throw new \RuntimeException('Impossible de préparer le transfert au worker.');
            }
            $copied = stream_copy_to_stream($input, $dest, 60 * 1024 * 1024 + 1);
            fflush($dest);
            fclose($input);
            fclose($dest);
            if ($copied === false || $copied !== filesize($archive) || $copied > 60 * 1024 * 1024
                    || !@chmod($staging, 0640) || !@rename($staging, $inbox)) {
                throw new \RuntimeException('Transfert du ZIP au worker incomplet.');
            }
            self::write_status($dir, array_merge($status, [
                'state' => 'running', 'updated' => time(),
                'message' => 'Compilation isolée sur le compte campusfr-builder.',
            ]));
            // sudoers explicitly allows this command only, with zero arguments.
            $runner = ['/usr/bin/timeout', '210', '/usr/bin/sudo', '-n', '-u',
                'campusfr-builder', '--', '/usr/local/libexec/campusfr-lovable-build'];
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $logfile, 'w'],
                2 => ['file', $logfile . '.err', 'w'],
            ];
            $proc = @proc_open($runner, $descriptors, $pipes, $dir, null, ['bypass_shell' => true]);
            if (!is_resource($proc)) {
                throw new \RuntimeException('Le worker sécurisé n’a pas démarré.');
            }
            $request = json_encode(['userid' => $userid, 'key' => $key, 'id' => $id], JSON_THROW_ON_ERROR) . "\n";
            fwrite($pipes[0], $request);
            fclose($pipes[0]);
            $code = proc_close($proc);
            if ($code !== 0) {
                $problem = $code === 124 ? 'Délai maximal de compilation atteint.' :
                    'Compilation interrompue. Consulte le journal technique.';
            } else if (!is_file($outbox) || is_link($outbox) || filesize($outbox) > 320 * 1024 * 1024) {
                $problem = 'Le worker n’a pas produit de ZIP d’aperçu valide.';
            } else if (!@copy($outbox, $output)) {
                $problem = 'Impossible de conserver le ZIP d’aperçu dans Moodle.';
            } else {
                @chmod($output, 0600);
            }
        } catch (\Throwable $exception) {
            $problem = $exception->getMessage();
        } finally {
            // Staging belongs to www-data, not the worker; never leave duplicate source ZIPs.
            if (is_file($staging) && !is_link($staging)) { @unlink($staging); }
            if (is_file($inbox) && !is_link($inbox)) { @unlink($inbox); }
        }
        self::write_status($dir, [
            'state' => ($code === 0 && $problem === '') ? 'ready' : 'error',
            'userid' => $userid, 'designId' => $id,
            'started' => (int)$status['started'], 'updated' => time(),
            'message' => $problem !== '' ? $problem : 'Aperçu compilé. Revue obligatoire avant intégration.',
        ]);
    }

    public static function read_log(string $dir): string {
        $result = '';
        foreach (['campusfr-build.log', 'campusfr-build.log.err'] as $filename) {
            $path = $dir . '/' . $filename;
            if (is_file($path) && !is_link($path)) {
                $content = (string)file_get_contents($path, false, null, 0, self::MAX_LOG_BYTES);
                $result .= $filename . ":\n" . $content . "\n";
            }
        }
        return mb_substr($result, 0, self::MAX_LOG_BYTES);
    }
}
