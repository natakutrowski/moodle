<?php
declare(strict_types=1);
namespace local_subscriptions;
use advanced_testcase;
use local_subscriptions\commerce\currency\CommerceEcbFxRateSource;
use local_subscriptions\commerce\currency\CommerceFrankfurterFxRateSource;
use local_subscriptions\commerce\currency\CommerceFxRefreshCoordinator;
use local_subscriptions\commerce\currency\CommerceFxRateRefreshResult;
defined('MOODLE_INTERNAL') || die();
final class commerce_796d3_fx_sources_and_ux_test extends advanced_testcase {
    public const ECB = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref"><Cube><Cube time="2026-08-21"><Cube currency="USD" rate="1.17"/><Cube currency="JPY" rate="172.5"/></Cube></Cube></gesmes:Envelope>
XML;
    public const FRANKFURTER = <<<'JSON'
[{"date":"2026-08-21","base":"EUR","quote":"RUB","rate":89.77},{"date":"2026-08-21","base":"EUR","quote":"TND","rate":3.41},{"date":"2026-08-21","base":"EUR","quote":"AED","rate":4.30}]
JSON;
    protected function setUp(): void { parent::setUp(); $this->resetAfterTest(true); }
    public function test_frankfurter_parser_returns_requested_available_rates(): void {
        $result=CommerceFrankfurterFxRateSource::from_json(self::FRANKFURTER,'EUR',['RUB','TND','AED','BYN']);
        $this->assertSame('89.77',$result->rates['RUB']);
        $this->assertSame('3.41',$result->rates['TND']);
        $this->assertSame('4.3',$result->rates['AED']);
        $this->assertSame(['BYN'],$result->unavailable);
    }
    public function test_coordinator_keeps_ecb_priority_and_uses_secondary_for_gaps(): void {
        $primary=new class implements \local_subscriptions\commerce\currency\CommerceFxRateSourceInterface {
            public function key(): string{return 'ecb';} public function label(): string{return 'ECB';}
            public function refresh(string $basecurrency,array $targetcurrencies):CommerceFxRateRefreshResult{return CommerceEcbFxRateSource::from_xml(commerce_796d3_fx_sources_and_ux_test::ECB,$basecurrency,$targetcurrencies);}
        };
        $secondary=new class implements \local_subscriptions\commerce\currency\CommerceFxRateSourceInterface {
            public function key(): string{return 'frankfurter';} public function label(): string{return 'Frankfurter';}
            public function refresh(string $basecurrency,array $targetcurrencies):CommerceFxRateRefreshResult{return CommerceFrankfurterFxRateSource::from_json(commerce_796d3_fx_sources_and_ux_test::FRANKFURTER,$basecurrency,$targetcurrencies);}
        };
        $batch=(new CommerceFxRefreshCoordinator($primary,$secondary))->refresh('EUR',['USD','JPY','RUB','TND','BYN']);
        $this->assertSame('ECB',$batch->entries['USD']['source']);
        $this->assertSame('ECB',$batch->entries['JPY']['source']);
        $this->assertSame('Frankfurter',$batch->entries['RUB']['source']);
        $this->assertSame('Frankfurter',$batch->entries['TND']['source']);
        $this->assertSame(['BYN'],$batch->unavailable);
    }
    public function test_admin_surfaces_have_bulk_selection_back_link_and_explicit_openai(): void {
        global $CFG;

        $section = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php'
        );
        $currencies = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/currencies.php'
        );
        self::assertIsString($section);
        self::assertIsString($currencies);

        self::assertStringContainsString('data-currency-select-all', $section);
        self::assertStringContainsString('data-currency-deselect-all', $section);
        self::assertStringContainsString(
            "['section' => 'localisation']",
            $currencies
        );
        self::assertStringContainsString(
            "'value'=>'openai_refresh'",
            str_replace(' ', '', $currencies)
        );
        self::assertStringContainsString(
            'CommerceOpenAiFxRateSource',
            $currencies
        );
    }
    public function test_openai_is_not_part_of_free_chain_or_scheduled_tasks(): void {
        global $CFG;
        $coordinator=file_get_contents($CFG->dirroot.'/local/subscriptions/classes/commerce/currency/CommerceFxRefreshCoordinator.php');
        $this->assertStringNotContainsString('CommerceOpenAiFxRateSource',$coordinator);
        $tasks=$CFG->dirroot.'/local/subscriptions/db/tasks.php';
        if (is_file($tasks)) {
            $contents=file_get_contents($tasks);
            $this->assertStringNotContainsString('CommerceOpenAiFxRateSource',$contents);
            $this->assertStringNotContainsString('CommerceFxRefreshCoordinator',$contents);
        } else {$this->assertTrue(true);}
    }
}
