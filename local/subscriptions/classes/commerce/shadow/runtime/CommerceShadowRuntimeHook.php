<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentExecutor;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\shadow\CommerceKernelShadowNativeExecutor;
use local_subscriptions\commerce\shadow\CommerceProjectedPurchaseShadowGrantSource;
use local_subscriptions\commerce\shadow\CommerceShadowComparator;
use local_subscriptions\commerce\shadow\CommerceShadowDivergenceClassifier;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionService;
use local_subscriptions\commerce\shadow\CommerceShadowSource;
use local_subscriptions\commerce\shadow\persistence\MoodleCommerceShadowPersistenceRepository;
use local_subscriptions\payment\dto\InternalEvent;

/** Non-blocking G5 hook called after authoritative Legacy checkout fulfillment. */
final class CommerceShadowRuntimeHook {
    private const RECENT_EXECUTION_WINDOW = 120;

    /** @var array<string, bool> */
    private static array $executed = [];
    public static function after_checkout_completed(InternalEvent $event, string $entrypoint = 'event_router'): void {
        if (!(bool) get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled')) {
            return;
        }
        try {
            $legacyrequestid = (int) ($event->payment_request_id ?? $event->meta['payment_request_id'] ?? 0);
            $legacyfamily = self::legacy_family($event);
            $purchasereference = (new CommerceShadowPurchaseReferenceResolver())->resolve(
                $legacyrequestid,
                $legacyfamily
            );
            if ($purchasereference === null) {
                debugging('Commerce Shadow skipped: Native purchase reference was not resolved.', DEBUG_DEVELOPER);
                return;
            }
            $source = self::source($event);
            self::run_projected_purchase($purchasereference, $source, $entrypoint);
        } catch (\Throwable $exception) {
            debugging(
                'Commerce Shadow runtime error [' . get_class($exception) . ']: ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }

    public static function run_projected_purchase(
        string $purchasereference,
        string $source,
        string $entrypoint = 'runtime',
        ?int $actoruserid = null
    ): void {
        $purchasereference = trim($purchasereference);
        if ($purchasereference === '') {
            return;
        }

        $dedupekey = $purchasereference . '|' . $source;
        if (isset(self::$executed[$dedupekey]) || self::was_recently_persisted($purchasereference)) {
            return;
        }
        self::$executed[$dedupekey] = true;

        self::service()->run($purchasereference, $source, $entrypoint, $actoruserid);
    }


    private static function was_recently_persisted(string $purchasereference): bool {
        global $DB;

        return $DB->record_exists_select(
            'local_subs_commerce_shadow',
            'purchasereference = :reference AND timecreated >= :since',
            [
                'reference' => $purchasereference,
                'since' => time() - self::RECENT_EXECUTION_WINDOW,
            ]
        );
    }

    private static function service(): CommerceShadowRuntimeService {
        $grants = new CommerceProjectedPurchaseShadowGrantSource();
        $registry = new CommerceNativeFulfillmentHandlerRegistry([
            new CommerceCourseAccessFulfillmentHandler(),
            new CommerceDigitalDownloadFulfillmentHandler(),
        ]);
        $native = new CommerceShadowExecutionService(
            $grants,
            new CommerceKernelShadowNativeExecutor(new CommerceNativeFulfillmentExecutor($registry))
        );
        return new CommerceShadowRuntimeService(
            new MoodleCommerceLegacyObservationCollector($grants),
            $native,
            new CommerceShadowComparator(),
            new CommerceShadowDivergenceClassifier(),
            new MoodleCommerceShadowPersistenceRepository()
        );
    }

    private static function legacy_family(InternalEvent $event): string {
        $paymentcontext = strtolower(trim((string)($event->meta['payment_context'] ?? '')));
        $paymentrequesttable = strtolower(trim((string)($event->meta['payment_request_table'] ?? '')));

        if ($paymentcontext === 'digital_product' || str_contains($paymentrequesttable, 'digital')) {
            return 'digital';
        }

        return 'subscription';
    }

    private static function source(InternalEvent $event): string {
        $provider = strtolower((string) ($event->meta['provider'] ?? ''));
        return match ($provider) {
            'stripe' => CommerceShadowSource::STRIPE_WEBHOOK,
            'alfa', 'alfabank' => CommerceShadowSource::ALFA_WEBHOOK,
            default => CommerceShadowSource::REPAIR_JOB,
        };
    }
}
