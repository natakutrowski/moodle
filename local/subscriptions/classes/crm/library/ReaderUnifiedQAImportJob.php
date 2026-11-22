<?php
/** CampusFR D2.17.3.2 - private DEV QA import jobs, no schema changes. */
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

final class ReaderUnifiedQAImportJob {
    private const MAX_BYTES = 536870912; // QA pilot: max 512 MiB per ZIP.

    public static function ensure_dev(): void {
        global $CFG;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'fpm-fcgi' && PHP_SAPI !== 'apache2handler' && PHP_SAPI !== 'cgi-fcgi') {
            throw new \RuntimeException('SAPI Moodle non reconnue');
        }
        if (realpath((string)$CFG->dirroot) !== '/var/www/moodle-dev' ||
            realpath('/var/www/moodle-dev') !== '/var/www/moodle-dev') {
            throw new \RuntimeException('Moodle DEV exclusivement - aucune exécution PROD');
        }
    }
    private static function root(): string {
        self::ensure_dev();
        $root = make_temp_directory('campusfr-d21732-qa-jobs');
        if (!is_dir($root) || is_link($root) || realpath($root) !== $root) {
            throw new \RuntimeException('Dossier privé des jobs introuvable');
        }
        return $root;
    }
    private static function path(string $id): string {
        if (preg_match('/\A[a-f0-9]{32}\z/D', $id) !== 1) {
            throw new \InvalidArgumentException('Identifiant de job incorrect');
        }
        return self::root() . '/' . $id;
    }
    /** Strictly one of the worker-created job directories. */
    public static function load(string $id): array {
        $dir = self::path($id);
        if (!is_dir($dir) || is_link($dir) || realpath($dir) !== $dir) {
            throw new \DomainException('Job inexistant ou non canonique');
        }
        $file = $dir . '/state.json';
        if (!is_file($file) || is_link($file) || filesize($file) < 20 || filesize($file) > 16384) {
            throw new \DomainException('État du job absent ou incorrect');
        }
        $s = json_decode((string)file_get_contents($file), true, 20, JSON_THROW_ON_ERROR);
        if (!is_array($s) || ($s['schema'] ?? '') !== 'cfr.d21732.job.v1' || ($s['id'] ?? '') !== $id ||
            !in_array($s['status'] ?? '', ['queued','running','partial','resume_queued','complete','failed'], true)) {
            throw new \DomainException('État du job incompatible');
        }
        return $s;
    }
    public static function status_for_user(string $id, int $userid): array {
        $state = self::load($id);
        if ((int)($state['actor'] ?? 0) !== $userid) {
            throw new \DomainException('Ce job ne correspond pas au compte connecté');
        }
        // A killed cron task is not automatically retried: require administrator inspection.
        if ($state['status'] === 'running' && time() - (int)($state['updated'] ?? $state['at']) > 1800) {
            $state['message'] = 'Traitement anormalement long : vérifier les tâches Moodle DEV, ne pas relancer.';
        }
        // Never return server paths or raw exception traces to the browser.
        foreach (['collection', 'anthology', 'revision', 'course', 'count'] as $n) {
            $state[$n] = (int)($state[$n] ?? 0);
        }
        return array_intersect_key($state, array_flip([
            'id','status','phase','at','updated','actor','course','collection','anthology',
            'revision','count','message','calendar_slug','anthology_slug','attempts',
        ]));
    }
    /** Atomic job state publication. Caller holds the job lock during execution. */
    public static function save(string $id, array $s): void {
        $dir = self::path($id);
        if (!is_dir($dir) || is_link($dir)) { throw new \RuntimeException('Dossier job perdu'); }
        $s['updated'] = time();
        $json = json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
        if (strlen($json) > 16384) { throw new \RuntimeException('État job trop grand'); }
        $tmp = $dir . '/.' . bin2hex(random_bytes(8)) . '.state.tmp';
        $f = @fopen($tmp, 'xb');
        if (!is_resource($f)) { throw new \RuntimeException('Écriture d’état refusée'); }
        @chmod($tmp, 0600);
        try {
            if (fwrite($f, $json) !== strlen($json)) { throw new \RuntimeException('État incomplet'); }
            fflush($f);
        } finally { fclose($f); }
        if (!rename($tmp, $dir . '/state.json')) {
            @unlink($tmp); throw new \RuntimeException('Publication de l’état impossible');
        }
    }
    private static function copy_archive(string $from, string $to, string $sha): void {
        if (!is_file($from) || is_link($from) || !is_string($sha) ||
            preg_match('/\A[a-f0-9]{64}\z/D', $sha) !== 1 ||
            filesize($from) < 100 || filesize($from) > self::MAX_BYTES) {
            throw new \DomainException('Archive source non autorisée');
        }
        $in = fopen($from, 'rb'); $out = fopen($to, 'xb');
        if (!is_resource($in) || !is_resource($out)) {
            if (is_resource($in)) { fclose($in); }
            if (is_resource($out)) { fclose($out); }
            throw new \RuntimeException('Copie de ZIP privée impossible');
        }
        chmod($to, 0600);
        try {
            $ctx = hash_init('sha256'); $n = 0;
            while (!feof($in)) {
                $buf = fread($in, 1048576);
                if ($buf === false || ($buf === '' && !feof($in))) {
                    throw new \RuntimeException('Flux ZIP interrompu');
                }
                if ($buf === '') { break; }
                $n += strlen($buf);
                if ($n > self::MAX_BYTES || fwrite($out, $buf) !== strlen($buf)) {
                    throw new \RuntimeException('ZIP incomplet ou excessif');
                }
                hash_update($ctx, $buf);
            }
            if ($n < 100 || !hash_equals($sha, hash_final($ctx))) {
                throw new \RuntimeException('ZIP modifié pendant la préparation');
            }
        } finally { fclose($in); fclose($out); }
    }
    public static function enqueue(array $stage, array $plan, int $actor): string {
        self::ensure_dev();
        if (empty($plan['ready']) || $actor < 2 ||
            !isset($stage['files']['calendar'], $stage['files']['anthology'],
                $stage['sha']['calendar'], $stage['sha']['anthology'])) {
            throw new \DomainException('Plan non validé');
        }
        $total = 0;
        foreach (['calendar','anthology'] as $kind) {
            $size = @filesize((string)$stage['files'][$kind]);
            if ($size === false || $size < 100 || $size > self::MAX_BYTES) {
                throw new \DomainException('Archive QA hors limite (512 Mio par ZIP)');
            }
            $total += $size;
        }
        $free = @disk_free_space(self::root());
        if ($free === false || $free < $total + 134217728) {
            throw new \DomainException('Espace disque DEV insuffisant pour conserver les ZIP privés');
        }
        $id = bin2hex(random_bytes(16)); $dir = self::path($id);
        if (!@mkdir($dir, 0700)) { throw new \RuntimeException('Création privée du job impossible'); }
        chmod($dir, 0700);
        try {
            foreach (['calendar','anthology'] as $kind) {
                self::copy_archive($stage['files'][$kind], $dir . '/' . $kind . '.zip',
                    (string)$stage['sha'][$kind]);
            }
            $state = [
                'schema' => 'cfr.d21732.job.v1', 'id' => $id,
                'status' => 'queued', 'phase' => 'waiting_for_cron', 'at' => time(),
                'actor' => $actor, 'course' => (int)$plan['courseid'],
                'calendar_slug' => (string)$plan['calendarslug'],
                'anthology_slug' => (string)$plan['anthologyslug'],
                'sha' => $stage['sha'], 'count' => (int)$plan['stories'],
                'collection' => 0, 'anthology' => 0, 'revision' => 0,
                'attempts' => 0, 'message' => 'En attente du traitement Moodle.',
            ];
            self::save($id, $state);
            $task = new \local_subscriptions\task\reader_unified_qa_import();
            $task->set_component('local_subscriptions');
            $task->set_custom_data((object)['jobid' => $id]);
            \core\task\manager::queue_adhoc_task($task);
            return $id;
        } catch (\Throwable $e) {
            foreach (['calendar.zip', 'anthology.zip', 'state.json'] as $filename) {
                if (is_file($dir . '/' . $filename) && !is_link($dir . '/' . $filename)) {
                    @unlink($dir . '/' . $filename);
                }
            }
            @rmdir($dir);
            throw $e;
        }
    }
    public static function queue_resume(string $id, int $actor): void {
        $state = self::load($id);
        if ((int)$state['actor'] !== $actor || $state['status'] !== 'partial' ||
            (int)$state['collection'] <= 0 || (int)$state['anthology'] !== 0 ||
            (int)$state['attempts'] >= 2) {
            throw new \DomainException('Reprise impossible ou déjà effectuée');
        }
        $state['status'] = 'resume_queued';
        $state['phase'] = 'waiting_for_cron';
        $state['message'] = 'Reprise du recueil en attente de traitement.';
        self::save($id, $state);
        try {
            $task = new \local_subscriptions\task\reader_unified_qa_import();
            $task->set_component('local_subscriptions');
            $task->set_custom_data((object)['jobid' => $id]);
            \core\task\manager::queue_adhoc_task($task);
        } catch (\Throwable $e) {
            $state['status'] = 'partial';
            $state['message'] = 'Reprise non planifiée : réessayer après vérification.';
            self::save($id, $state);
            throw $e;
        }
    }
    public static function acquire_lock(string $id) {
        $dir = self::path($id);
        if (!is_dir($dir) || is_link($dir)) { throw new \DomainException('Job disparu'); }
        $global = self::root() . '/.import-global.lock';
        $h = fopen($global, 'cb');
        if (!is_resource($h) || !flock($h, LOCK_EX | LOCK_NB)) {
            if (is_resource($h)) { fclose($h); }
            throw new \RuntimeException('Un import QA est déjà en cours; le job reste en file.');
        }
        return $h;
    }
    public static function archives(string $id, array $state): array {
        $dir = self::path($id); $result = [];
        foreach (['calendar','anthology'] as $kind) {
            $file = $dir . '/' . $kind . '.zip';
            $hash = $state['sha'][$kind] ?? '';
            if (!is_file($file) || is_link($file) || filesize($file) < 100 ||
                filesize($file) > self::MAX_BYTES ||
                !is_string($hash) || !preg_match('/\A[a-f0-9]{64}\z/D', $hash) ||
                !hash_equals($hash, (string)hash_file('sha256', $file))) {
                throw new \RuntimeException('ZIP du job altéré : import interdit');
            }
            $result[$kind] = $file;
        }
        return $result;
    }
}
