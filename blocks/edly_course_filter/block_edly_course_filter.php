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
        global $CFG, $DB, $COURSE, $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;

        $isguest = (!isloggedin() || isguestuser());

        $istrial = function_exists('local_campus_is_trial_user') && local_campus_is_trial_user();
        $isrestricted = $isguest || $istrial;  // ← invité OU compte d’essai


        // Libellé du bouton (i18n/fo)
        $triallabel = get_string('trial_access', 'theme_edly');


        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';} 

        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';} 

        if(!empty($this->config->body)){$this->content->body = $this->config->body;} else {$this->content->body = '';} 

        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){ $this->content->shape_img  = $this->config->shape_img ;
        }else{ $this->content->shape_img  = ''; }

        $courses = [];
        if (!empty($this->config->courses)) {
            $ids = array_map('intval', (array)$this->config->courses);

            // Récupère les cours sélectionnés, triés par nom (alpha)
            list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $recs = $DB->get_records_select('course', "id $insql", $inparams, 'fullname ASC', 'id, fullname, shortname, category');

            foreach ($recs as $rec) {
                $cat = core_course_category::get($rec->category);
                $courses[] = (object)[
                    'id'            => (int)$rec->id,
                    'category'      => (int)$rec->category,
                    'category_name' => $cat->get_formatted_name(),
                ];
            }
        }

        $style = 1;
        if(isset($this->config->style)){
            $style = $this->config->style;
        }
        $text = '';
        
        if($style == 2):
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container">
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                        <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                    </div>

                    <div class="row justify-content-center">';
                        if(!empty($this->config->courses)){

                            foreach ($courses as $course) {
                                if ($DB->record_exists('course', array('id' => $course->id))) {

                                    $edlyCourseHandler = new edlyCourseHandler();
                                    $edlyCourse = $edlyCourseHandler->edlyGetCourseDetails($course->id);

                                    $cardhref = $this->resolve_card_href((int)$course->id, $isrestricted);
                                    $badge    = trim((string)($this->cf_value((int)$course->id, $this->config->label_field ?? 'cardlabel') ?? ''));

                                    $text .= '
                                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                                        <div class="courses-box">
                                            <div class="courses-image">
                                                <a href="'. $cardhref .'">
                                                    '.$edlyCourse->edlyRender->coverImage.'
                                                </a>
                                                '.(!empty($badge) ? '<span class="cf-card-badge">'.s($badge).'</span>' : '');
                                                if($edlyCourse->course_price) {
                                                    $text .= '
                                                    <div class="price">'.format_text(get_config('theme_edly', 'site_currency') .''.$edlyCourse->course_price, FORMAT_HTML, array('filter' => true)).'</div>';
                                                }else{
                                                    $text .= '
                                                    <div class="price">'.format_text(get_config('theme_edly', 'free_course_price'), FORMAT_HTML, array('filter' => true) ).'</div>';
                                                } $text .= '
                                            </div>

                                            <div class="courses-content">
                                            <h3>
                                                <a href="'.$cardhref.'">'.format_text($edlyCourse->fullName, FORMAT_HTML, array('filter' => true)).'</a>
                                            </h3>


                                            '.$this->card_footer((int)$course->id, $isrestricted, $triallabel).'
                                            </div>

                                        </div>
                                    </div>';
                                }
                            }
                        }
                        $text .= '
                    </div>
                    <div class="courses-bottom-content">
                        <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                    </div>
                </div>';

                if($this->content->shape_img):
                    $shape_img = $this->content->shape_img;
                    $text .= '
                    <div class="courses-pot-shape">
                        <img src="'.edly_block_image_process($shape_img).'" class="shape shape-1" alt="'. strip_tags($this->content->title).'">
                    </div>';
                endif;
                $text .= '
            </div>';
        else:
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container">
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                        <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                    </div>

                    <div class="row justify-content-center">';
                        if(!empty($this->config->courses)){

                            foreach ($courses as $course) {
                                if ($DB->record_exists('course', array('id' => $course->id))) {

                                    $edlyCourseHandler = new edlyCourseHandler();
                                    $edlyCourse = $edlyCourseHandler->edlyGetCourseDetails($course->id);

                                    $cardhref = $this->resolve_card_href((int)$course->id, $isrestricted);
                                    $badge    = trim((string)($this->cf_value((int)$course->id, $this->config->label_field ?? 'cardlabel') ?? ''));


                                    $text .= '
                                    <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                                        <div class="courses-card">
                                            <div class="courses-image">
                                                <a href="'. $cardhref .'">
                                                    '.$edlyCourse->edlyRender->coverImage.'
                                                </a>
                                                '.(!empty($badge) ? '<span class="cf-card-badge">'.s($badge).'</span>' : '').'
                                            </div>
                        
                                            <div class="courses-content">
                                                <div class="top-content">

                                                
                                                    <h3>
                                                        <a href="'. $cardhref .'">'.format_text($edlyCourse->fullName, FORMAT_HTML, array('filter' => true)).'</a>
                                                    </h3>';
                                                    if($edlyCourse->course_price) {
                                                        $text .= '
                                                        <div class="price">'.format_text(get_config('theme_edly', 'site_currency') .''.$edlyCourse->course_price, FORMAT_HTML, array('filter' => true)).'</div>';
                                                    }else{
                                                        $text .= '
                                                        <div class="price">'.format_text(get_config('theme_edly', 'free_course_price'), FORMAT_HTML, array('filter' => true) ).'</div>';
                                                    } $text .= '
                                                </div>
                        
                        
                                                ' . $this->card_footer((int)$course->id, $isrestricted, $triallabel) . '

                                            </div>
                                        </div>
                                    </div>';
                                }
                            }
                        }
                        $text .= '
                    </div>
                    <div class="courses-bottom-content">
                        <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                    </div>
                </div>';

                if($this->content->shape_img):
                    $shape_img = $this->content->shape_img;
                    $text .= '
                    <div class="courses-pot-shape">
                        <img src="'.edly_block_image_process($shape_img).'" class="shape shape-1" alt="'. strip_tags($this->content->title).'">
                    </div>';
                endif;
                $text .= '
            </div>';
        endif;

        $this->content->footer = '';

        ob_start();
        local_campus_inject_trial_ui($PAGE);
        $modal = ob_get_clean();
        $text .= $modal;

        $this->content->text   = $text;

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