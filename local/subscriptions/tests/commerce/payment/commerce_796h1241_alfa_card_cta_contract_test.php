<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1241_alfa_card_cta_contract_test extends \advanced_testcase {
    public function test_alfa_card_uses_explicit_cta_after_terms_validation(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        $validation = strpos($amd, 'form.reportValidity?.();');
        $prepare = strpos($amd, 'await prepare(');
        $modal = strpos($amd, 'officialButton.click();');

        self::assertNotFalse($validation);
        self::assertNotFalse($prepare);
        self::assertNotFalse($modal);
        self::assertLessThan($prepare, $validation);
        self::assertLessThan($modal, $prepare);

        self::assertStringContainsString(
            'The primary checkout CTA remains the explicit action',
            $amd
        );
    }

    public function test_card_selection_itself_has_no_auto_submit_listener(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringNotContainsString(
            'data.alfaCardDirect',
            $amd
        );
        self::assertStringNotContainsString(
            'wireDirectCardAction',
            $amd
        );
        self::assertStringContainsString(
            "input.addEventListener(\n            'change'",
            $amd
        );
        self::assertStringContainsString(
            'refreshSubmit();',
            $amd
        );
    }
}
