<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\actions\CommandActionKeys;
use local_subscriptions\subscription_config;
use moodle_url;

final class WorkItemProvider implements CommandProviderInterface {

    public function search(CommandQuery $query, int $limit = 10): array {
        global $DB;

        if (!Capabilities::can_view_work_items()) {
            return [];
        }

        $text = trim($query->text());
        $directid = 0;
        if (preg_match('/^(?:work:|WORK-0*)(\d+)$/i', trim($query->raw()), $matches)) {
            $directid = (int)$matches[1];
        }

        if ($directid > 0) {
            $item = $DB->get_record('local_subscriptions_work_item', ['id' => $directid]);
            return $item ? [$this->format($item, 150)] : [];
        }

        if ($query->is_action_mode() || \core_text::strlen($text) < 2) {
            return [];
        }

        $needle = '%' . \core_text::strtolower($text) . '%';
        $items = $DB->get_records_sql(
            "SELECT *
               FROM {local_subscriptions_work_item}
              WHERE " . $DB->sql_like('LOWER(reference)', ':ref', false) . "
                 OR " . $DB->sql_like('LOWER(title)', ':title', false) . "
           ORDER BY timemodified DESC, id DESC",
            ['ref' => $needle, 'title' => $needle],
            0,
            $limit
        );

        return array_map(fn($item) => $this->format($item, 90), array_values($items));
    }

    private function format(
        \stdClass $item,
        int $score
    ): array {
        $url = new moodle_url(
            subscription_config::admin_work_item_view_page(),
            [
                'id' => (int)$item->id,
            ]
        );

        $urlstring = $url->out(false);

        return CommandResult::create()
            ->icon('✅')
            ->type('work_item')
            ->title(
                $item->reference
                    . ' — '
                    . format_string(
                        $item->title
                    )
            )
            ->subtitle(
                get_string(
                    'crm_work_status_' . $item->status,
                    'local_subscriptions'
                )
            )
            ->url(
                $urlstring
            )
            ->action(
                CommandActionKeys::OPEN_URL,
                [
                    'url' => $urlstring,
                ]
            )
            ->score(
                $score
            )
            ->to_array();
    }
}