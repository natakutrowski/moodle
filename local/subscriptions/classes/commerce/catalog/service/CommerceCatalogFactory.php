<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\audit\CommerceCatalogParityAuditor;
use local_subscriptions\commerce\catalog\migration\CommerceLegacyCatalogImporter;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\bundle\audit\CommerceBundleDomainAuditor;
use local_subscriptions\commerce\bundle\audit\CommerceBundleExpansionAuditor;
use local_subscriptions\commerce\bundle\audit\CommerceBundleCrmBackendAuditor;
use local_subscriptions\commerce\bundle\audit\CommerceBundlePreviewAuditor;
use local_subscriptions\commerce\bundle\audit\CommerceBundlePricingAuditor;
use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductManager;
use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\bundle\repository\CommerceBundleRepository;
use local_subscriptions\commerce\bundle\service\CommerceBundleDomainValidator;
use local_subscriptions\commerce\bundle\service\CommerceBundleReadService;
use local_subscriptions\commerce\bundle\preview\CommerceBundlePreviewService;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingCalculator;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingService;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;

/** Creates the catalogue service graph for CLI and integration boundaries. */
final class CommerceCatalogFactory {
    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    private CommerceCatalogHydrator $hydrator;
    private CommerceProductRepository $products;
    private CommerceProductPriceRepository $prices;
    private CommerceProductTranslationRepository $translations;
    private CommerceProductComponentRepository $components;
    private CommerceProductEntitlementRepository $entitlements;

    public function __construct(
        private readonly \moodle_database $db,
        private readonly ?CommercePaymentProviderRegistry $paymentproviders = null
    ) {
        $this->hydrator = new CommerceCatalogHydrator();
        $this->products = new CommerceProductRepository($db, $this->hydrator);
        $this->prices = new CommerceProductPriceRepository($db, $this->hydrator, $this->products);
        $this->translations = new CommerceProductTranslationRepository($db, $this->hydrator, $this->products);
        $this->components = new CommerceProductComponentRepository($db, $this->hydrator, $this->products);
        $this->entitlements = new CommerceProductEntitlementRepository($db, $this->hydrator, $this->products);
    }

    public function admin(): CommerceProductAdminService {
        return new CommerceProductAdminService(
            $this->db,
            $this->products,
            $this->prices,
            $this->translations,
            $this->components,
            $this->entitlements
        );
    }

    public function importer(): CommerceLegacyCatalogImporter {
        return new CommerceLegacyCatalogImporter(
            $this->db,
            $this->admin(),
            new CommerceLegacyProductMapRepository($this->db)
        );
    }


    public function resolver(): CommerceProductResolver {
        return new CommerceProductResolver(
            $this->products,
            $this->prices,
            $this->translations,
            $this->entitlements,
            new CommerceEffectiveEntitlementResolver(
                $this->db,
                $this->products,
                $this->entitlements
            )
        );
    }

    public function bundle_expander(): CommerceBundleExpander {
        return new CommerceBundleExpander(
            $this->products,
            $this->components
        );
    }

    public function bundle_expansion_service(): CommerceBundleExpansionService {
        return new CommerceBundleExpansionService(
            $this->products,
            $this->components
        );
    }

    public function bundle_expansion_auditor(): CommerceBundleExpansionAuditor {
        return new CommerceBundleExpansionAuditor(
            $this->bundle_read_service(),
            $this->bundle_expansion_service()
        );
    }

    public function purchase_request_factory(): \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchaseRequestFactory {
        return new \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchaseRequestFactory(
            $this->resolver(),
            $this->bundle_expander(),
            new \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchaseItemFactory()
        );
    }

    public function purchase_preparation_service(): \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchasePreparationService {
        $runtime = \local_subscriptions\commerce\runtime\CommerceRuntimeFactory::create();

        return new \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPurchasePreparationService(
            $this->purchase_request_factory(),
            $runtime->purchase_preparation()
        );
    }

    public function payment_pipeline(): \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipeline {
        $runtime = \local_subscriptions\commerce\runtime\CommerceRuntimeFactory::create();

        return new \local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipeline(
            $this->purchase_preparation_service(),
            $runtime->payment_requests()
        );
    }

    public function purchase_shadow_auditor(): \local_subscriptions\commerce\catalog\audit\CommerceCatalogPurchaseShadowAuditor {
        return new \local_subscriptions\commerce\catalog\audit\CommerceCatalogPurchaseShadowAuditor(
            $this->db,
            $this->purchase_request_factory()
        );
    }

    public function subscription_checkout_page(): \local_subscriptions\commerce\catalog\checkout\CommerceSubscriptionCheckoutPageService {
        return new \local_subscriptions\commerce\catalog\checkout\CommerceSubscriptionCheckoutPageService(
            $this->db,
            new CommerceLegacyProductMapRepository($this->db),
            $this->products,
            $this->prices
        );
    }

    public function catalog_checkout(): \local_subscriptions\commerce\catalog\checkout\CommerceCatalogCheckoutService {
        $runtime = \local_subscriptions\commerce\runtime\CommerceRuntimeFactory::create();

        return new \local_subscriptions\commerce\catalog\checkout\CommerceCatalogCheckoutService(
            $this->payment_pipeline(),
            $runtime->payment_contexts(),
            $this->payment_orchestrator($runtime)
        );
    }

    public function payment_simulation(): \local_subscriptions\commerce\catalog\checkout\CommerceCatalogPaymentSimulationService {
        $runtime = \local_subscriptions\commerce\runtime\CommerceRuntimeFactory::create();

        return new \local_subscriptions\commerce\catalog\checkout\CommerceCatalogPaymentSimulationService(
            $this->payment_pipeline(),
            $runtime->payment_contexts(),
            $this->payment_orchestrator($runtime)
        );
    }

    public function payment_pipeline_auditor(): \local_subscriptions\commerce\catalog\audit\CommerceCatalogPaymentPipelineAuditor {
        return new \local_subscriptions\commerce\catalog\audit\CommerceCatalogPaymentPipelineAuditor(
            $this->db,
            $this->payment_pipeline()
        );
    }

    public function checkout_certification_auditor(): \local_subscriptions\commerce\catalog\audit\CommerceCatalogCheckoutCertificationAuditor {
        return new \local_subscriptions\commerce\catalog\audit\CommerceCatalogCheckoutCertificationAuditor(
            $this->db,
            $this->catalog_checkout()
        );
    }

    public function product_type_registry(): CommerceProductTypeRegistry {
        return CommerceProductTypeRegistry::create_default();
    }

    public function bundle_read_service(): CommerceBundleReadService {
        return new CommerceBundleReadService(
            new CommerceBundleRepository(
                $this->products,
                $this->components
            )
        );
    }

    public function product_manager(): CommerceCatalogProductManager {
        return new CommerceCatalogProductManager(
            $this->db,
            $this->products,
            $this->prices,
            $this->translations,
            $this->components,
            $this->entitlements,
            $this->admin(),
            $this->bundle_expansion_service(),
            $this->product_type_registry()
        );
    }

    public function bundle_crm_backend_auditor(): CommerceBundleCrmBackendAuditor {
        return new CommerceBundleCrmBackendAuditor(
            $this->product_manager(),
            $this->product_type_registry()
        );
    }

    public function bundle_preview_service(): CommerceBundlePreviewService {
        return new CommerceBundlePreviewService(
            $this->products,
            $this->prices,
            $this->entitlements,
            $this->bundle_expansion_service()
        );
    }

    public function bundle_preview_auditor(): CommerceBundlePreviewAuditor {
        return new CommerceBundlePreviewAuditor(
            $this->bundle_read_service(),
            $this->bundle_preview_service()
        );
    }

    public function bundle_pricing_service(): CommerceBundlePricingService {
        return new CommerceBundlePricingService(
            $this->db,
            $this->products,
            $this->prices,
            $this->bundle_preview_service(),
            new CommerceBundlePricingCalculator()
        );
    }

    public function locale_service(): CommerceCatalogLocaleService {
        return new CommerceCatalogLocaleService();
    }

    public function currency_service(): CommerceCatalogCurrencyService {
        return new CommerceCatalogCurrencyService(
            $this->prices,
            $this->bundle_preview_service()
        );
    }

    public function bundle_pricing_auditor(): CommerceBundlePricingAuditor {
        return new CommerceBundlePricingAuditor(
            $this->bundle_read_service(),
            $this->bundle_pricing_service()
        );
    }

    public function bundle_domain_auditor(): CommerceBundleDomainAuditor {
        return new CommerceBundleDomainAuditor(
            $this->bundle_read_service(),
            new CommerceBundleDomainValidator($this->products),
            $this->product_type_registry()
        );
    }

    private function payment_orchestrator(
        \local_subscriptions\commerce\runtime\CommerceRuntime $runtime
    ): CommercePaymentOrchestrator {
        if ($this->paymentproviders === null) {
            return $runtime->payment_orchestration();
        }

        return new CommercePaymentOrchestrator(
            $this->paymentproviders
        );
    }

    public function parity_auditor(): CommerceCatalogParityAuditor {
        return new CommerceCatalogParityAuditor(
            $this->db,
            $this->products,
            $this->prices,
            $this->translations,
            $this->entitlements
        );
    }
}
