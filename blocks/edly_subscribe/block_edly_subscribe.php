<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_subscribe extends block_base {

    public function init(): void {
        $this->title = get_string('pluginname', 'block_edly_subscribe');
    }

    public function specialization() {
        if (empty($this->config)) {
            $this->config = (object)[];
        }
        // Réglages du bloc
        $this->config->onlyactive = $this->config->onlyactive ?? 1;
        $this->config->planids    = $this->config->planids    ?? '';
        $this->config->sortbydur  = $this->config->sortbydur  ?? 1;

        // En-tête optionnelle au-dessus des cards
        $this->config->top_title  = $this->config->top_title  ?? get_string('default_top', 'block_edly_subscribe');   // petit texte
        $this->config->title      = $this->config->title      ?? get_string('default_title', 'block_edly_subscribe'); // grand titre
    }

    public function get_content() {
        global $PAGE, $CFG, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        // CSS/JS des plans (comme subscribe.php) + CSS du bloc
        $PAGE->requires->css('/local/subscriptions/styles.css');
        $PAGE->requires->js_call_amd('local_subscriptions/plan_prices', 'init');
        $PAGE->requires->css('/blocks/edly_subscribe/styles.css');

        require_once($CFG->dirroot . '/local/subscriptions/lib/user_subs_lib.php');
        require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');

        // Plans
        $where = [];
        if (!empty($this->config->onlyactive)) { $where['is_active'] = 1; }
        $plans = $DB->get_records('subscription_plan', $where, 'name ASC');

        // Filtre ID
        if (!empty($this->config->planids)) {
            $allow = array_map('intval', array_filter(array_map('trim', explode(',', $this->config->planids)), 'strlen'));
            if ($allow) {
                $plans = array_filter($plans, fn($p) => in_array((int)$p->id, $allow, true));
            }
        }

        // Tri par durée
        if (!empty($this->config->sortbydur) && function_exists('sort_plans_by_duration')) {
            $plans = sort_plans_by_duration($plans, true);
        }

        /** @var \local_subscriptions\output\renderer $renderer */
        $renderer = $PAGE->get_renderer('local_subscriptions');

        $top = format_text($this->config->top_title ?? '', FORMAT_HTML, ['filter' => true]);
        $ttl = format_text($this->config->title ?? '', FORMAT_HTML, ['filter' => true]);

        $html = '';
        $html .= html_writer::start_div('edly-plans-section pt-100 pb-75');
        $html .= html_writer::start_div('container'); // largeur contrôlée

        // En-tête (optionnelle)
        if (!empty(trim(strip_tags($top))) || !empty(trim(strip_tags($ttl)))) {
            $html .= '
            <div class="section-title">
                <span class="sub">'.$top.'</span>
                <h2>'.$ttl.'</h2>
            </div>';
        }

        // Wrapper pour gérer marges/gap/centrage sans toucher au renderer
        $html .= html_writer::start_div('edly-plans-container');
        if (!empty($plans)) {
            $html .= $renderer->render_available_plans($plans);
        } else {
            $html .= html_writer::div(get_string('noplans', 'block_edly_subscribe'), 'alert alert-info');
        }
        $html .= html_writer::end_div(); // edly-plans-container

        $html .= html_writer::end_div(); // container
        $html .= html_writer::end_div(); // edly-plans-section

        $this->content = (object)[
            'text'   => $html,
            'footer' => '',
        ];
        return $this->content;
    }

    public function applicable_formats() {
        return ['all' => true, 'my' => false];
    }

    public function instance_allow_multiple() {
        return true;
    }
}
