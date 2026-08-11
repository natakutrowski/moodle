<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;

final class commerce_product_display_name_resolver_k10j_test extends advanced_testcase {
    public function test_resolver_reuses_catalogue_translation_fallback_and_tries_all_skus(): void {
        global $DB;

        $this->resetAfterTest(true);

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);
        $translations = new CommerceProductTranslationRepository(
            $DB,
            $hydrator,
            $products
        );

        $products->save(new CommerceProduct(
            'THIRD-GROUP-VERBS-COURSE',
            'course_access',
            'active',
            'third-group-verbs-course'
        ));

        $translations->save(new CommerceProductTranslation(
            'THIRD-GROUP-VERBS-COURSE',
            'fr',
            'Verbes du 3e groupe<br> - le cours'
        ));

        $resolver = new CommerceProductDisplayNameResolver(
            $products,
            $translations
        );

        $this->assertSame(
            'Verbes du 3e groupe - le cours',
            $resolver->resolve(
                [
                    'DOES-NOT-EXIST',
                    'THIRD-GROUP-VERBS-COURSE',
                ],
                'ru',
                'third-group-verbs-course'
            )
        );
    }

    public function test_shared_order_stack_uses_the_central_resolver(): void {
        $root = dirname(__DIR__, 3);
        $service = (string)file_get_contents(
            $root . '/classes/commerce/order/presentation/CommerceOrderPresentationService.php'
        );
        $details = (string)file_get_contents(
            $root . '/order_details.php'
        );

        $this->assertStringContainsString(
            'CommerceProductDisplayNameResolver',
            $service
        );
        $this->assertStringContainsString(
            '...$grantproductskus',
            $service
        );
        $this->assertStringContainsString(
            'CommerceOrderPresentationService::create()',
            $details
        );
    }
}
