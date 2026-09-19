<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\currency;
use local_subscriptions\currency\Currency;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponseParser;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiResponsesClient;
defined('MOODLE_INTERNAL') || die();
/** Explicit administrator-triggered fallback. Never called by the free chain or cron. */
final class CommerceOpenAiFxRateSource implements CommerceFxRateSourceInterface {
    public function key(): string { return 'openai'; }
    public function label(): string { return 'OpenAI web search'; }
    public function available(): bool {
        return (new OpenAiInboxConfiguration(new OpenAiApiKeyProvider()))->available();
    }
    public function refresh(string $basecurrency, array $targetcurrencies): CommerceFxRateRefreshResult {
        $basecurrency = Currency::sanitize($basecurrency);
        $targets = [];
        foreach ($targetcurrencies as $rawcode) {
            $code = Currency::sanitize((string)$rawcode);
            if ($code !== '' && $code !== $basecurrency && !in_array($code, $targets, true)) { $targets[] = $code; }
        }
        if ($basecurrency === '' || $targets === []) {
            return new CommerceFxRateRefreshResult('OpenAI', $basecurrency, [], [], date('Y-m-d'));
        }
        $configuration = new OpenAiInboxConfiguration(new OpenAiApiKeyProvider());
        if (!$configuration->available()) { throw new \runtime_exception('OpenAI is not configured for CampusFR.'); }
        $response = (new OpenAiResponsesClient($configuration))->create([
            'model' => $configuration->model(),
            'store' => false,
            'max_output_tokens' => 1200,
            'tools' => [['type' => 'web_search']],
            'instructions' => implode("\n", [
                'Retrieve current foreign-exchange reference rates for an administrator.',
                'Use web search and reputable current financial or central-bank sources.',
                'Return a rate only when credible current evidence was found.',
                'Rates must mean exactly: 1 BASE currency = RATE TARGET currency.',
                'Do not estimate or invent a missing rate.',
                'Return only the requested structured JSON.',
            ]),
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => json_encode(['base_currency'=>$basecurrency, 'target_currencies'=>$targets, 'requested_on'=>date('Y-m-d')], JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
                ]],
            ]],
            'text' => ['format' => [
                'type' => 'json_schema', 'name' => 'campusfr_fx_rates', 'strict' => true,
                'schema' => [
                    'type'=>'object',
                    'properties'=>['rates'=>['type'=>'array','items'=>[
                        'type'=>'object',
                        'properties'=>[
                            'currency'=>['type'=>'string'],
                            'rate'=>['type'=>'number'],
                            'reference_date'=>['type'=>'string'],
                        ],
                        'required'=>['currency','rate','reference_date'],
                        'additionalProperties'=>false,
                    ]]],
                    'required'=>['rates'], 'additionalProperties'=>false,
                ],
            ]],
        ]);
        $data = (new OpenAiResponseParser())->structured_data($response);
        $rates = []; $dates = [];
        foreach (($data['rates'] ?? []) as $row) {
            if (!is_array($row)) { continue; }
            $code = Currency::sanitize((string)($row['currency'] ?? ''));
            $rate = (float)($row['rate'] ?? 0);
            if ($code === '' || !in_array($code, $targets, true) || $rate <= 0) { continue; }
            $value = rtrim(rtrim(number_format($rate, 8, '.', ''), '0'), '.');
            $rates[$code] = $value === '' ? '0' : $value;
            $dates[] = trim((string)($row['reference_date'] ?? ''));
        }
        return new CommerceFxRateRefreshResult('OpenAI', $basecurrency, $rates, array_values(array_diff($targets,array_keys($rates))), $dates !== [] ? max($dates) : date('Y-m-d'));
    }
}
