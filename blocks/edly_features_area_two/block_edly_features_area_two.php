<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_features_area_two extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_features_area_two');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();

            $this->config->top_title    = 'Transform Your Life';
            $this->config->title        = 'Improving lives through <span>learning</span>';

            $this->config->icon1                    = 'ri-moon-clear-line';
            $this->config->features_title1          = 'Earn certificates and degrees';
            $this->config->features_content1        = 'Break into a new field like format technology or data science get started.';

            $this->config->icon2                    = 'ri-stack-line';
            $this->config->features_title2          = 'Learn anything together';
            $this->config->features_content2        = 'Break into a new field like format technology or data science get started.';

            $this->config->icon3                    = 'ri-star-line';
            $this->config->features_title3          = 'Learn with experts';
            $this->config->features_content3        = 'Break into a new field like format technology or data science get started.';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        $this->content         =  new stdClass;

        $features_number = 3;
        if(isset($this->config->features_number)){
            $features_number = $this->config->features_number;
        }

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        $text = '';
        $text .= '
        <div class="improving-area pt-100 pb-75">
            <div class="container">';
                if($this->content->title || $this->content->top_title):
                    $text .= '
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text( $this->content->top_title, FORMAT_HTML, array('filter' => true) ).'</span>
                        <h2>'.format_text( $this->content->title, FORMAT_HTML, array('filter' => true) ).'</h2>
                    </div>';
                endif;
                $text .= '

                <div class="row justify-content-center">';
                    for($i = 1; $i <= $features_number; $i++) {
                        $icon                   = 'icon' . $i;
                        $features_title         = 'features_title' . $i;
                        $features_content       = 'features_content' . $i;

                        // Icon
                        if(isset($this->config->$icon)) { $icon = $this->config->$icon; }else{ $icon = ''; }

                        // Title
                        if(isset($this->config->$features_title)) { $features_title = $this->config->$features_title; }else{ $features_title = ''; }

                        // Content
                        if(isset($this->config->$features_content)) { $features_content = $this->config->$features_content; }else{ $features_content = ''; }
                        $text .= '
                        <div class="col-lg-4 col-sm-6" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <div class="improving-card">
                                <i class="'.$icon.'"></i>
                                <h3>'.format_text($features_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                <p>'.format_text($features_content, FORMAT_HTML, array('filter' => true)).'</p>
                            </div>
                        </div>';
                    } $text .= '
                </div>
            </div>
        </div>';
        
        $this->content->footer = '';
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

}