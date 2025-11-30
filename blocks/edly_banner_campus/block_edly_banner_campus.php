<?php

use local_subscriptions\trial_manager;
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
require_once($CFG->dirroot . '/local/campus/lib.php'); // CampusFR: popup Trial

class block_edly_banner_campus extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_banner_campus');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');

        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->title = '<span>5500+</span> Courses Upgrade your learning Skills and Upgrade Life';
            $this->config->body = 'Learn 100% online with world class universities and industry experts.';
            $this->config->button_text = 'Sign Up Now';
            $this->config->button_link = $CFG->wwwroot . '/login/signup.php';
            $this->config->right_button_text = 'Find Courses';
            $this->config->right_button_link = $CFG->wwwroot . '/course';
            $this->config->banner_img = $CFG->wwwroot . '/theme/edly/pix/main-banner/banner-large.webp';
            $this->config->shape_two = $CFG->wwwroot . '/theme/edly/pix/main-banner/shape5.png';
            $this->config->shape = $CFG->wwwroot . '/theme/edly/pix/main-banner/shape4.png';
        }
    }

    public function get_content() {
        global $CFG, $DB, $PAGE, $SITE, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content  = new stdClass;

        // Etat utilisateur
        $isguest = (!isloggedin() || isguestuser());
        $istrial  = trial_manager::user_has_active_trial($USER->id);

        $greeting = '';
        if (!$isguest) {
            $firstname = trim($USER->firstname ?? '');
            if ($firstname !== '') {
                $greeting = get_string('hero_greeting', 'block_edly_banner_campus', $firstname);
            }
        }


        // Labels / config
        $title = !empty($this->config->title) ? $this->config->title : get_string('title','block_edly_banner_campus');
        $body = !empty($this->config->body) ? $this->config->body : get_string('body','block_edly_banner_campus');
        $button_text = !empty($this->config->button_text) ? $this->config->button_text : get_string('button_text','block_edly_banner_campus');
        $button_link = !empty($this->config->button_link) ? $this->config->button_link : '';
        $right_button_text = !empty($this->config->right_button_text) ? $this->config->right_button_text : get_string('right_button_text','block_edly_banner_campus');
        $right_button_link = !empty($this->config->right_button_link) ? $this->config->right_button_link : '';
        $banner_img = !empty($this->config->banner_img) ? $this->config->banner_img : '';
        $shape_two = !empty($this->config->shape_two) ? $this->config->shape_two : '';
        $shape = !empty($this->config->shape) ? $this->config->shape : '';
        $trial_note = get_string('trial_note', 'block_edly_banner_campus');

        $trial_note = get_string('trial_note', 'block_edly_banner_campus');

        // Langues supportées
        $currentlang = current_language();

        $langsfull = [
            'en' => '🇬🇧 English (en)',
            'fr' => '🇫🇷 Français (fr)',
            'ru' => '🇷🇺 Русский (ru)',
        ];

        $langsshort = [
            'en' => '🇬🇧 EN',
            'fr' => '🇫🇷 FR',
            'ru' => '🇷🇺 RU',
        ];

        $langmenu = '';
        if (!empty($langsfull)) {
            $action = $PAGE->url->out(false);

            $langmenu .= '<div class="campus-hero-lang-inner">';

            // Version DESKTOP (full label)
            $langmenu .= '<form method="get" action="'.$action.'" class="langmenu-form langmenu-desktop d-inline">';
            $langmenu .= '<select name="lang" class="form-select langmenu" onchange="this.form.submit()">';
            foreach ($langsfull as $code => $label) {
                $selected = ($code === $currentlang) ? ' selected' : '';
                $langmenu .= '<option value="'.s($code).'"'.$selected.'>'.s($label).'</option>';
            }
            $langmenu .= '</select></form>';

            // Version MOBILE (compact label)
            $langmenu .= '<form method="get" action="'.$action.'" class="langmenu-form langmenu-mobile d-inline">';
            $langmenu .= '<select name="lang" class="form-select langmenu" onchange="this.form.submit()">';
            foreach ($langsshort as $code => $label) {
                $selected = ($code === $currentlang) ? ' selected' : '';
                $langmenu .= '<option value="'.s($code).'"'.$selected.'>'.s($label).'</option>';
            }
            $langmenu .= '</select></form>';

            $langmenu .= '</div>';
        }

        $haslangmenu = ($langmenu !== '');

        // URL "Mes cours" (hub utilisateur connecté)
        $mycourses = new moodle_url('/local/campus/mycourses.php');
        $mycoursesurl = $mycourses->out(false);
        $mycourseslabel = get_string('mycourses_title','local_campus'); // "Mes cours" / "Мои курсы" / "My courses"


        // URLs login / logout / abonnement
        $signupurl = new moodle_url('/local/subscriptions/subscribe.php');
        $loginurl  = new moodle_url('/login/index.php', [
            'returnurl' => $mycoursesurl,  // après login → Mes cours
        ]);

        $logouturl = new moodle_url('/login/logout.php', ['sesskey' => sesskey()]);

        $loginlabel   = get_string('login');   // core
        $logoutlabel  = get_string('logout');  // core
        $subscribelabel = get_string('subscribe', 'local_subscriptions');


        // Logo du thème Edly
        $herologo_desktop = null;
        $herologo_mobile  = null;
        $desk_w = $desk_h = $mob_w = $mob_h = null;

        try {
            $themeconfig = \theme_config::load($PAGE->theme->name);

            // URLs des fichiers
            $herologo_desktop = $themeconfig->setting_file_url('main_logo', 'main_logo');
            $herologo_mobile  = $themeconfig->setting_file_url('mobile_logo', 'mobile_logo');

            // Tailles (en px, uniquement chiffres dans tes settings)
            if (!empty($themeconfig->settings->logo_image_width)) {
                $desk_w = (int)($themeconfig->settings->logo_image_width * 1.25);
            }
            if (!empty($themeconfig->settings->logo_image_height)) {
                $desk_h = (int)($themeconfig->settings->logo_image_height * 1.25);
            }

            if (!empty($themeconfig->settings->mobile_logo_width)) {
                $mob_w = (int)$themeconfig->settings->mobile_logo_width;
            }
            if (!empty($themeconfig->settings->mobile_logo_height)) {
                $mob_h = (int)$themeconfig->settings->mobile_logo_height;
            }
        } catch (\Throwable $e) {
            // on laisse les valeurs à null
        }

        // Styles inline (optionnel, mais pratique pour respecter les tailles)
        $desk_style = '';
        if ($desk_w || $desk_h) {
            $parts = [];
            if ($desk_w) { $parts[] = 'max-width:'.$desk_w.'px'; }
            if ($desk_h) { $parts[] = 'max-height:'.$desk_h.'px'; }
            $desk_style = ' style="'.implode(';',$parts).'"';
        }

        $mob_style = '';
        if ($mob_w || $mob_h) {
            $parts = [];
            if ($mob_w) { $parts[] = 'max-width:'.$mob_w.'px'; }
            if ($mob_h) { $parts[] = 'max-height:'.$mob_h.'px'; }
            $mob_style = ' style="'.implode(';',$parts).'"';
        }



        // URL "Mes cours" (hub utilisateur connecté)
        $mycoursesurl = (new moodle_url('/local/campus/mycourses.php'))->out(false);

        // === CampusFR : déterminer un cours d'essai par défaut pour le bouton "Commencer" ===
        $trialredirect = null;
        if ($isguest && function_exists('local_campus_trial_course_ids')) {
            $trialids = local_campus_trial_course_ids();
            if (!empty($trialids)) {
                // On prend simplement le premier cours d'essai comme destination
                $trialredirect = reset($trialids);
            }
        }

        $text = '';
        $text .= '
            <div class="edly-bleed">
            <div class="main-banner-with-large-area">
                <div class="campus-hero-logo">
                    <a href="'.$CFG->wwwroot.'/">';

        if ($herologo_desktop || $herologo_mobile) {
            // Desktop
            if ($herologo_desktop) {
                $text .= '<img src="'.$herologo_desktop.'" alt="'.format_string($SITE->shortname).'"
                           class="hero-logo-desktop"'.$desk_style.'>';
            }
            // Mobile
            if ($herologo_mobile) {
                $text .= '<img src="'.$herologo_mobile.'" alt="'.format_string($SITE->shortname).'"
                           class="hero-logo-mobile"'.$mob_style.'>';
            }
        } else {
            // Fallback texte si aucun logo n'est configuré
            $text .= '<span class="campus-hero-logo-text">'.format_string($SITE->shortname).'</span>';
        }

        $text .= '
                    </a>
                </div>';



        // Image de fond pleine largeur
        if ($banner_img) {
            $text .= '
                <div class="main-banner-large-image"
                    data-aos="fade-left"
                    data-aos-delay="80"
                    data-aos-duration="800"
                    data-aos-once="true"
                    style="background-image:url('.edly_block_image_process($banner_img).');"></div>';
        }


        // Image de fond pleine largeur
        if ($banner_img) {
            $text .= '
                <div class="main-banner-large-image"
                    data-aos="fade-left"
                    data-aos-delay="80"
                    data-aos-duration="800"
                    data-aos-once="true"
                    style="background-image:url('.edly_block_image_process($banner_img).');"></div>';
        }

        // === MINI "NAVBAR" DANS LE HERO (en haut à droite) ===
        $text .= '
                <div class="campus-hero-nav">
                    <div class="campus-hero-nav-inner">';

        if ($haslangmenu) {
            $text .= '
                        <div class="campus-hero-lang langmenu">'
                            .$langmenu.
                        '</div>';
        }

        // Boutons Abonnement / Connexion/Déconnexion
        $text .= '
                        <div class="campus-hero-ctas">';

        // Abonnement : visible pour invités ET comptes en trial
        if ($isguest || $istrial) {
            $text .= '
                            <a href="'.$signupurl->out(false).'"
                            class="hero-btn hero-btn-primary hero-compact"
                            data-role="subscribe" data-subs-modal="1">
                                <i class="ri-vip-crown-line hero-btn-icon" aria-hidden="true"></i>
                                <span class="hero-btn-label">'.s($subscribelabel).'</span>
                            </a>';
        }
        
        if (!$isguest) {
            // CONNECTÉ (trial ou abo) : "Mes cours" (plein) + "Déconnexion" (compact)
            $text .= '
                      <a href="'. $mycoursesurl .'"
                         class="hero-btn hero-btn-primary hero-compact"
                         data-role="mycourses">
                        <i class="ri-book-mark-line hero-btn-icon" aria-hidden="true"></i>
                        <span class="hero-btn-label">'. s($mycourseslabel) .'</span>
                      </a>';
        }

        // Connexion ou Déconnexion
        if ($isguest) {
            // Invité → bouton Connexion
            $authurl   = $loginurl;
            $authlabel = $loginlabel;
            $authicon  = 'ri-login-box-line';
            $authrole  = 'login';
        } else {
            // Utilisateur connecté (avec ou sans trial) → bouton Déconnexion
            $authurl   = $logouturl;
            $authlabel = $logoutlabel;
            $authicon  = 'ri-logout-box-line';
            $authrole  = 'logout';
        }

        $text .= '
                            <a href="'.$authurl->out(false).'"
                            class="hero-btn hero-btn-secondary hero-expanded"
                            data-role="'.s($authrole).'">
                                <i class="'.$authicon.' hero-btn-icon" aria-hidden="true"></i>
                                <span class="hero-btn-label">'.s($authlabel).'</span>
                            </a>';

        $text .= '
                        </div> <!-- .campus-hero-ctas -->
                    </div> <!-- .campus-hero-nav-inner -->
                </div> <!-- .campus-hero-nav -->';


        // === CONTENU PRINCIPAL CENTRÉ ===
        $text .= '
                <div class="container">
                    <div class="main-banner-large-content">
                        <h1 data-aos="fade-right" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">'
                            .format_text($title, FORMAT_HTML, ['filter' => true]).'</h1>
                        <p class="banner-lead" data-aos="fade-right" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">'
                            .format_text($body, FORMAT_HTML, ['filter' => true]).'</p>';

        if ($greeting !== '') {
            $text .= '
                        <p class="hero-greeting" data-aos="fade-right" data-aos-delay="85" data-aos-duration="800" data-aos-once="true">'
                            .s($greeting).'</p>';
        }

        $text .= '
                        <ul class="banner-btn" data-aos="fade-right" data-aos-delay="90" data-aos-duration="900" data-aos-once="true">';

        // Bouton principal ("Commencer")
        if ($right_button_text) {
            $text .= '<li>';

            if ($isguest && $trialredirect && function_exists('local_campus_inject_trial_ui')) {
                // Invité + cours d’essai → popup trial
                $text .= '
                    <a href="#"
                    class="default-btn cf-cta"
                    data-campus-trial-redirect="'.(int)$trialredirect.'">
                        <span class="hero-main-cta-label">'
                            .format_text($right_button_text, FORMAT_HTML, ['filter' => true]).
                        '</span>
                    </a>';

            } else if ($isguest) {
                // Invité sans trial → lien simple (catalogue/cours)
                $target = $right_button_link !== '' ? $right_button_link : (new moodle_url('/local/campus/courses.php'))->out(false);
                $text .= '
                    <a href="'.$target.'" class="default-btn">
                        <span class="hero-main-cta-label">'
                            .format_text($right_button_text, FORMAT_HTML, ['filter' => true]).
                        '</span>
                    </a>';

            } else {
                // Utilisateur connecté (trial ou abonné)
                if ($istrial) {
                    $mainctatext = get_string('hero_cta_trial_continue', 'block_edly_banner_campus');
                } else {
                    $mainctatext = get_string('hero_cta_subscribed_mycourses', 'block_edly_banner_campus');
                }

                $text .= '
                    <a href="'.$mycoursesurl.'" class="default-btn">
                        <i class="ri-book-mark-fill hero-main-cta-icon pr-2" aria-hidden="true"></i>
                        <span class="hero-main-cta-label">'.s($mainctatext).'</span>
                    </a>';
            }

            $text .= '</li>';
        }


        $text .= '
                        </ul>';

        // Texte sous le bouton : "7 jours/7 days/7 дней..."
        if ($trialredirect) {
            $text .= '
                        <p class="banner-trial-note mt-1" data-aos="fade-right" data-aos-delay="70" data-aos-duration="1000" >'.s($trial_note).'</p>';
        }

        $text .= '
                    </div>
                </div>';

        // Shapes
        if ($shape_two) {
            $text .= '            
                <div class="banner-large-shape-1">
                    <img src="'.edly_block_image_process($shape_two).'" alt="'.strip_tags($title).'">
                </div>';
        }

        if ($shape) {
            $text .= '            
                <div class="banner-large-shape-2">
                    <img src="'.edly_block_image_process($shape).'" alt="'.strip_tags($title).'">
                </div>';
        }

        $text .= '
            </div>
            </div>';



        // === CampusFR : injection du HTML/JS de la popup Trial (même logique que l'autre block) ===
        if ($isguest && $trialredirect && function_exists('local_campus_inject_trial_ui')) {
            ob_start();
            local_campus_inject_trial_ui($PAGE);
            $modal = ob_get_clean();
            $text .= $modal;
        }

        $this->content         = new stdClass;
        $this->content->footer = '';
        $this->content->text   = $text;

        $PAGE->requires->js_amd_inline(<<<'JS'
        require(['jquery'], function($) {
            $(function() {
                var $btns = $('.block_edly_banner_campus .hero-btn');

                function isMobile() {
                    return window.innerWidth <= 767;
                }

                $btns.on('click', function(e) {
                    if (!isMobile()) {
                        return; // sur desktop : on laisse le comportement normal
                    }

                    var $btn = $(this);

                    // Si le bouton n'est PAS encore "expanded", on bascule simplement l'état (sans naviguer)
                    if (!$btn.hasClass('hero-expanded')) {
                        e.preventDefault();

                        $btns.removeClass('hero-expanded').addClass('hero-compact');
                        $btn.removeClass('hero-compact').addClass('hero-expanded');

                        return;
                    }
                    // Si le bouton est déjà expanded → on laisse le click normal (navigation)
                });
            });
        });
        JS
        );


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

}