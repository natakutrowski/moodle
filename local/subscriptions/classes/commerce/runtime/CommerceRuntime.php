<?php

namespace local_subscriptions\commerce\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialClassifier;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\bridge\CommerceFulfillmentFeatureToggle;
use local_subscriptions\commerce\fulfillment\bridge\CommercePostPaymentBridge;
use local_subscriptions\commerce\fulfillment\postaction\CommercePostFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\postaction\DigitalEmailPostFulfillmentAction;
use local_subscriptions\commerce\fulfillment\postaction\SubscriptionEmailPostFulfillmentAction;
use local_subscriptions\commerce\fulfillment\shadow\CommerceFulfillmentShadowService;
use local_subscriptions\commerce\payment\CommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseBuilder;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseMapper;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseValidator;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparationOrchestrator;
use local_subscriptions\commerce\purchase\repository\CommercePurchaseRepository;
use local_subscriptions\commerce\purchase\shadow\CommercePurchaseShadowService;

/**
 * Runtime container for the Commerce domain.
 */
final class CommerceRuntime {

    private ?CommercePurchaseBuilder $purchasebuilder = null;

    private ?CommercePurchaseMapper $purchasemapper = null;

    private ?CommercePurchaseValidator $purchasevalidator = null;

    private ?CommercePurchaseRepository $purchasedomainrepository = null;

    private ?CommercePurchaseShadowService $purchaseshadowservice = null;

    private ?CommercePostFulfillmentCoordinator $postfulfillment = null;

    private ?CommercePostPaymentBridge $postpaymentbridge = null;

    private ?CommerceFulfillmentShadowService $fulfillmentshadow = null;

    public function __construct(
        private readonly CommercePurchaseService $purchaseservice,
        private readonly CommercePurchaseFinancialClassifier
            $financialclassifier,
        private readonly CommercePurchaseHandlerRegistry
            $purchasehandlerregistry,
        private readonly CommercePurchasePreparationOrchestrator
            $purchasepreparationorchestrator,
        private readonly CommerceFulfillmentHandlerRegistry
            $fulfillmenthandlerregistry,
        private readonly CommerceFulfillmentCoordinator
            $fulfillmentcoordinator,
        private readonly CommercePaymentRequestFactory
            $paymentrequestfactory,
        private readonly CommercePaymentProviderRegistry
            $paymentproviderregistry,
        private readonly CommercePaymentOrchestrator
            $paymentorchestrator,
        private readonly CommercePaymentProviderContextFactory
            $paymentcontextfactory,
        private readonly LegacyCommercePaymentRequestFactory
            $legacypaymentrequestfactory
    ) {
    }

    public function purchases(): CommercePurchaseService {
        return $this->purchaseservice;
    }

    public function financial_classifier():
        CommercePurchaseFinancialClassifier {
        return $this->financialclassifier;
    }

    public function purchase_handlers():
        CommercePurchaseHandlerRegistry {
        return $this->purchasehandlerregistry;
    }

    public function purchase_preparation():
        CommercePurchasePreparationOrchestrator {
        return $this->purchasepreparationorchestrator;
    }

    public function fulfillment_handlers():
        CommerceFulfillmentHandlerRegistry {
        return $this->fulfillmenthandlerregistry;
    }

    public function fulfillment():
        CommerceFulfillmentCoordinator {
        return $this->fulfillmentcoordinator;
    }

    public function payment_requests():
        CommercePaymentRequestFactory {
        return $this->paymentrequestfactory;
    }

    public function payment_providers():
        CommercePaymentProviderRegistry {
        return $this->paymentproviderregistry;
    }

    public function payment_orchestration():
        CommercePaymentOrchestrator {
        return $this->paymentorchestrator;
    }

    public function payment_contexts():
        CommercePaymentProviderContextFactory {
        return $this->paymentcontextfactory;
    }

    public function legacy_payment_requests():
        LegacyCommercePaymentRequestFactory {
        return $this->legacypaymentrequestfactory;
    }

    /**
     * Return secondary post-fulfillment actions.
     */
    public function post_fulfillment():
        CommercePostFulfillmentCoordinator {
        $this->postfulfillment ??=
            new CommercePostFulfillmentCoordinator([
                new SubscriptionEmailPostFulfillmentAction(),
                new DigitalEmailPostFulfillmentAction(),
            ]);

        return $this->postfulfillment;
    }

    /**
     * Return the disabled-by-default post-payment bridge.
     */
    public function post_payment_bridge():
        CommercePostPaymentBridge {
        $this->postpaymentbridge ??=
            new CommercePostPaymentBridge(
                $this->fulfillmentcoordinator,
                $this->post_fulfillment(),
                new CommerceFulfillmentFeatureToggle()
            );

        return $this->postpaymentbridge;
    }

    /**
     * Return the read-only fulfillment shadow service.
     */
    public function fulfillment_shadow():
        CommerceFulfillmentShadowService {
        $this->fulfillmentshadow ??=
            new CommerceFulfillmentShadowService();

        return $this->fulfillmentshadow;
    }

    /**
     * Return the Commerce purchase builder.
     *
     * @return CommercePurchaseBuilder
     */
    public function purchase_builder():
        CommercePurchaseBuilder {
        $this->purchasebuilder ??=
            new CommercePurchaseBuilder();

        return $this->purchasebuilder;
    }

    /**
     * Return the Commerce purchase mapper.
     *
     * @return CommercePurchaseMapper
     */
    public function purchase_mapper():
        CommercePurchaseMapper {
        $this->purchasemapper ??=
            new CommercePurchaseMapper();

        return $this->purchasemapper;
    }

    /**
     * Return the Commerce purchase validator.
     *
     * @return CommercePurchaseValidator
     */
    public function purchase_validator():
        CommercePurchaseValidator {
        $this->purchasevalidator ??=
            new CommercePurchaseValidator();

        return $this->purchasevalidator;
    }

    /**
     * Return the unified Commerce purchase repository.
     *
     * @return CommercePurchaseRepository
     */
    public function purchase_domain_repository():
        CommercePurchaseRepository {
        $this->purchasedomainrepository ??=
            new CommercePurchaseRepository(
                $this->purchaseservice
            );

        return $this->purchasedomainrepository;
    }

    /**
     * Return the Commerce purchase shadow service.
     *
     * @return CommercePurchaseShadowService
     */
    public function purchase_shadow():
        CommercePurchaseShadowService {
        $this->purchaseshadowservice ??=
            new CommercePurchaseShadowService(
                $this->purchase_validator(),
                $this->purchase_mapper()
            );

        return $this->purchaseshadowservice;
    }
}