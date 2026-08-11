<?php

declare(strict_types=1);

namespace local_subscriptions\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Records the download of one concrete digital asset variant.
 */
final class digital_file_downloaded extends \core\event\base {
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_subs_commerce_dig_access';
    }

    public static function get_name(): string {
        return get_string('event_digital_file_downloaded', 'local_subscriptions');
    }

    public function get_description(): string {
        $variant = (string)($this->other['variant'] ?? 'desktop');
        return "The user with id '{$this->userid}' downloaded the '{$variant}' digital asset "
            . "for digital access id '{$this->objectid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/subscriptions/my_digital_products.php');
    }

    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['variant']) || !in_array($this->other['variant'], ['desktop', 'mobile'], true)) {
            throw new \coding_exception('The digital download event requires a valid asset variant.');
        }
        if (empty($this->other['grantreference'])) {
            throw new \coding_exception('The digital download event requires a grant reference.');
        }
    }
}
