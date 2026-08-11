<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_single_image_k13b3_test extends advanced_testcase {

    public function test_offer_image_is_rendered_only_after_cta_and_signature(): void {
        $root = dirname(__DIR__, 3);

        $mustache = (string)file_get_contents(
            $root . '/templates/commerce/mail/personal_offer.mustache'
        );
        $template = (string)file_get_contents(
            $root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php'
        );

        // No image remains in the offer body before the generic CTA.
        $this->assertStringNotContainsString('personaloffer.hasmailimage', $mustache);
        $this->assertStringNotContainsString('personaloffer.mailimageurl', $mustache);

        // The unique image is appended by the after-CTA hook, after the signature.
        $methodpos = strpos($template, 'primary_action_after_html');
        $signaturepos = strpos($template, 'personaloffer_after_cta_signature', $methodpos);
        $imagepos = strpos($template, "mailimageurl", $signaturepos);

        $this->assertNotFalse($methodpos);
        $this->assertNotFalse($signaturepos);
        $this->assertNotFalse($imagepos);
        $this->assertLessThan($imagepos, $signaturepos);
    }
}
