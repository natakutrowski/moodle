<?php
// CampusFR D2.12.2 — authorized, frozen-source learner PDF exports.
// Never reads editable story pages; never infers grants from a course enrolment.
declare(strict_types=1);
namespace local_campusreader\local;
defined('MOODLE_INTERNAL') || die();

final class publication_pdf {
    /**
     * Authorize the user and resolve one immutable chapter for PDF rendering.
     * The HTTP route must call this before emitting any PDF bytes.
     *
     * @return array{bookid:int,book:object,pages:array,imageprovider:callable,mode:string}
     */
    public static function resolve(string $slug, int $chapter, string $mode): array {
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug) ||
                $chapter < 1 || $chapter > 100 ||
                !in_array($mode, ['reading', 'booklet'], true)) {
            throw new \invalid_parameter_exception('Invalid PDF request');
        }

        // This is the *existing* Native Commerce gate. It checks the current user,
        // fulfilled active grant, publication status and frozen Advent unlock.
        $pub = publication_access::require_customer($slug, $chapter);
        if ((int)($pub->id ?? 0) < 1 || (int)($pub->revisionid ?? 0) < 1) {
            throw new \invalid_parameter_exception('Invalid published edition');
        }
        $snapshot = publication_access::frozen($pub);
        $item = $snapshot['chapters'][$chapter - 1] ?? null;
        if (!is_array($item) || ($item['position'] ?? null) !== $chapter ||
                !is_int($item['bookid'] ?? null) || $item['bookid'] < 1) {
            throw new \invalid_parameter_exception('Invalid frozen chapter');
        }

        // D2.12.1 maps digest-validated story pages and checked private images.
        $source = \local_subscriptions\crm\library\ReaderPublishedPdfSource::prepare(
            (int)$pub->id, $chapter);
        if (!is_array($source) || !isset($source['book'], $source['pages'], $source['imageprovider']) ||
                !is_object($source['book']) || !is_array($source['pages']) ||
                !is_callable($source['imageprovider'])) {
            throw new \invalid_parameter_exception('Invalid immutable PDF source');
        }

        // Recheck at the end of preparation: reject concurrent pause, revision
        // change, grant revocation or slot closure before rendering starts.
        $latest = publication_access::require_customer($slug, $chapter);
        foreach (['id', 'revisionid', 'anthologyid', 'kind', 'calendarjson', 'status', 'productsku'] as $field) {
            if ((string)($pub->{$field} ?? '') !== (string)($latest->{$field} ?? '')) {
                throw new \invalid_parameter_exception('Publication changed during preparation');
            }
        }

        return ['bookid' => $item['bookid'], 'book' => $source['book'],
            'pages' => $source['pages'], 'imageprovider' => $source['imageprovider'],
            'mode' => $mode];
    }
}
