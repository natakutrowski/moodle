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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\form\library_form;
use block_gearup\local\controller\utils\route_base;
use core\output\notification;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class library extends route_base {

    protected $form;

    protected function get_page_html_head_title() {
        return get_string('library', 'block_gearup');
    }

    protected function pre_content() {
        parent::pre_content();

        $form = $this->get_form();
        $form->set_data((object) $this->get_initial_form_data());

        if ($form->is_cancelled()) {
            redirect($this->pageurl);
        } else if ($data = $form->get_data()) {
            file_save_draft_area_files($data->achievementbadges,
                $this->context->id,
                'block_gearup',
                'achievementbadges',
                0,
                library_form::get_achievement_badges_options()
            );
            file_save_draft_area_files($data->questnarrators,
                $this->context->id,
                'block_gearup',
                'questnarrators',
                0,
                library_form::get_quest_narrators_options()
            );
            redirect($this->pageurl, get_string('changessaved', 'block_gearup'), null, notification::NOTIFY_SUCCESS);
        }
    }

    protected function get_form() {
        if (!$this->form) {
            $this->form = new library_form($this->pageurl->get_compatible_url());
        }
        return $this->form;
    }

    /**
     * Get the initial form data.
     *
     * @return array
     */
    protected function get_initial_form_data() {
        $achievementbadgesdraftitemid = file_get_submitted_draft_itemid('achievementbadges');
        file_prepare_draft_area($achievementbadgesdraftitemid,
            $this->context->id,
            'block_gearup',
            'achievementbadges',
            0,
            library_form::get_achievement_badges_options()
        );

        $questnarratorsdraftitemid = file_get_submitted_draft_itemid('questnarrators');
        file_prepare_draft_area($questnarratorsdraftitemid,
            $this->context->id,
            'block_gearup',
            'questnarrators',
            0,
            library_form::get_quest_narrators_options()
        );

        return [
            'achievementbadges' => $achievementbadgesdraftitemid,
            'questnarrators' => $questnarratorsdraftitemid,
        ];
    }

    protected function content() {
        $output = $this->get_renderer();

        $form = $this->get_form();
        ob_start();
        $form->display();
        $formcontent = ob_get_contents();
        ob_end_clean();

        echo $output->navigation_for_management($this->urlresolver, 'library');
        echo $output->render_from_template('block_gearup/library', [
            'form' => $formcontent,
        ]);
    }

}
