<?php
declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\currency\CommerceEcbFxRateSource;
use local_subscriptions\commerce\currency\CommerceFxRateBook;
defined('MOODLE_INTERNAL') || die();

final class commerce_796d2_fx_on_demand_refresh_test extends advanced_testcase {
    private const ECB_FIXTURE = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
<Cube><Cube time="2026-08-21"><Cube currency="USD" rate="1.1700"/><Cube currency="JPY" rate="172.5000"/><Cube currency="GBP" rate="0.8600"/></Cube></Cube>
</gesmes:Envelope>
XML;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('commerce_enabled_currencies', 'EUR,USD,JPY,TND,GBP', 'local_subscriptions');
    }

    public function test_ecb_fixture_supports_eur_and_cross_rates_without_network(): void {
        $result = CommerceEcbFxRateSource::from_xml(self::ECB_FIXTURE, 'EUR', ['USD', 'JPY', 'TND']);
        $this->assertSame('ECB', $result->source);
        $this->assertSame('2026-08-21', $result->referencedate);
        $this->assertSame('1.17', $result->rates['USD']);
        $this->assertSame('172.5', $result->rates['JPY']);
        $this->assertSame(['TND'], $result->unavailable);

        $cross = CommerceEcbFxRateSource::from_xml(self::ECB_FIXTURE, 'GBP', ['EUR', 'USD', 'JPY']);
        $this->assertEqualsWithDelta(1 / 0.86, (float)$cross->rates['EUR'], 0.00000001);
        $this->assertEqualsWithDelta(1.17 / 0.86, (float)$cross->rates['USD'], 0.00000001);
        $this->assertEqualsWithDelta(172.5 / 0.86, (float)$cross->rates['JPY'], 0.00000001);
    }

    public function test_refresh_is_not_persisted_until_admin_explicitly_applies_it(): void {
        $book = new CommerceFxRateBook(new CommerceCurrencyRegistry());
        $book->save('EUR', ['TND' => '3.365'], 'manual');
        $preview = CommerceEcbFxRateSource::from_xml(self::ECB_FIXTURE, 'EUR', ['USD', 'JPY', 'TND']);

        $this->assertNull($book->rate_for('USD'));
        $this->assertSame('3.365', $book->rate_for('TND'));

        $book->apply_refresh('EUR', $preview->rates, $preview->source, 1234567890);
        $this->assertSame('1.17', $book->rate_for('USD'));
        $this->assertSame('172.5', $book->rate_for('JPY'));
        $this->assertSame('3.365', $book->rate_for('TND'));
        $this->assertSame('ECB', $book->rates()['USD']['source']);
        $this->assertSame('manual', $book->rates()['TND']['source']);
    }

    public function test_no_scheduled_task_is_added_for_fx_refresh(): void {
        global $CFG;
        $tasks = $CFG->dirroot . '/local/subscriptions/db/tasks.php';
        if (!is_file($tasks)) {
            $this->assertTrue(true);
            return;
        }
        $contents = file_get_contents($tasks);
        $this->assertStringNotContainsString('CommerceEcbFxRateSource', $contents);
        $this->assertStringNotContainsString('fx_refresh', strtolower($contents));
    }
}
