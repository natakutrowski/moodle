<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseFulfillmentSummary;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseGrantSummary;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use moodle_database;
use moodle_url;
use local_subscriptions\url\UrlFactory;

/** Resolves safe customer actions from Native grants and fulfillment state. */
final class CommerceOrderAccessActionResolver {
    private const COMPLETE_STATUSES = ['completed', 'fulfilled', 'delivered', 'success'];

    public function __construct(
        private readonly moodle_database $database,
        private readonly int $now
    ) {
    }

    public static function create(?int $now = null): self {
        global $DB;
        return new self($DB, $now ?? time());
    }

    public function resolve(
        string $orderreference,
        CommercePurchaseGrantSummary $grant,
        ?CommercePurchaseFulfillmentSummary $fulfillment
    ): CommerceOrderAccessPresentation {
        $status = strtolower($fulfillment?->status ?? $grant->status);
        $fulfilled = in_array($status, self::COMPLETE_STATUSES, true);
        $reason = $fulfilled ? null : $this->reason_from_status($status);
        $available = false;
        $metadata = [
            'resourcekey' => $grant->resourcekey,
            'productsku' => $grant->productsku,
        ];

        if ($grant->validfrom > $this->now) {
            $reason = 'not_started';
        } elseif ($grant->validuntil !== null && $grant->validuntil < $this->now) {
            $reason = 'expired';
        } elseif ($grant->type === 'course_access') {
            $courseid = $this->resolve_course_id($grant);
            $metadata['courseid'] = $courseid;
            if ($courseid === null || !$this->database->record_exists('course', ['id' => $courseid])) {
                $reason = 'resource_missing';
            } else {
                $available = $fulfilled;
            }
        } elseif ($grant->type === 'digital_download') {
            $digital = $this->find_digital_access($grant->reference);
            if ($digital === null) {
                $reason = $fulfilled ? 'access_missing' : $reason;
            } else {
                $metadata['downloadcount'] = (int)$digital->downloadcount;
                $metadata['maxdownloads'] = $digital->maxdownloads === null ? null : (int)$digital->maxdownloads;
                $metadata['remainingdownloads'] = $digital->maxdownloads === null
                    ? null
                    : max(0, (int)$digital->maxdownloads - (int)$digital->downloadcount);

                $product = $this->database->get_record('local_subs_commerce_product', [
                    'sku' => (string)$digital->productsku,
                ], 'id,metadatajson', IGNORE_MISSING);
                if ($product !== false) {
                    $files = new CommerceCatalogDigitalFileManager(\context_system::instance());
                    $metadata['hasdesktop'] = $files->get_file((int)$product->id, CommerceCatalogDigitalFileManager::ROLE_DESKTOP) !== null;
                    $metadata['hasmobile'] = $files->get_file((int)$product->id, CommerceCatalogDigitalFileManager::ROLE_MOBILE) !== null;
                    $productmetadata = json_decode((string)($product->metadatajson ?? ''), true);
                    if (is_array($productmetadata)) {
                        $metadata['hasdesktop'] = $metadata['hasdesktop'] || trim((string)($productmetadata['filename'] ?? '')) !== '';
                        $metadata['hasmobile'] = $metadata['hasmobile'] || trim((string)($productmetadata['mobilefilename'] ?? '')) !== '';
                    }
                }

                if ((string)$digital->status !== 'active') {
                    $reason = 'inactive';
                } elseif ((int)$digital->validfrom > $this->now) {
                    $reason = 'not_started';
                } elseif ($digital->validuntil !== null && (int)$digital->validuntil < $this->now) {
                    $reason = 'expired';
                } elseif ($digital->maxdownloads !== null
                    && (int)$digital->downloadcount >= (int)$digital->maxdownloads) {
                    $reason = 'download_limit_reached';
                } else {
                    $available = $fulfilled;
                }
            }
        } else {
            $reason = $fulfilled ? 'unsupported_access_type' : $reason;
        }

        $url = null;
        if ($available) {
            if ($grant->type === 'course_access') {
                $courseid = (int)($metadata['courseid'] ?? 0);
                $url = $courseid > 0
                    ? UrlFactory::course($courseid)->out(false)
                    : null;
            } else {
                $url = (new moodle_url('/local/subscriptions/order_access.php', [
                    'reference' => $orderreference,
                    'grant' => $grant->reference,
                ]))->out(false);
            }
        }

        return new CommerceOrderAccessPresentation(
            $grant->type,
            $grant->type === 'course_access' ? 'open_course' : ($grant->type === 'digital_download' ? 'download_file' : 'open_access'),
            $status,
            $available,
            $url,
            $grant->reference,
            $grant->validuntil,
            $metadata,
            $reason
        );
    }

    public function find_digital_access(string $grantreference): ?\stdClass {
        $record = $this->database->get_record('local_subs_commerce_dig_access', [
            'grantreference' => $grantreference,
        ], '*', IGNORE_MISSING);
        return $record === false ? null : $record;
    }

    public function resolve_course_id(CommercePurchaseGrantSummary $grant): ?int {
        $courseid = (int)($grant->configuration['courseid'] ?? 0);
        if ($courseid > 0) {
            return $courseid;
        }
        if (preg_match('/^course:(\d+)(?::|$)/', $grant->resourcekey, $matches) === 1) {
            return (int)$matches[1];
        }
        return null;
    }

    private function reason_from_status(string $status): string {
        return match ($status) {
            'failed', 'error' => 'fulfillment_failed',
            'cancelled', 'canceled' => 'cancelled',
            default => 'pending',
        };
    }
}
