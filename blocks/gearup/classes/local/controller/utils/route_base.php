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
 * Route controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller\utils;

use block_gearup\di;
use block_gearup\local\controller\controller;
use block_gearup\local\routing\url;
use block_gearup\local\routing\url_resolver;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\user_utils;
use coding_exception;
use context;
use context_system;
use core\output\notification;
use html_writer;
use moodle_url;
use renderer_base;

/**
 * Route controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class route_base implements controller {

    /** @var \block_gearup\local\permission\access_permissions The permissions. */
    protected $accessperms;
    /** @var \block_gearup\local\routing\routed_request The request. */
    protected $request;
    /** @var context The context. */
    protected $context;
    /** @var object The thing. */
    protected $lm;
    /** @var context The page context. */
    private $pagecontext;
    /** @var url The page URL, not relative to the router. */
    protected $pageurl;
    /** @var renderer_base A renderer. */
    protected $renderer;
    /** @var bool Whether manage permissions ar required. */
    protected $requiremanage = true;
    /** @var bool Whether view permissions ar required. */
    protected $requireview = true;
    /** @var url_resolver URL resolver. */
    protected $urlresolver;

    /** @var bool Whether the page supports groups. */
    protected $supportsgroups = false;
    /** @var bool Whether the page is using groups. */
    private $isusinggroups = null;

    /** @var array The combined request and optional parameters. */
    private $params;
    /** @var array The optional parameters. */
    private $optionalparams;
    /** @var int|null|false The group ID. */
    private $groupid;

    /**
     * Define the page url.
     *
     * @return void
     */
    private function define_pageurl() {
        $paramsdef = array_reduce($this->get_optional_params(), function ($carry, $item) {
            if (isset($item[3]) && !$item[3]) {
                // Do not return parameters which must not be in the URL.
                return $carry;
            }
            $carry[$item[0]] = $item[1];
            return $carry;
        }, []);

        $params = [];
        foreach ($this->optionalparams as $param => $value) {
            // Skip the ones which didn't make the cut.
            if (!array_key_exists($param, $paramsdef)) {
                continue;
            }
            // Skip arguments whose defaults values are the same.
            if ($paramsdef[$param] == $value) {
                continue;
            }
            // Finally, accept the parameter.
            $params[$param] = $value;
        }

        $this->pageurl = new url($this->request->get_url());
        $this->pageurl->params($params);
    }

    /**
     * Resolve the context ID.
     *
     * @return int
     */
    final protected function resolve_context() {
        $contextid = $this->get_param('guctxid');

        // Normalize the context.
        $candidatecontext = $contextid ? context::instance_by_id($contextid) : context_system::instance();
        $context = di::get('context_manager')->normalise_context($candidatecontext);

        // Redirect to the right context page when there is a mismatch.
        if ($context->id != $candidatecontext->id) {
            $this->redirect(new url($this->pageurl, ['guctxid' => $context->id]));
        }

        $this->context = $context;
    }

    /**
     * Resolve the page's context ID.
     *
     * @return int
     */
    final protected function resolve_page_context() {
        $contextid = $this->get_param('gupagectxid');
        $pagecontext = $contextid ? context::instance_by_id($contextid) : $this->context;
        if ($pagecontext instanceof \context_user) {
            $pagecontext = $pagecontext->get_parent_context();
        }
        $this->pagecontext = $pagecontext ?: context_system::instance(); // Fail safe.
    }

    /**
     * Authentication.
     *
     * @return void
     */
    private function require_login() {
        $coursecontext = $this->pagecontext->get_course_context(false);
        require_login($coursecontext ? $coursecontext->instanceid : null, false);
    }

    /**
     * Post authentication.
     *
     * Use this to initialise objects which you'll need throughout the request. Or
     * to perform simple checks that do not need permission checks. You must never
     * perform actions that could be destructive, or could leak information from here.
     *
     * @return void
     */
    protected function post_login() {
        $this->urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($this->context, $this->pagecontext);
        $this->accessperms = di::get('access_permissions_factory')->get_permissions_for_context($this->context);
    }

    /**
     * Check permissions related to accessing the page.
     *
     * @throws moodle_exception When the conditions are not met.
     * @return void
     */
    private function permissions_checks() {
        global $USER;

        // We only need one of, ordered in such a way that the most important check is done first.
        if ($this->requiremanage) {
            $this->accessperms->require_manage();
        } else if ($this->requireview) {
            $this->accessperms->require_access();
        } else {
            throw new coding_exception('Misconfigured controller. Should the page be public?');
        }

        // When the course is explicitly supporting groups, we only allow access when we can validate that the user
        // can view all participants in a course without any group restriction applied. When they cannot, we redirect them.
        if ($this->is_page_supporting_groups() && course_utils::uses_group_mode($this->context)) {
            if (!user_utils::can_view_all_participants($this->context, $USER->id)) {
                $this->redirect($this->urlresolver->reverse('missions'),
                    get_string('accessnotpermittedcannotviewallparticipants', 'block_gearup'),
                    notification::NOTIFY_ERROR
                );
            }
        }
    }

    /**
     * Safe page setup.
     *
     * This is a safe version where no information can be leaked. It does not set
     * the page title, heading, etc. Only the background information that is
     * sufficient to treat the request.
     *
     * @return void
     */
    protected function safe_page_setup() {
        global $PAGE;
        $PAGE->set_context($this->pagecontext);
        $PAGE->set_url($this->pageurl->get_compatible_url());
        $PAGE->set_pagelayout($this->get_page_layout());
        if (!$this->is_page_wide()) {
            $PAGE->add_body_class('limitedwidth');
        }
    }

    /**
     * Page setup.
     *
     * This will be done after we have done some permission checking. This can set
     * the page title, which could contain information such as a person's name, hence
     * why we need to do this after the permission checks.
     *
     * @return void
     */
    protected function page_setup() {
        global $PAGE;
        $PAGE->set_heading($this->get_page_title());
        $headtitle = $this->get_page_html_head_title();
        if ($headtitle) {
            $PAGE->set_title($headtitle);
        }
    }

    /**
     * Collect the parameters.
     *
     * @return void
     */
    private function collect_params() {
        $this->collect_optional_params();
        $this->params = $this->request->get_route()->get_params() + $this->optionalparams;
    }

    /**
     * The optional params expected.
     *
     * Using this format:
     * [
     *     ['paramname', $defaultvalue, PARAM_TYPE],
     *     ['paramname2', $defaultvalue, PARAM_TYPE, $includeinurl],
     *     ...
     * ]
     *
     * The parameter $includeinurl is optional and defaults to true. When false,
     * the value will not be added to the page URL, you can consider it as being
     * dismissed when the user navigated to another page. Make sure to pass it
     * around when you need it. It's useful for values such as 'confirm' which
     * you would want to automatically remove from the page URL.
     *
     * @return array
     */
    protected function define_optional_params() {
        return [];
    }

    /**
     * Read and compute the optional params.
     *
     * This should only be used once, to read the parameters, refer to {@link self::get_param}.
     *
     * @return array
     */
    private function collect_optional_params() {
        $this->optionalparams = array_reduce($this->get_optional_params(), function ($carry, $data) {
            $carry[$data[0]] = optional_param($data[0], $data[1], $data[2]);
            return $carry;
        }, []);
    }

    /**
     * Get all the optional params.
     *
     * @return array
     */
    private function get_optional_params() {
        return array_merge($this->define_optional_params(), [
            ['guctxid', 0, PARAM_INT, true],
            ['gupagectxid', 0, PARAM_INT, true],
        ]);
    }

    /**
     * Get the group ID.
     *
     * @return int|null
     */
    final protected function get_group_id(): ?int {
        if (!$this->is_page_supporting_groups()) {
            throw new \coding_exception('The page must explicitly disclose that it supports groups.');
        }

        if (!isset($this->groupid)) {
            $this->groupid = false;
            if (course_utils::uses_group_mode($this->context)) {
                $groupid = groups_get_course_group(course_utils::get_course($this->context), true);
                $this->groupid = !$groupid ? null : (int) $groupid;
            }
        }
        return $this->groupid ?: null;
    }

    /**
     * The page layout to use.
     *
     * @return string
     */
    protected function get_page_layout() {
        return 'course';
    }

    /**
     * The page title (in <head>).
     *
     * @return string
     */
    abstract protected function get_page_html_head_title();

    /**
     * The page title.
     *
     * @return string
     */
    protected function get_page_title() {
        global $COURSE;
        return format_string($COURSE->fullname);
    }

    /**
     * Get the page URL for actions.
     *
     * This returns a URL that contains all necessary arguments to perform a safe refresh
     * ensuring that all data is contained within the URL. This is especially important
     * when using groups as the group is stored in the session and as such not including
     * in the URL could caused the group to be changed another tab.
     *
     * @return url
     */
    protected function get_page_url_for_actions(): url {
        if ($this->is_page_using_groups()) {
            return new url($this->pageurl, ['group' => $this->get_group_id()]);
        }
        return new url($this->pageurl);
    }

    /**
     * Read one of the parameters.
     *
     * This includes request, and GET/POST parameters.
     *
     * @param string $name The parameter name.
     * @return mixed
     */
    final protected function get_param($name) {
        if (!array_key_exists($name, $this->params)) {
            throw new coding_exception('Unknown parameter: ' . $name);
        }
        return $this->params[$name];
    }

    /**
     * Get the renderer.
     *
     * @return \block_gearup\output\renderer
     */
    protected function get_renderer() {
        if (!$this->renderer) {
            $this->renderer = di::get('renderer');
        }
        return $this->renderer;
    }

    /**
     * Whether the page is supporting groups.
     *
     * This must be enabled for group feature to kick in, and other verifications to be done.
     *
     * @return bool
     */
    protected function is_page_supporting_groups(): bool {
        return $this->supportsgroups;
    }

    /**
     * Whether the page is using groups.
     *
     * This requires the page to support groups.
     *
     * @return bool
     */
    final protected function is_page_using_groups(): bool {
        if (!isset($this->isusinggroups)) {
            if (!$this->is_page_supporting_groups()) {
                throw new coding_exception('The page must explicitly disclose that it supports groups.');
            }
            $this->isusinggroups = course_utils::uses_group_mode($this->context);
        }
        return $this->isusinggroups;
    }

    /**
     * Whether the page is wide.
     *
     * @return bool
     */
    protected function is_page_wide() {
        return false;
    }

    /**
     * Handle the request.
     *
     * @param \block_gearup\local\routing\routed_request $request The request.
     * @return void
     */
    final public function handle(\block_gearup\local\routing\request $request) {
        if (!$request instanceof \block_gearup\local\routing\routed_request) {
            throw new coding_exception('Routed request must be used here...');
        }
        $this->lm = di::get('lm');
        $this->request = $request;
        $this->collect_params();
        $this->define_pageurl();
        $this->resolve_context();
        $this->resolve_page_context();
        $this->require_login();
        $this->post_login();
        $this->safe_page_setup();
        $this->permissions_checks();
        $this->page_setup();
        $this->pre_content();
        $this->start();
        $this->content();
        $this->end();
    }

    /**
     * What needs to be done prior to any output.
     *
     * This is the place you want to initiate redirections from, treat forms, etc.
     *
     * You must always call the parent.
     *
     * @return void
     */
    protected function pre_content() {
    }

    /**
     * Start the output.
     *
     * @return void
     */
    final protected function start() {
        $output = $this->get_renderer();
        echo $output->header();
        echo html_writer::start_tag('div', ['class' => 'block_gearup']);

        if ($this->lm->is_evaluating() && $this->accessperms->can_manage()) {
            echo html_writer::div(strip_tags(markdown_to_html(get_string('evaluationnotice', 'block_gearup')),
                '<a><em><strong>'
            ), 'gu-bg-yellow-100 gu-border-2 gu-border-black gu-my-4 gu-rounded gu-px-2 gu-py-1 gu-font-bold');
        }
    }

    /**
     * Echo the content.
     *
     * @return void
     */
    abstract protected function content();

    /**
     * Finalise the output.
     *
     * @return void
     */
    final protected function end() {
        global $PAGE;
        echo html_writer::end_tag('div');

        $PAGE->requires->js_call_amd('block_gearup/modal_form', 'registerSimpleOpenFormActionObserver');

        echo $this->get_renderer()->footer();
    }

    /**
     * Helper method to redirect.
     *
     * @param moodle_url $url The URL to go to.
     * @param string $message The redirect message.
     * @return void
     */
    final protected function redirect(?moodle_url $url = null, $message = '', $messagetype = notification::NOTIFY_INFO) {
        if ($url === null) {
            $url = $this->pageurl;
        }
        redirect($url, $message, null, $messagetype);
    }
}
