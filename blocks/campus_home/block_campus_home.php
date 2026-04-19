<?php

use local_subscriptions\trial_manager;

global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
require_once($CFG->dirroot . '/local/campus/lib.php');

class block_campus_home extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_campus_home');
    }

    /**
     * Valeurs par défaut
     */
    public function specialization() {
        global $CFG;

        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        $this->config->title        = $this->config->title        ?? get_string('title', 'block_campus_home');
        $this->config->body         = $this->config->body         ?? get_string('body', 'block_campus_home');
        $this->config->css_class    = $this->config->css_class    ?? 'bloc1';
        $this->config->bg_image     = $this->config->bg_image     ?? '';
        $this->config->bg_image_mob = $this->config->bg_image_mob ?? '';
        $this->config->left_button_text  = $this->config->left_button_text  ?? '';
        $this->config->left_button_link  = $this->config->left_button_link  ?? '';
        $this->config->right_button_text = $this->config->right_button_text ?? '';
        $this->config->right_button_link = $this->config->right_button_link ?? '';

    }

    public function get_content() {
        global $CFG, $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // État utilisateur
        $isguest = (!isloggedin() || isguestuser());
        $istrial = (!$isguest && trial_manager::user_has_active_trial($USER->id));

        // Greeting
        $greeting = '';
        if (!$isguest && !empty($USER->firstname)) {
            $greeting = get_string('hero_greeting', 'block_campus_home', $USER->firstname);
        }

        // Config
        $title     = $this->config->title ?? '';
        $body      = $this->config->body ?? '';
        $cssclass  = trim($this->config->css_class ?? '');
        $bgdesktop = trim($this->config->bg_image ?? '');
        $bgmobile  = trim($this->config->bg_image_mob ?? '');
        $leftbtn_text  = trim($this->config->left_button_text ?? '');
        $leftbtn_link  = trim($this->config->left_button_link ?? '');
        $rightbtn_text = trim($this->config->right_button_text ?? '');
        $rightbtn_link = trim($this->config->right_button_link ?? '');


        $blockclasses = 'block-campus-home';
        if ($cssclass !== '') {
            $blockclasses .= ' ' . s($cssclass);
        }

        // HTML
        $text = '';
        $text .= '<section class="'.$blockclasses.'">';

        // Background images
        if ($bgdesktop || $bgmobile) {
            $text .= '<picture class="campus-home-bg">';
            if ($bgmobile) {
                $text .= '<source media="(max-width: 767px)" srcset="'.s($bgmobile).'">';
            }
            if ($bgdesktop) {
                if ($title) {
                    $text .= '<img src="'.s($bgdesktop).'" alt="'. format_text($title, FORMAT_HTML, ['filter' => true]) .'" loading="lazy">';
                }
                else {
                    $text .= '<img src="'.s($bgdesktop).'" alt="" loading="lazy">';
                }
            }
            $text .= '</picture>';
        }

        // Content
        $text .= '
            <div class="campus-home-inner">
                <div class="campus-home-content">';

        // SEO content (invisible)
        if ($title) {
            $tag = ($cssclass === 'bloc1') ? 'h1' : 'h2';

            $text .= "
                <$tag class='campus-seo-hidden'>"
                    . format_text($title, FORMAT_HTML, ['filter' => true]) .
                "</$tag>";
        }

        if ($body) {
            $text .= "
                <div class='campus-seo-hidden'>"
                    . format_text($body, FORMAT_HTML, ['filter' => true]) .
                "</div>";
        }

        // if ($greeting) {
        //     $text .= '
        //             <p class="campus-home-greeting">'.s($greeting).'</p>';
        // }

        $text .= '
                </div>
            </div>';

        if ($leftbtn_text || $rightbtn_text) {
            $text .= '<div class="campus-home-ctas">';

            if ($leftbtn_text && $leftbtn_link) {
                $text .= '
                    <a href="'.s($leftbtn_link).'"
                    class="campus-home-btn campus-home-btn-primary">
                        <span>'.format_text($leftbtn_text, FORMAT_HTML, ['filter' => true]).'</span>
                    </a>';
            }

            if ($rightbtn_text && $rightbtn_link) {
                $text .= '
                    <a href="'.s($rightbtn_link).'"
                    class="campus-home-btn campus-home-btn-secondary">
                        <span>'.format_text($rightbtn_text, FORMAT_HTML, ['filter' => true]).'</span>
                    </a>';
            }

            $text .= '</div>';
        }
        $text .= '
        </section>';


        $this->content->text   = $text;
        $this->content->footer = '';

        return $this->content;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return [
            'all' => true,
            'my' => false,
            'admin' => false,
            'course' => true,
        ];
    }
}
