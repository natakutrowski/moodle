<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\certification;

defined('MOODLE_INTERNAL') || die();

/** Immutable release manifest for the certified Commerce transactional mail engine. */
final class CommerceMailReleaseManifest {

    public const RELEASE = 'Transactional Mail Engine';
    public const STATUS = 'CERTIFIED';
    public const LIFECYCLE = 'FROZEN';
    public const FROZEN_ON = '2026-08-01';

    /** @return string[] */
    public static function required_files(): array {
        return [
            'classes/commerce/mail/CommerceMailDispatcher.php',
            'classes/commerce/mail/CommerceMailQueueProcessor.php',
            'classes/commerce/mail/CommerceMailQueueRepository.php',
            'classes/commerce/mail/CommerceMailQueueService.php',
            'classes/commerce/mail/CommerceMailRuntime.php',
            'classes/commerce/mail/CommerceMailCustomerContentPolicy.php',
            'classes/commerce/mail/MoodleCommerceMailTransport.php',
            'classes/commerce/mail/service/CommerceTransactionalPurchaseMailService.php',
            'classes/commerce/mail/context/CommercePurchaseMailContextFactory.php',
            'classes/commerce/mail/template/studio/CommerceMailTemplateDefaults.php',
            'classes/commerce/mail/template/studio/CommerceMailTokenResolver.php',
            'classes/commerce/mail/template/studio/CommerceMailHeaderImageService.php',
            'classes/commerce/order/invoice/CommerceInvoicePdfService.php',
            'classes/task/process_commerce_mail_queue_task.php',
            'admin/commerce/mail/index.php',
            'admin/commerce/mail/view.php',
            'admin/commerce/mail/templates/index.php',
            'cli/commerce/mail/certify_engine.php',
            'tests/commerce/mail/commerce_mail_engine_certification_test.php',
            'tests/commerce/mail/commerce_mail_engine_end_to_end_test.php',
            'tests/commerce/mail/commerce_mail_engine_failure_recovery_test.php',
            'tests/commerce/mail/commerce_mail_admin_preview_test.php',
            'docs/commerce/CommerceMailEngine.md',
            'docs/commerce/7.95-I6-release-checklist.md',
        ];
    }

    /** @return string[] Relative paths missing from the supplied plugin root. */
    public static function missing_files(string $pluginroot): array {
        $pluginroot = rtrim($pluginroot, DIRECTORY_SEPARATOR);
        $missing = [];
        foreach (self::required_files() as $relativepath) {
            if (!is_file($pluginroot . DIRECTORY_SEPARATOR . $relativepath)) {
                $missing[] = $relativepath;
            }
        }
        return $missing;
    }

    /** @return array{release:string,status:string,lifecycle:string,frozenon:string,requiredfiles:int} */
    public static function export(): array {
        return [
            'release' => self::RELEASE,
            'status' => self::STATUS,
            'lifecycle' => self::LIFECYCLE,
            'frozenon' => self::FROZEN_ON,
            'requiredfiles' => count(self::required_files()),
        ];
    }
}
