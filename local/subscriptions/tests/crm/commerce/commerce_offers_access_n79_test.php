<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n79_test extends advanced_testcase {
    public function test_offer_create_edit_and_detail_use_business_product_labels(): void {
        $root = dirname(__DIR__, 3);
        $presentation = file_get_contents(
            $root . '/classes/commerce/personaloffer/admin/'
            . 'CommercePersonalOfferCrmPresentation.php'
        );

        self::assertStringContainsString(
            'public static function business_product_label',
            $presentation
        );
        self::assertStringContainsString(
            'local_subs_commerce_prod_map',
            $presentation
        );

        foreach ([
            '/admin/commerce/personal-offers/create.php',
            '/admin/commerce/personal-offers/edit.php',
            '/admin/commerce/personal-offers/view.php',
        ] as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertStringContainsString(
                'business_product_label',
                $source,
                $relative
            );
        }
    }

    public function test_offer_detail_supports_copy_send_now_refresh_and_preview(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/view.php'
        );

        foreach ([
            'personal-offer-copy-link',
            'navigator.clipboard.writeText',
            'commerce_personal_offer_mail_refresh_status',
            "'action' => 'sendmailnow'",
            'CommercePersonalOfferMailService::create($DB)',
            'preview_offer($id)',
            'crm-offers-access-mail-preview-frame',
        ] as $needle) {
            self::assertStringContainsString($needle, $source);
        }
    }

    public function test_secure_link_is_open_before_technical_references(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/view.php'
        );

        $secure = strpos(
            $source,
            "get_string(\n            'commerce_personal_offer_secure_link'"
        );
        $technical = strpos(
            $source,
            "get_string('commerce_personal_offer_technical_title'"
        );

        self::assertNotFalse($secure);
        self::assertNotFalse($technical);
        self::assertLessThan($technical, $secure);
        self::assertStringContainsString(
            '$securecontent,' . "\n" . '        true',
            $source
        );
    }

    public function test_mail_runtime_preview_and_queue_share_the_same_request_builder(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferMailService.php'
        );

        self::assertStringContainsString(
            'private function request_for_offer',
            $source
        );
        self::assertStringContainsString(
            'public function preview_offer',
            $source
        );
        self::assertStringContainsString(
            '$this->request_for_offer($offerid, $campaignid, $memberid)',
            $source
        );
    }

    public function test_queue_label_says_queue_not_send(): void {
        $root = dirname(__DIR__, 3);
        $fr = file_get_contents(
            $root . '/lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            "commerce_personal_offer_mail_queue_single'] = "
            . "'Mettre l’e-mail en file d’attente'",
            $fr
        );
    }


    public function test_offer_send_now_controller_sets_page_context_and_returns_to_offer(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/personal-offers/action.php'
        );

        self::assertStringContainsString(
            '$PAGE->set_context($context);',
            $source
        );
        self::assertStringContainsString(
            'if ($action === \'sendmailnow\')',
            $source
        );
        self::assertStringContainsString(
            'CommerceMailAdminService',
            $source
        );
        self::assertStringContainsString(
            '/admin/commerce/personal-offers/view.php',
            $source
        );
    }

    public function test_n79_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
