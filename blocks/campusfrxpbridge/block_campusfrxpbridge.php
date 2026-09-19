<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Invisible technical bridge used only for Level Up XP's block callback API.
 *
 * The plugin must advertise at least one applicable format because Moodle's
 * block self-test rejects blocks for which every format is disabled. Actual
 * block instances are nevertheless forbidden by can_block_be_added().
 */
class block_campusfrxpbridge extends block_base {
    public function init(): void {
        $this->title = get_string('pluginname', 'block_campusfrxpbridge');
    }

    public function applicable_formats(): array {
        return ['all' => true];
    }

    public function can_block_be_added(moodle_page $page): bool {
        return false;
    }

    public function instance_allow_multiple(): bool {
        return false;
    }

    public function instance_can_be_edited(): bool {
        return false;
    }

    public function hide_header(): bool {
        return true;
    }

    public function get_content(): stdClass {
        if ($this->content === null) {
            $this->content = new stdClass();
            $this->content->text = '';
            $this->content->footer = '';
        }

        return $this->content;
    }
}
