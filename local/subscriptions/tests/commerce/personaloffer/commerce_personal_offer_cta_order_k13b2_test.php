<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_cta_order_k13b2_test extends advanced_testcase {

    public function test_personal_offer_signature_is_moved_after_cta_before_image(): void {
        $root = dirname(__DIR__, 3);

        $abstract = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/AbstractCommerceMailTemplate.php'
        );
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );
        $renderer = (string)file_get_contents(
            $root . '/classes/mail/MailRenderer.php'
        );

        $this->assertStringContainsString(
            'personaloffer_after_cta_signature',
            $abstract
        );
        $this->assertStringContainsString(
            "\$context['has_editorial_signature'] = false",
            $abstract
        );

        $signaturepos = strpos($template, 'personaloffer_after_cta_signature');
        $imagepos = strpos($template, "mailimageurl", $signaturepos);
        $this->assertNotFalse($signaturepos);
        $this->assertNotFalse($imagepos);
        $this->assertLessThan($imagepos, $signaturepos);

        // Generic mail layout remains CTA then after-button content.
        $buttonpos = strpos($renderer, "'." . '$btn' . ".'");
        $afterpos = strpos($renderer, "'." . '$afterbuttonhtml' . ".'");
        $this->assertNotFalse($buttonpos);
        $this->assertNotFalse($afterpos);
        $this->assertLessThan($afterpos, $buttonpos);
    }
}
