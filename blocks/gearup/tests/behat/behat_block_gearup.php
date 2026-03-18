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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use block_gearup\di;
use block_gearup\local\model\mission;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps in plugin block_gearup
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_gearup extends behat_base {

    /**
     * Resolve a page URL.
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch ($page) {
            // This did not exist prior to Moodle 4.3, so we create our own.
            case 'Profile editing':
                return new moodle_url('/user/edit.php', ['returnto' => 'profile']);
        }
        throw new coding_exception("Unrecognised page type '{$page}'.");
    }

    /**
     * Resolve a page instance URL.
     *
     * @param string $type
     * @param string $identifier
     * @return moodle_url
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch ($type) {
            case 'missions':
            case 'streaks':
            case 'insights':
            case 'recruits':
            case 'advanced':
                $context = context_system::instance();
                if (!in_array(strtolower($identifier), ['sys', 'system'])) {
                    $courseid = $this->get_course_id($identifier);
                    $context = context_course::instance($courseid);
                }
                $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($context, null);
                $routename = $type === 'recruits' ? 'users' : $type;
                return $urlresolver->reverse($routename);

            case 'mission':
            case 'quest':
            case 'challenge':
            case 'achievement':
            case 'streak':
                $mission = mission::get_record(['title' => $identifier], MUST_EXIST);
                $urlresolver = di::get('url_resolver_factory')
                    ->get_resolver_for_context(context::instance_by_id($mission->get('contextid')), null);
                return $urlresolver->reverse('mission', ['missionid' => $mission->get('id')]);

            case 'mission assign':
            case 'quest assign':
            case 'challenge assign':
            case 'achievement assign':
            case 'streak assign':
            case 'mission recruits':
            case 'quest recruits':
            case 'challenge recruits':
            case 'achievement recruits':
            case 'streak recruits':
            case 'mission insights':
            case 'quest insights':
            case 'challenge insights':
            case 'achievement insights':
            case 'streak insights':
                $target = explode(' ', $type)[1];
                $routename = 'mission_' . ($target === 'recruits' ? 'users' : $target);
                $mission = mission::get_record(['title' => $identifier], MUST_EXIST);
                $urlresolver = di::get('url_resolver_factory')
                    ->get_resolver_for_context(context::instance_by_id($mission->get('contextid')), null);
                return $urlresolver->reverse($routename, ['missionid' => $mission->get('id')]);
        }
        throw new \coding_exception("Unknown page type '$type' for page identifier '$identifier'");
    }

    /**
     * I activate Level Up Quest.
     *
     * @Given /^I activate Level Up Quest$/
     */
    public function i_activate_level_up_quest() {
        global $CFG;

        // Apparently we cannot used $CFG->forced_plugin_settings, so here is an alternative.
        if (empty($CFG->behat_block_gearup_apihost)) {
            throw new \coding_exception('Tests cannot be run without a defined test Level Up server.');
        }
        set_config('apihost', $CFG->behat_block_gearup_apihost, 'block_gearup');

        $this->execute('behat_auth::i_log_in_as', ['admin']);
        $this->execute('behat_general::i_visit', [new moodle_url('/blocks/gearup/index.php/activation')]);
        $this->execute("behat_forms::i_set_the_field_to", ['activation-key', 'valid-behat-tester']);
        $this->execute("behat_general::i_click_on", [get_string('activate', 'block_gearup'), 'button']);
        $this->execute("behat_general::should_exist", ['has been activated', 'text']);
        $this->execute('behat_auth::i_log_out');
    }

    /**
     * I select a visual.
     *
     * @Given /^I select a visual$/
     */
    public function i_select_a_visual() {
        behat_base::require_javascript_in_session($this->getSession());

        $node = $this->find('radio', 'visual');
        $this->execute_js_on_node($node, '{{ELEMENT}}.checked = true');
    }

    /**
     * I click on a clickable element.
     *
     * This is to capture other types of clickable elements, including those who are out of view.
     *
     * @When I click on :label clickable element
     */
    public function i_click_on_clickable_element($label) {
        behat_base::require_javascript_in_session($this->getSession());

        $contains = "contains(normalize-space(.), '$label')";
        $xpath = "//a[$contains]|//button[$contains]|//*[@role='button' and $contains]|//label[$contains]";
        $node = $this->find('xpath', $xpath);
        $this->execute_js_on_node($node, '{{ELEMENT}}.scrollIntoView();');
        $node->click();
    }

    /**
     * I click on a definition item.
     *
     * This is to target elements within the definition of a definition term.
     *
     * @When I click on :label :type in the definition :term
     */
    public function i_click_on_in_definition($label, $type, $term) {
        behat_base::require_javascript_in_session($this->getSession());

        $contains = "contains(normalize-space(.), '$term')";
        $xpath = "//dt[$contains]/following-sibling::dd";
        $node = $this->find('xpath', $xpath);
        $this->execute('behat_general::i_click_on_in_the', [$label, $type, $node, 'NodeElement']);
    }

    /**
     * Should exist in definition.
     *
     * This is to target elements within the definition of a definition term.
     *
     * @When :label :type should exist in the definition :term
     */
    public function should_exist_in_definition($label, $type, $term) {
        $contains = "contains(normalize-space(.), '$term')";
        $xpath = "//dt[$contains]/following-sibling::dd";
        $node = $this->find('xpath', $xpath);
        $this->execute('behat_general::should_exist_in_the', [$label, $type, $node, 'NodeElement']);
    }

}
