<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_mail_image_k13b_test extends advanced_testcase {

    public function test_personal_offer_email_supports_default_and_custom_image(): void {
        $root = dirname(__DIR__, 3);

        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );
        $mustache = (string)file_get_contents(
            $root . '/templates/commerce/mail/personal_offer.mustache'
        );
        $mailservice = (string)file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferMailService.php'
        );

        $this->assertStringContainsString('personal-offer-default.jpg', $template);
        $this->assertStringNotContainsString('personaloffer.hasmailimage', $mustache);
        $this->assertStringContainsString('primary_action_after_html', $template);
        $this->assertStringContainsString('mailimageurl', $mailservice);
    }

    public function test_creator_and_editor_accept_custom_email_image(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            '/admin/commerce/personal-offers/create.php',
            '/admin/commerce/personal-offers/edit.php',
        ] as $path) {
            $source = (string)file_get_contents($root . $path);
            $this->assertStringContainsString("enctype' => 'multipart/form-data", $source);
            $this->assertStringContainsString("'mailimage'", $source);
            $this->assertStringContainsString('CommercePersonalOfferMailImageService', $source);
        }
    }

    public function test_offer_mail_image_filearea_is_publicly_served(): void {
        $root = dirname(__DIR__, 3);
        $lib = (string)file_get_contents($root . '/lib.php');

        $this->assertStringContainsString(
            'CommercePersonalOfferMailImageService::FILEAREA',
            $lib
        );
        $this->assertStringContainsString("'cacheability' => 'public'", $lib);
    }

    public function test_support_form_imports_global_html_writer(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/forms/commerce/support/CommerceSupportRequestForm.php'
        );

        $this->assertStringContainsString('use html_writer;', $source);
    }
}
