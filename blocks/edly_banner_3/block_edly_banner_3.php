<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');

class block_edly_banner_3 extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_banner_3');
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
        global $CFG, $DB;

        if ($this->content !== null) {
          return $this->content;
        }

        $this->content  =  new stdClass;

        $title = !empty($this->config->title) ? $this->config->title : '';
        $body = !empty($this->config->body) ? $this->config->body : '';
        $button_text = !empty($this->config->button_text) ? $this->config->button_text : '';
        $button_link = !empty($this->config->button_link) ? $this->config->button_link : '';
        $right_button_text = !empty($this->config->right_button_text) ? $this->config->right_button_text : '';
        $right_button_link = !empty($this->config->right_button_link) ? $this->config->right_button_link : '';
        $banner_img = !empty($this->config->banner_img) ? $this->config->banner_img : '';
        $shape_two = !empty($this->config->shape_two) ? $this->config->shape_two : '';
        $shape = !empty($this->config->shape) ? $this->config->shape : '';
        
        $text = '';
        $text .= '
        <div class="main-banner-with-large-area">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-5 col-md-12">
                        <div class="main-banner-large-content">
                            <h1 data-aos="fade-right" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">'.format_text($title, FORMAT_HTML, array('filter' => true)).'</h1>
                            <p data-aos="fade-right" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">'.format_text($body, FORMAT_HTML, array('filter' => true)).'</p>
                            <ul class="banner-btn" data-aos="fade-right" data-aos-delay="90" data-aos-duration="900" data-aos-once="true">';
                                if($button_text):
                                    $text .='
                                    <li>
                                        <a href="'.$button_link.'" class="default-btn">'.format_text($button_text, FORMAT_HTML, array('filter' => true)).'</a>
                                    </li>';
                                endif;

                                if($right_button_text):
                                    $text .='
                                    <li>
                                        <a href="'.$right_button_link.'" class="default-btn">'.format_text($right_button_text, FORMAT_HTML, array('filter' => true)).'</a>
                                    </li>';
                                endif;
                                $text .='
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-12">';
                        if($banner_img):
                            $text .= '            
                            <div class="main-banner-large-image" data-aos="fade-left" data-aos-delay="80" data-aos-duration="800" data-aos-once="true" style="background-image:url('.edly_block_image_process($banner_img).');"></div>';
                        endif;
                        $text .= '
                    </div>
                </div>
            </div>';
            if($shape_two):
                $text .= '            
                <div class="banner-large-shape-1">
                    <img src="'.edly_block_image_process($shape_two).'" alt="'.strip_tags($title).'">
                </div>';
            endif;

            if($shape):
                $text .= '            
                <div class="banner-large-shape-2">
                    <img src="'.edly_block_image_process($shape).'" alt="'.strip_tags($title).'">
                </div>';
            endif;
            $text .= '
        </div>';
        
        $this->content         =  new stdClass;
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