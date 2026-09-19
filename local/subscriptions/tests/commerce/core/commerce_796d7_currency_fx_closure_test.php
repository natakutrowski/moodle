<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\currency\CommerceFxRateBook;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d7_currency_fx_closure_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config(
            'commerce_enabled_currencies',
            'EUR,USD,JPY,TND',
            'local_subscriptions'
        );
    }

    public function test_rate_book_preserves_refresh_source_and_reference_date(): void {
        $book = new CommerceFxRateBook(new CommerceCurrencyRegistry());

        $book->apply_refresh_entries(
            'EUR',
            [
                'JPY' => [
                    'rate' => '170',
                    'source' => 'ECB',
                    'referencedate' => '2026-08-21',
                ],
                'TND' => [
                    'rate' => '3.365',
                    'source' => 'Frankfurter',
                    'referencedate' => '2026-08-20',
                ],
            ],
            1234567890
        );

        $rates = $book->rates();

        $this->assertSame('170', $rates['JPY']['rate']);
        $this->assertSame('ECB', $rates['JPY']['source']);
        $this->assertSame('2026-08-21', $rates['JPY']['referencedate']);
        $this->assertSame(
            '2026-08-20',
            $rates['TND']['referencedate']
        );
    }

    public function test_invalid_reference_date_is_not_persisted_as_trusted_metadata(): void {
        $book = new CommerceFxRateBook(new CommerceCurrencyRegistry());

        $book->apply_refresh_entries(
            'EUR',
            [
                'USD' => [
                    'rate' => '1.17',
                    'source' => 'TU',
                    'referencedate' => 'not-a-date',
                ],
            ],
            1234567890
        );

        $this->assertSame(
            '',
            $book->rates()['USD']['referencedate']
        );
    }

    public function test_closure_diagnostics_are_read_only_and_refresh_remains_admin_triggered(): void {
        global $CFG;

        $diagnostics = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/currency/CommerceFxDiagnosticsService.php'
        );
        $currencies = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/currencies.php'
        );

        $this->assertStringContainsString(
            "'automaticrefresh' => false",
            $diagnostics
        );
        $this->assertStringNotContainsString(
            'set_config(',
            $diagnostics
        );
        $this->assertStringContainsString(
            'commerce_fx_diagnostics_title',
            $currencies
        );
        $this->assertStringContainsString(
            "'referencedate'",
            $currencies
        );

        $tasks = $CFG->dirroot . '/local/subscriptions/db/tasks.php';
        if (is_file($tasks)) {
            $taskcontents = file_get_contents($tasks);
            $this->assertStringNotContainsString(
                'CommerceFxRefreshCoordinator',
                $taskcontents
            );
            $this->assertStringNotContainsString(
                'CommerceOpenAiFxRateSource',
                $taskcontents
            );
        } else {
            $this->assertTrue(true);
        }
    }
}
