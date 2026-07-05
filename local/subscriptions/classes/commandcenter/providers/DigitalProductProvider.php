<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\repositories\DigitalProductSearchRepository;
use local_subscriptions\subscription_config;
use moodle_url;

final class DigitalProductProvider implements CommandProviderInterface {

    private DigitalProductSearchRepository $repository;

    public function __construct(?DigitalProductSearchRepository $repository = null) {
        $this->repository = $repository ?? new DigitalProductSearchRepository();
    }

    public function search(CommandQuery $query, int $limit = 10): array {
        if ($query->has_direct_entity() && !$query->is_direct_entity('product')) {
            return [];
        }

        if ($query->is_direct_entity('product')) {
            $product = $this->repository->find_by_id((int)$query->id());
            return $product ? $this->format_results([$product], (string)$query->id(), 120) : [];
        }

        $text = trim($query->text());

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        return $this->format_results(
            $this->repository->search($text, $limit),
            $text
        );
    }

    private function format_results(array $products, string $text, int $basescore = 0): array {
        $results = [];

        foreach ($products as $product) {
            $score = $basescore ?: $this->score($text, $product);

            if (empty($product->enabled)) {
                $score -= 15;
            }

            $subtitle = get_string('command_center_product_subtitle', 'local_subscriptions', [
                'slug' => $product->slug,
                'eur' => format_float((float)$product->price_eur, 2),
                'rub' => format_float((float)$product->price_rub, 2),
            ]);

            if (empty($product->enabled)) {
                $subtitle .= ' · ' . get_string('command_center_disabled', 'local_subscriptions');
            }

            $results[] = CommandResult::create()
                ->icon(CommandIcons::DIGITAL_PRODUCT)
                ->type(CommandTypes::digital_product())
                ->title($product->name)
                ->subtitle($subtitle)
                ->url((new moodle_url(subscription_config::digital_product_edit_admin_page(), [
                    'id' => $product->id,
                ]))->out(false))
                ->score($score)
                ->meta('provider', 'digital_products')
                ->meta('entity', 'digital_product')
                ->meta('id', (int)$product->id)
                ->to_array();
        }

        return $results;
    }

    private function score(string $query, object $product): int {
        return CommandScorer::best(
            CommandScorer::id($query, (int)$product->id),
            CommandScorer::slug($query, (string)$product->slug),
            CommandScorer::title($query, (string)$product->name),
            CommandScorer::filename($query, (string)$product->filename)
        );
    }
}