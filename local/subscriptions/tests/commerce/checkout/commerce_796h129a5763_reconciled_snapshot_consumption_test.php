<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5763_reconciled_snapshot_consumption_test extends \advanced_testcase {
    public function test_authenticated_reconciliation_consumes_original_guest_snapshot(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_checkout_resume.php'
        );

        $reconcile = strpos(
            $source,
            'CommerceAuthenticatedCartReconciliationService('
        );
        $unset = strpos(
            $source,
            "unset(\$metadata['guest_cart_snapshot']);"
        );
        $transition = strpos(
            $source,
            "\$repository->transition(\$guestsession, 'active'"
        );

        self::assertNotFalse($reconcile);
        self::assertNotFalse($unset);
        self::assertNotFalse($transition);
        self::assertGreaterThan($reconcile, $unset);
        self::assertGreaterThan($unset, $transition);

        self::assertStringContainsString(
            "'guest_cart_snapshot_consumed_at'",
            $source
        );
    }

    public function test_all_owned_message_uses_articles_wording_in_french(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        self::assertStringContainsString(
            "Vous possédez déjà les articles de ce panier. Aucun paiement n’est nécessaire.",
            $source
        );
    }
}
