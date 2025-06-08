<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');

class block_edly_banner_1 extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_banner_1');
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
            $this->config->search_placeholder = 'Search our 12,500+ courses';
            $this->config->search_btn = 'Search Now';
            $this->config->bottom_title = '522,8910 <span>people are learning on edly today.</span>';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        if ($this->content !== null) {
          return $this->content;
        }

        $this->content  =  new stdClass;

        if(!empty($this->config->title)){
            $this->content->title = $this->config->title;
        }else{
            $this->content->title = '';
        }

        if(isset($this->config->body) && !empty($this->config->body)){
            $this->content->body = $this->config->body;
        }else{
            $this->content->body = '';
        }

        if (\core_search\manager::is_global_search_enabled() === false) {
            $this->content->search_placeholder = 'Global searching is not enabled.';
        }else{
            if(isset($this->config->search_placeholder) && !empty($this->config->search_placeholder)){
                $this->content->search_placeholder = $this->config->search_placeholder;
            }else{
                $this->content->search_placeholder = '';
            }
        }

        $url = new moodle_url('/search/index.php');

        if(isset($this->config->search_btn) && !empty($this->config->search_btn)){
            $this->content->search_btn = $this->config->search_btn;
        }else{
            $this->content->search_btn = '';
        }

        if(isset($this->config->placeholder_icon) && !empty($this->config->placeholder_icon)){
            $this->content->placeholder_icon = $this->config->placeholder_icon;
        }else{
            $this->content->placeholder_icon = '';
        }

        if(isset($this->config->bottom_title) && !empty($this->config->bottom_title)){
            $this->content->bottom_title = $this->config->bottom_title;
        }else{
            $this->content->bottom_title = '';
        }

        if(isset($this->config->banner_img) && !empty($this->config->banner_img)){
            $this->content->banner_img = $this->config->banner_img;
        }else{
            $this->content->banner_img = '';
        }

        $shape_image_count = 4;
        for($i = 1; $i <= $shape_image_count; $i++) {
            $shape_img = 'shape_img' .$i;
            if(isset($this->config->$shape_img) && !empty($this->config->$shape_img)){
                $this->content->$shape_img = $this->config->$shape_img;
            }else{
                $this->content->$shape_img = '';
            }
        }
        
        $text = '';
        $text .= '
        <div class="main-banner-area">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-7 col-md-12">
                        <div class="main-banner-content">
                            <h1 data-aos="fade-right" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h1>
                            <p data-aos="fade-right" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>';

                            if($this->content->search_placeholder):
                                $text .= '
                                <form class="banner-search" data-aos="fade-right" data-aos-delay="90" data-aos-duration="900" data-aos-once="true" action="'.$url->out().'">
                                    <label><i class="'.$this->content->placeholder_icon.'"></i></label>
                                    <input type="text" name="q" class="input-search" placeholder="'.format_text($this->content->search_placeholder, FORMAT_HTML, array('filter' => true)).'">';

                                    if($this->content->search_btn):
                                        $text .='
                                        <button type="submit" class="default-btn">'.format_text($this->content->search_btn, FORMAT_HTML, array('filter' => true)).'</button>';
                                    endif;
                                        $text .='
                                </form>';
                            endif;
                            $text .= '
                        </div>
                    </div>

                    <div class="col-lg-5 col-md-12">
                        <div class="main-banner-image" data-aos="fade-left" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">';
                            if($this->content->banner_img):
                                $text .= '
                                <img src="'.edly_block_image_process($this->content->banner_img).'" alt="'.strip_tags($this->content->title).'">';
                            endif;
                            $text .= '
                        </div>
                    </div>

                </div>
            </div>';

            if($this->content->shape_img1):
                $text .= '
                <div class="main-banner-shape-1">
                    <img src="'.edly_block_image_process($this->content->shape_img1).'" alt="'.strip_tags($this->content->title).'">
                </div>';
            endif;
            if($this->content->shape_img2):
                $text .= '
                <div class="main-banner-shape-2">
                    <img src="'.edly_block_image_process($this->content->shape_img2).'" alt="'.strip_tags($this->content->title).'">
                </div>';
            endif;
            if($this->content->shape_img3):
                $text .= '
                <div class="main-banner-shape-3">
                    <img src="'.edly_block_image_process($this->content->shape_img3).'" alt="'.strip_tags($this->content->title).'">
                </div>';
            endif;
            if($this->content->shape_img4):
                $text .= '
                <div class="main-banner-ellipse">
                    <img src="'.edly_block_image_process($this->content->shape_img4).'" alt="'.strip_tags($this->content->title).'">
                </div>';
            endif;
            
            if($this->content->bottom_title):
                $text .= '
                <div class="main-banner-bottom-content">
                    <h3>'.format_text($this->content->bottom_title, FORMAT_HTML, array('filter' => true)).'</h3>
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