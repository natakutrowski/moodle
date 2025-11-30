<?php
require_once($CFG->dirroot. '/course/renderer.php');
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
require_once($CFG->dirroot.'/local/campus/lib.php');

global $CFG;
class block_edly_course_filter extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_course_filter');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->style = 1;
            $this->config->class = 'courses-area ptb-100';
            $this->config->title = 'Discover Your Perfect Program In Our Courses';
            $this->config->top_title = 'Popular Courses';
            $this->config->body = 'Enjoy the top notch learning methods and achieve next level skills! You are the creator of your own career &amp; we will guide you through that. <a href="#">Register Free Now!</a>';
        }
    }

    public function get_content() {
        global $DB, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new \stdClass();

        $isguest   = (!isloggedin() || isguestuser());
        $istrial   = function_exists('local_campus_is_trial_user') && local_campus_is_trial_user();
        $restricted = $isguest || $istrial;

        $triallabel = get_string('trial_access', 'theme_edly');

        // Récupère les cours choisis dans la config du block
        $courses = [];
        $ids = !empty($this->config->courses) ? array_map('intval', (array)$this->config->courses) : [];
        if ($ids) {
            list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $courses = $DB->get_records_select('course', "id $insql", $inparams, 'fullname ASC', 'id');
        }

        // Injecte la popup/JS d’essai
        ob_start();
        local_campus_inject_trial_ui($PAGE);
        $modal = ob_get_clean();

        // Rend via le renderer partagé
        /** @var \block_edly_course_filter\output\renderer $renderer */  
        $renderer = $PAGE->get_renderer('block_edly_course_filter');
        $html = $renderer->catalogue(array_values($courses), [
            'style' => (int)($this->config->style ?? 1),
            'class' => (string)('courses-area ptb-100'),
            'title' => (string)($this->config->title ?? ''),
            'top_title' => (string)($this->config->top_title ?? ''),
            'body' => (string)($this->config->body ?? ''),
            'label_field' => (string)($this->config->label_field ?? 'cardlabel'),
            'trial_field' => (string)($this->config->trial_field ?? 'trialcourseid'),
            'real_field'  => (string)($this->config->real_field ?? 'realcourseid'),
            'force_direct_loggedin' => !empty($this->config->force_direct_loggedin) ? 1 : 0,
            'desc_baseurl' => (string)($this->config->desc_baseurl ?? ''), // tu peux y mettre /local/campus/course.php?id={id}&checktrial=1
            'desc_label' => (string)($this->config->desc_label ?? 'En savoir plus'),
            'cta_guest' => (string)($this->config->cta_guest ?? $triallabel),
            'cta_connected' => (string)($this->config->cta_connected ?? 'Accéder au cours'),
            'restricted' => $restricted,
            'shape_img' => (string)($this->config->shape_img ?? ''),
            'hide_desc' => 1,
        ]);

        $this->content->text = $html . $modal;
        $this->content->footer = '';
        return $this->content;
    }


    /**
     * The block can be used repeatedly in a page.
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    function applicable_formats() {
        return array(
            'all' => true,
            'my' => false,
            'admin' => false,
            'course-view' => true,
            'course' => true,
        );
    }

    /** ====== CampusFR: helpers (champ perso + mapping + footer) ====== */

    /** Récupère la valeur d’un champ personnalisé (texte) par shortname. */
    private function cf_value(int $courseid, string $shortname): ?string {
        global $DB;
        $shortname = trim($shortname ?? '');
        if ($shortname === '') { return null; }
        $sql = "SELECT d.value
                FROM {customfield_data} d
                JOIN {customfield_field} f ON f.id = d.fieldid
                JOIN {customfield_category} c ON c.id = f.categoryid
                WHERE d.instanceid = :cid
                AND f.shortname = :shortname
                AND c.component = 'core_course'
                AND c.area = 'course'";
        $params = ['cid' => $courseid, 'shortname' => $shortname];
        $val = $DB->get_field_sql($sql, $params);
        if ($val === false || $val === null) { return null; }
        return trim((string)$val);
    }

    private function cf_int(int $courseid, string $shortname): int {
        $v = $this->cf_value($courseid, $shortname);
        if ($v === null || $v === '') { return 0; }
        return (int)$v;
    }

    /** URL cible principale de la card : invité → essai ; connecté → vrai (si configuré). */
    private function resolve_card_href(int $courseid, bool $isguest): string {
        $trialshort = trim($this->config->trial_field ?? 'trialcourseid');
        $realshort  = trim($this->config->real_field  ?? 'realcourseid');
        $forcedirect = !empty($this->config->force_direct_loggedin);

        $targetid = $courseid;
        if (!$isguest && $forcedirect) {
            $realid = $this->cf_int($courseid, $realshort);
            if ($realid > 0) { $targetid = $realid; }
        } else if ($isguest) {
            $trialid = $this->cf_int($courseid, $trialshort);
            if ($trialid > 0) { $targetid = $trialid; }
        }
        $url = new \moodle_url('/course/view.php', ['id' => $targetid]);
        return $url->out(false);
    }

    /** Lien “En savoir plus”. Supporte {id}, {shortname}, {categoryid}. */
    private function resolve_desc_href(int $courseid): string {
        // Toujours privilégier l'ID du cours RÉEL pour la page "En savoir plus"
        $realshort = trim($this->config->real_field ?? 'realcourseid');
        $descid = $this->cf_int($courseid, $realshort);
        if ($descid <= 0) {
            $descid = $courseid; // fallback si pas de mapping
        }

        $base = trim($this->config->desc_baseurl ?? '');
        if ($base === '') {
            $url = new \moodle_url('/course/view.php', ['id' => $descid]);
            return $url->out(false);
        }

        $c = get_course($descid);
        $search  = ['{id}', '{shortname}', '{categoryid}'];
        $replace = [$descid, $c->shortname ?? '', $c->category ?? 0];
        return str_replace($search, $replace, $base);
    }


    /** Footer commun : bouton gauche “En savoir plus” + CTA à droite. */
    private function card_footer(int $courseid, bool $isguest, string $triallabel): string {
        $desclabel = trim($this->config->desc_label   ?? 'En savoir plus');

        // Libellés CTA configurables + repli propre
        $guestlabel     = trim((string)($this->config->cta_guest ?? ''));
        if ($guestlabel === '') {
            $guestlabel = $triallabel; // fallback : string du thème "Accéder au cours d’essai"
        }
        $connectedlabel = trim((string)($this->config->cta_connected ?? 'Accéder au cours'));

        $cta = $isguest ? $guestlabel : $connectedlabel;

        // URL description = cours RÉEL (déjà géré dans resolve_desc_href)
        $descurl = $this->resolve_desc_href($courseid);

        $html  = '<div class="cf-footer d-flex align-items-center justify-content-between">';
        $html .= '  <a class="btn btn-outline-dark cf-desc" href="'. s($descurl) .'">'. s($desclabel) .'</a>';
        if ($isguest) {
            $redir = $this->trial_redirect_id_for($courseid);
            if ($redir) {
                // Ouvre la popup (pas de href direct)
                $html .= '  <a href="#" class="default-btn cf-cta" data-campus-trial-redirect="'.(int)$redir.'">'. s($guestlabel) .'</a>';
            }
        } else {
            // Connecté → vrai cours (lien direct)
            $ctaurl  = $this->resolve_card_href($courseid, false);
            $html   .= '  <a class="default-btn cf-cta" href="'. s($ctaurl) .'">'. s($connectedlabel) .'</a>';
        }
        $html .= '</div>';
        return $html;
    }

    private function trial_redirect_id_for(int $courseid): ?int {
        // Si le cours cliqué est déjà un cours d’essai (dans la conf), on garde cet id.
        $trialids = local_campus_trial_course_ids();
        if (in_array($courseid, $trialids, true)) { return $courseid; }
        // Sinon, on tente le champ personnalisé "trialcourseid"
        $trialshort = trim($this->config->trial_field ?? 'trialcourseid');
        $mapped = $this->cf_int($courseid, $trialshort);
        if ($mapped > 0 && in_array($mapped, $trialids, true)) { return $mapped; }
        // Fallback : aucun (pas de bouton essai)
        return null;
    }




}