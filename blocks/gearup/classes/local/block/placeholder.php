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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\block;

use block_gearup\di;

/**
 * Block.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placeholder extends standard {

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
        $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($context, $PAGE->context);
        $canview = $accessperms->can_access();

        // Hide the block to non-logged in users, guests and those who cannot view the block.
        if (!$USER->id || isguestuser() || !$canview) {
            return $this->content;
        }

        $output = di::get('renderer');
        $lm = di::get('lm');
        $canmanagelicence = $accessperms->can_manage_licence();
        if (!$lm->is_activated()) {
            if ($canmanagelicence) {
                $this->content->text = $output->render_from_template('block_gearup/block/activation', [
                    'url' => $urlresolver->reverse('activation')->out(false),
                ]);
            } else {
                $this->content->text = $output->render_from_template('block_gearup/block/activation-readonly', []);
            }
        } else {
            $this->content->text = $output->render_from_template('block_gearup/block/inactive', [
                'canmanagelicence' => $canmanagelicence,
                'url' => $urlresolver->reverse('inactive')->out(false),
            ]);
        }

        return $this->content;
    }

    public function has_config() {
        return false;
    }

}
