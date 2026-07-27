<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\reporting;

defined('MOODLE_INTERNAL') || die();

/** Deterministic JSON and CSV exporter for Commerce Shadow search results. */
final class CommerceShadowReportExporter {
    public function export_json(array $rows): string {
        $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode Commerce Shadow JSON export.');
        }
        return $json . PHP_EOL;
    }

    public function export_csv(array $rows): string {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            throw new \RuntimeException('Unable to create Commerce Shadow CSV stream.');
        }
        fputcsv($stream, [
            'id', 'executionreference', 'purchasereference', 'source', 'entrypoint',
            'comparisonstatus', 'classification', 'durationms', 'errorclass',
            'errormessage', 'timecreated', 'differencesjson',
        ], ',', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($stream, [
                $row['id'] ?? 0,
                $row['executionreference'] ?? '',
                $row['purchasereference'] ?? '',
                $row['source'] ?? '',
                $row['entrypoint'] ?? '',
                $row['comparisonstatus'] ?? '',
                $row['classification'] ?? '',
                $row['durationms'] ?? 0,
                $row['errorclass'] ?? '',
                $row['errormessage'] ?? '',
                $row['timecreated'] ?? 0,
                json_encode($row['differences'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ], ',', '"', '\\');
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read Commerce Shadow CSV export.');
        }
        return $contents;
    }
}
