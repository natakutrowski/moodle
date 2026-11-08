<?php
// This file is part of Moodle - http://moodle.org/.
// CampusFR 7.98X.2.2.1: Admin-only, source-only Lovable preparation.

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

use invalid_parameter_exception;
use RuntimeException;
use ZipArchive;

final class CommerceShowroomBridgeLovableUiService {
    public const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;
    public const MAX_BUNDLE_BYTES = 350 * 1024 * 1024;
    public const MAX_BUNDLE_FILES = 3500;
    public const MAX_JOB_AGE = 7200;

    /**
     * @return array{id:string,label:string}
     */
    public static function validate_identity(string $id, string $label): array {
        $id = trim($id);
        $label = trim($label);
        if (!preg_match('/\A[a-z][a-z0-9_]{2,47}\z/D', $id)) {
            throw new invalid_parameter_exception('Identifiant : 3 à 48 caractères, a-z, 0-9 et _ ; commencer par une lettre.');
        }
        if ($label === '' || strlen($label) > 150 || preg_match('/[\x00-\x1F<>]/', $label)) {
            throw new invalid_parameter_exception('Nom du design invalide (maximum 150 octets, pas de balises).');
        }
        return ['id' => $id, 'label' => $label];
    }

    public static function validate_upload_details(string $filename, int $size, int $error): void {
        if ($error !== UPLOAD_ERR_OK) {
            throw new invalid_parameter_exception(self::upload_error_message($error));
        }
        if ($size < 1 || $size > self::MAX_UPLOAD_BYTES) {
            throw new invalid_parameter_exception('Le ZIP Lovable doit être compris entre 1 octet et 20 Mo.');
        }
        if (!preg_match('/\.zip\z/iD', $filename)) {
            throw new invalid_parameter_exception('Un fichier ZIP Lovable est requis.');
        }
    }

    private static function upload_error_message(int $error): string {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux pour la configuration PHP du serveur.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n’a été reçu que partiellement.',
            UPLOAD_ERR_NO_FILE => 'Sélectionne un fichier ZIP Lovable.',
            default => 'Échec du téléversement ZIP (code ' . $error . ').',
        };
    }

    /** Prevent confusing a ZIP signature with a full archive validation. */
    public static function assert_zip_file(string $path): void {
        if (!is_file($path) || is_link($path) || filesize($path) < 4) {
            throw new invalid_parameter_exception('Fichier ZIP illisible.');
        }
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new invalid_parameter_exception('Impossible de lire le ZIP.');
        }
        try {
            $header = fread($handle, 4);
        } finally {
            fclose($handle);
        }
        if ($header !== "PK\x03\x04") {
            throw new invalid_parameter_exception('L’archive ZIP est invalide (signature absente).');
        }
        $zip = new ZipArchive();
        $result = $zip->open($path, ZipArchive::RDONLY);
        if ($result !== true) {
            throw new invalid_parameter_exception('Impossible d’ouvrir cette archive ZIP.');
        }
        $zip->close();
    }

    /** @return list<string> Process is launched without shell interpolation. */
    public static function command(string $cli, string $zip, string $id, string $label, string $output): array {
        self::validate_identity($id, $label);
        return ['/usr/bin/timeout', '--signal=TERM', '--kill-after=2s', '40s', '/usr/bin/python3',
            $cli, 'prepare', $zip, '--id', $id, '--label', $label, '--output', $output];
    }

    /**
     * @return array<string,mixed> Inspection report.
     */
    public static function prepare(string $uploadedzip, string $id, string $label, string $jobdir, string $cli): array {
        self::assert_zip_file($uploadedzip);
        if (!is_dir($jobdir) || is_link($jobdir)) {
            throw new invalid_parameter_exception('Dossier de travail privé invalide.');
        }
        if (!is_file($cli) || !is_readable($cli)) {
            throw new RuntimeException('Outil X2.2 indisponible.');
        }
        if (!function_exists('proc_open') || !is_executable('/usr/bin/timeout') || !is_executable('/usr/bin/python3')) {
            throw new RuntimeException('Préparation indisponible : Python ou exécution contrôlée désactivés sur le serveur.');
        }
        // X2.2 deliberately forbids writing any prepared sources under /var/www.
        // On this Moodle installation $CFG->tempdir is under /var/www (but outside
        // the web document root). Keep the uploaded ZIP and final download in
        // Moodle's private temporary area, and process sources in a separate,
        // unpredictable, mode-0700 directory under /tmp. No PHP/JS is executed.
        if (!is_dir('/tmp') || is_link('/tmp') || !is_writable('/tmp')) {
            throw new RuntimeException('Répertoire temporaire isolé /tmp indisponible.');
        }
        $stagedir = '/tmp/campusfr_lovable_prep_' . bin2hex(random_bytes(16));
        if (!mkdir($stagedir, 0700) || is_link($stagedir)) {
            throw new RuntimeException('Impossible de créer le répertoire temporaire isolé.');
        }
        try {
            $output = $stagedir . '/workspace';
            $stdout = $jobdir . '/stdout.txt';
            $stderr = $jobdir . '/stderr.txt';
            $command = self::command($cli, $uploadedzip, $id, $label, $output);
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $stdout, 'w'],
                2 => ['file', $stderr, 'w'],
            ];
            // Only CampusFR's trusted CLI is launched; user-supplied code stays inert.
            $process = @proc_open($command, $descriptors, $pipes, $jobdir, null, ['bypass_shell' => true]);
            if (!is_resource($process)) {
                throw new RuntimeException('Impossible de démarrer le préparateur Lovable.');
            }
            fclose($pipes[0]);
            $exitcode = proc_close($process);
            if ($exitcode !== 0) {
                $diagnostic = is_file($stderr) ? trim((string)file_get_contents($stderr, false, null, 0, 1800)) : '';
                if ($exitcode === 124 || $exitcode === 137) {
                    $diagnostic = 'Limite de traitement atteinte (40 secondes).';
                }
                throw new RuntimeException('Préparation refusée : ' . ($diagnostic ?: 'échec de validation du ZIP (' . $exitcode . ').'));
            }
            $reportfile = $output . '/campusfr-inspection.json';
            if (!is_file($reportfile)) {
                throw new RuntimeException('Le préparateur n’a pas produit de rapport.');
            }
            $report = json_decode((string)file_get_contents($reportfile), true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($report) || ($report['status'] ?? null) !== 'PREPARED_SOURCE_ONLY'
                    || ($report['designId'] ?? null) !== $id || ($report['approved'] ?? true) !== false) {
                throw new RuntimeException('Rapport de préparation invalide : préparation non approuvée uniquement.');
            }
            $archive = $jobdir . '/campusfr-lovable-' . $id . '-a-revoir.zip';
            self::package_workspace($output, $archive);
            return $report;
        } finally {
            // A source workspace never persists on the server after the request.
            if (is_dir($stagedir) && !is_link($stagedir)) {
                remove_dir($stagedir);
            }
        }
    }

    /** ZIP prepared *source* workspace only. Never web-root, never an approved/executable Bridge package. */
    public static function package_workspace(string $workspace, string $destination): void {
        if (!is_dir($workspace) || is_link($workspace) || !is_file($workspace . '/README_CAMPUSFR_X22.md')
                || !is_file($workspace . '/campusfr-inspection.json') || !is_dir($workspace . '/source')) {
            throw new invalid_parameter_exception('Workspace de préparation incomplet.');
        }
        if (file_exists($destination)) {
            throw new invalid_parameter_exception('Le ZIP de sortie existe déjà.');
        }
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('Impossible de créer l’archive du workspace.');
        }
        $count = 0;
        $total = 0;
        try {
            $dir = new \RecursiveDirectoryIterator($workspace, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($iterator as $file) {
                $path = $file->getPathname();
                if ($file->isLink() || !$file->isFile()) {
                    throw new RuntimeException('Lien symbolique ou entrée inhabituelle interdite.');
                }
                $relative = substr($path, strlen($workspace) + 1);
                if ($relative === false || $relative === '' || str_contains($relative, '\\')
                        || str_contains($relative, '../') || str_starts_with($relative, '/')) {
                    throw new RuntimeException('Chemin du workspace invalide.');
                }
                $count++;
                $total += $file->getSize();
                if ($count > self::MAX_BUNDLE_FILES || $total > self::MAX_BUNDLE_BYTES) {
                    throw new RuntimeException('Workspace trop volumineux pour un téléchargement depuis le CRM.');
                }
                if (!$zip->addFile($path, $relative)) {
                    throw new RuntimeException('Échec de l’ajout au ZIP : ' . $relative);
                }
            }
            if ($count === 0) {
                throw new RuntimeException('Workspace vide.');
            }
        } catch (\Throwable $error) {
            $zip->close();
            @unlink($destination);
            throw $error;
        }
        if (!$zip->close()) {
            @unlink($destination);
            throw new RuntimeException('Impossible de finaliser le ZIP préparé.');
        }
    }

    public static function valid_job_key(string $key): bool {
        return preg_match('/\A[a-f0-9]{32}\z/D', $key) === 1;
    }
}
