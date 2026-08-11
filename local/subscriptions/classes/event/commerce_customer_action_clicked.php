<?php

declare(strict_types=1);

namespace local_subscriptions\event;

defined('MOODLE_INTERNAL') || die();

/** Records a customer click on a tracked post-purchase action. */
final class commerce_customer_action_clicked extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_subscriptions_commerce_purchase';
    }

    public static function get_name(): string {
        return get_string('event_commerce_customer_action_clicked', 'local_subscriptions');
    }

    public function get_description(): string {
        return "The customer clicked action '{$this->other['action']}' from '{$this->other['source']}' "
            . "for purchase id '{$this->objectid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/subscriptions/order_details.php', [
            'reference' => (string)$this->other['reference'],
        ]);
    }

    protected function validate_data(): void {
        parent::validate_data();
        foreach (['action', 'source', 'reference'] as $key) {
            if (empty($this->other[$key])) {
                throw new \coding_exception("Commerce customer action event requires '{$key}'.");
            }
        }
    }
}
