<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\repositories\DigitalPurchaseSearchRepository;
use local_subscriptions\subscription_config;
use moodle_url;

final class DigitalPurchaseProvider implements CommandProviderInterface {

    private DigitalPurchaseSearchRepository $repository;

    public function __construct(?DigitalPurchaseSearchRepository $repository = null) {
        $this->repository = $repository ?? new DigitalPurchaseSearchRepository();
    }

    public function search(CommandQuery $query, int $limit = 10): array {
        if ($query->has_direct_entity() && !$query->is_direct_entity('purchase')) {
            return [];
        }

        if ($query->is_direct_entity('purchase')) {
            $purchase = $this->repository->find_by_id((int)$query->id());
            return $purchase ? $this->format_results([$purchase], (string)$query->id(), 120) : [];
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

    private function format_results(array $purchases, string $text, int $basescore = 0): array {
        $results = [];

        foreach ($purchases as $purchase) {
            $buyer = trim($purchase->firstname . ' ' . $purchase->lastname);
            $title = $buyer !== '' ? $buyer : $purchase->email;
            $score = $basescore ?: $this->score($text, $purchase);

            $subtitle = get_string('command_center_purchase_subtitle', 'local_subscriptions', [
                'product' => $purchase->productname,
                'status' => strtoupper($purchase->status),
                'price' => AdminFormatter::price($purchase->price, $purchase->currency),
                'date' => AdminFormatter::datetime((int)$purchase->creation_date),
            ]);

            $results[] = CommandResult::create()
                ->icon(CommandIcons::DIGITAL_PURCHASE)
                ->type(CommandTypes::digital_purchase())
                ->group('purchases', get_string('command_center_group_purchases', 'local_subscriptions'))
                ->action_label(get_string('command_center_action_view', 'local_subscriptions'))
                ->shortcut('purchase:' . (int)$purchase->id)
                ->title($title)
                ->subtitle($subtitle)
                ->url((new moodle_url(subscription_config::digital_purchase_view_admin_page(), [
                    'id' => $purchase->id,
                ]))->out(false))
                ->score($score)
                ->meta('provider', 'digital_purchases')
                ->meta('entity', 'digital_purchase')
                ->meta('id', (int)$purchase->id)
                ->to_array();
        }

        return $results;
    }

    private function score(string $query, object $purchase): int {
        return CommandScorer::best(
            CommandScorer::id($query, (int)$purchase->id),
            CommandScorer::email($query, (string)$purchase->email),
            CommandScorer::transaction($query, (string)$purchase->transactionid),
            CommandScorer::title($query, (string)$purchase->productname),
            CommandScorer::status($query, (string)$purchase->status)
        );
    }
}