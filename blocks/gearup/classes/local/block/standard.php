<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Block.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\block;
defined('MOODLE_INTERNAL') || die();

use block_gearup\di;
use block_gearup\local\permission\access_permissions;
use block_gearup\output\tracker;
use context_system;
use core\output\notification;
use html_writer;

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

/**
 * Block.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class standard extends \block_base {

    /** @var object */
    protected $lm;

    /**
     * Applicable formats.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'site' => $this->lm->use_sitewide(),
            'my' => $this->lm->use_sitewide(),
            'course' => true,
        ];
    }

    /**
     * The plugin has a settings.php file.
     *
     * @return boolean True.
     */
    public function has_config() {
        return true;
    }

    /**
     * Init.
     *
     * @return void
     */
    public function init() {
        // At this stage, this is not the title, it is the name displayed in the block selector.
        $this->title = get_string('pluginname', 'block_gearup');
        $this->lm = di::get('lm');
    }

    /**
     * Get content.
     *
     * @return stdClass
     */
    public function get_content() {
        global $PAGE, $USER;

        if (isset($this->content)) {
            return $this->content;
        }

        $this->content = new \stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // Resolve the relevant context.
        $cm = di::get('context_manager');
        $context = $cm->normalise_context($this->context);
        $accessperms = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $canview = $accessperms->can_access();
        $canedit = $accessperms->can_manage();

        // Hide the block to non-logged in users, guests and those who cannot view the block.
        if (!$USER->id || isguestuser() || !$canview) {
            return $this->content;
        } else if ($context instanceof context_system && !$this->lm->use_sitewide()) {
            return $this->content;
        }

        $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($context, $PAGE->context);
        $output = di::get('renderer');

        $this->content = new \stdClass();
        $this->content->text = '';
        $this->content->text .= $this->render_notice($accessperms);
        if (empty($this->config->hidetracker)) {
            $this->content->text .= $output->render(new tracker($context, $USER->id, $urlresolver, $this->page->url));
        } else if ($canedit) {
            $notification = new notification(get_string('trackerhiddenbyblocksetting', 'block_gearup'),
                notification::NOTIFY_WARNING);
            $notification->set_announce(false);
            $notification->set_show_closebutton(false);
            $this->content->text .= $output->render($notification);
        }
        $this->content->text .= $output->navigation_on_block($urlresolver, $accessperms);

        return $this->content;
    }

    /**
     * Render a notice.
     *
     * @param access_permissions $accessperms The access permissions.
     * @return string
     */
    protected function render_notice(access_permissions $accessperms) {
        if (!$accessperms->can_manage() || !$this->lm->is_evaluating()) {
            return '';
        }
        return html_writer::div(strip_tags(markdown_to_html(get_string('evaluationnoticeshort', 'block_gearup')),
            '<a><em><strong>'), 'gu-bg-yellow-100 gu-border-1 gu-border-black gu-my-4 gu-rounded gu-px-2 gu-py-1 gu-text-xs');
    }

    /**
     * Specialization.
     *
     * Happens right after the initialisation is complete.
     *
     * @return void
     */
    public function specialization() {
        parent::specialization();
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title, true, ['context' => $this->page->context]);
        }
    }

}
