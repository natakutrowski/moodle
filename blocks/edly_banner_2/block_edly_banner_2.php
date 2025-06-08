<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');

class block_edly_banner_2 extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_banner_2');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');

        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->title = 'Explore variety of quality courses and gain new skills';
            $this->config->body = 'Learn 100% online with world class universities and industry experts.';
            $this->config->button_text = 'Sign Up Now';
            $this->config->button_link = $CFG->wwwroot . '/login/signup.php';
            $this->config->right_button_text = 'Find Courses';
            $this->config->right_button_link = $CFG->wwwroot . '/course';
            $this->config->info_card1_user_img = $CFG->wwwroot . '/theme/edly/pix/main-banner/user10.jpg';
            $this->config->info_card1_top_title = 'User Experience Class';
            $this->config->info_card1_title = 'Tomorrow is our "When I Grow Up" Spirit Day!';
            $this->config->info_card2_icon = 'ri-shield-check-line';
            $this->config->info_card2_title = 'Congratulations';
            $this->config->info_card2_content = 'Your admission completed';
            $this->config->info_card3_icon = 'ri-star-fill';
            $this->config->info_card3_content = '4.5 (6.8k Reviews)';
            $this->config->info_card3_title = 'Get job-ready for an inseptual demand career';
            $this->config->banner_img = $CFG->wwwroot . '/theme/edly/pix/main-banner/main2.png';
            $this->config->bg_shape = $CFG->wwwroot . '/theme/edly/pix/main-banner/ellipse2.png';
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
        $info_card1_user_img = !empty($this->config->info_card1_user_img) ? $this->config->info_card1_user_img : '';
        $info_card1_top_title = !empty($this->config->info_card1_top_title) ? $this->config->info_card1_top_title : '';
        $info_card1_title = !empty($this->config->info_card1_title) ? $this->config->info_card1_title : '';
        $info_card2_icon = !empty($this->config->info_card2_icon) ? $this->config->info_card2_icon : '';
        $info_card2_title = !empty($this->config->info_card2_title) ? $this->config->info_card2_title : '';
        $info_card2_content = !empty($this->config->info_card2_content) ? $this->config->info_card2_content : '';
        $info_card3_icon = !empty($this->config->info_card3_icon) ? $this->config->info_card3_icon : '';
        $info_card3_content = !empty($this->config->info_card3_content) ? $this->config->info_card3_content : '';
        $info_card3_title = !empty($this->config->info_card3_title) ? $this->config->info_card3_title : '';
        $banner_img = !empty($this->config->banner_img) ? $this->config->banner_img : '';
        $bg_shape = !empty($this->config->bg_shape) ? $this->config->bg_shape : '';
        $shape = !empty($this->config->shape) ? $this->config->shape : '';
        
        $text = '';
        $text .= '
        <div class="main-banner-wrap-area">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="main-banner-wrap-content">
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

                    <div class="col-lg-6 col-md-12">
                        <div class="main-banner-wrap-image" data-aos="fade-left" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">';
                            if($banner_img):
                                $text .= '
                                <img src="'.edly_block_image_process($banner_img).'" alt="'.strip_tags($title).'">';
                            endif;

                            if($info_card1_title || $info_card1_top_title || $info_card1_user_img):
                            $text .= '
                                <div class="banner-box" data-aos="fade-left" data-aos-delay="120" data-aos-duration="1200" data-aos-once="true">
                                    <div class="info d-flex align-items-center">';
                                        if($info_card1_user_img):
                                            $text .= '
                                            <img src="'.edly_block_image_process($info_card1_user_img).'" class="rounded-circle" alt="'.strip_tags($info_card1_title).'">';
                                        endif;
                                        $text .= '

                                        <div class="title">
                                            <span>'.format_text($info_card1_top_title, FORMAT_HTML, array('filter' => true)).'</span>
                                        </div>
                                    </div>
                                    <h3>
                                        '.format_text($info_card1_title).'
                                    </h3>
                                </div>';
                            endif; 
                            
                            if($info_card2_icon || $info_card2_title || $info_card2_content):
                            $text .= '
                                <div class="banner-congratulations" data-aos="fade-left" data-aos-delay="140" data-aos-duration="1400" data-aos-once="true">
                                    <div class="content">';
                                        if($info_card2_icon):
                                            $text .= '
                                            <div class="icon">
                                                <i class="'.$info_card2_icon.'"></i>
                                            </div>';
                                        endif;
                                        $text .= '

                                        <h3>'.format_text($info_card2_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                        <p>'.format_text($info_card2_content, FORMAT_HTML, array('filter' => true)).'</p>
                                    </div>
                                </div>';
                            endif;

                            if($info_card3_icon || $info_card3_title || $info_card3_content):
                            $text .= '
                                <div class="banner-reviews" data-aos="fade-left" data-aos-delay="150" data-aos-duration="1500" data-aos-once="true">
                                    <span><i class="'.$info_card3_icon.'"></i> '.format_text($info_card3_content, FORMAT_HTML, array('filter' => true)).'</span>
                                    <h3>
                                    '.format_text($info_card2_title, FORMAT_HTML, array('filter' => true)).'
                                    </h3>
                                </div>';
                            endif; 
                            $text .= '
                        </div>
                    </div>
                </div>
            </div>';

            if($bg_shape):
                $text .= '            
                <div class="banner-wrap-ellipse">
                    <img src="'.edly_block_image_process($bg_shape).'" class="rounded-circle" alt="'.strip_tags($title).'">
                </div>';
            endif;

            if($shape):
                $text .= '            
                <div class="banner-wrap-shape">
                    <img src="'.edly_block_image_process($shape).'" class="rounded-circle" alt="'.strip_tags($title).'">
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