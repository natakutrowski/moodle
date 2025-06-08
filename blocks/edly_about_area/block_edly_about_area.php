<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_about_area extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_about_area');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->top_title = 'Over 5500+ courses available';
            $this->config->title = 'Affordable online <span>courses</span> &amp; learning opportunities';
            $this->config->content = 'Break into a new field like information technology o data science. No prior experience necessary to get started. Break a new field like information technology.';
            
            $this->config->features_title1 = 'Learn the essential skills';
            $this->config->icon1 = 'ri-settings-5-line';
            $this->config->features_content1 = 'Break into a new field like format technology or data science.';

            $this->config->features_title2 = 'Learn in your own place';
            $this->config->icon2 = 'ri-home-smile-line';
            $this->config->features_content2 = 'Break into a new field like format technology or data science.';
        }
    }

    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (isset($this->config->items)) {
            $data = $this->config;
            $data->items = is_numeric($data->items) ? (int)$data->items : 8;
        } else {
            $data = new stdClass();
            $data->items = '0';
        }

        $this->content         =  new stdClass;
        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}
        
        if(!empty($this->config->content)){$this->content->content = $this->config->content;} else {$this->content->content = '';}

        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){
            $this->content->shape_img  = $this->config->shape_img ;
        }else{
            $this->content->shape_img  = '';
        }

        if(isset($this->config->shape_img2 ) && !empty($this->config->shape_img2 )){
            $this->content->shape_img2  = $this->config->shape_img2 ;
        }else{
            $this->content->shape_img2  = '';
        } 

        if(isset($this->config->shape_img3 ) && !empty($this->config->shape_img3 )){
            $this->content->shape_img3  = $this->config->shape_img3 ;
        }else{
            $this->content->shape_img3  = '';
        } 

        $text = '';

        $text .= '
        <div class="opportunities-area ptb-100">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-5 col-md-12">
                        <div class="opportunities-content" data-aos="fade-down" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                            <h3>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h3>  
                            '.format_text($this->content->content, FORMAT_HTML, array('filter' => true)).'
                        </div>
                    </div>
    
                    <div class="col-lg-7 col-md-12">
                        <div class="opportunities-right-content">
                            <div class="row justify-content-center">';
                            if ($data->items > 0) {
                                for ($i = 1; $i <= $data->items; $i++) {
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
                                    <div class="col-lg-6 col-sm-6" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                                        <div class="opportunities-card">';
                                            if($icon):
                                                $text .= '
                                                <div class="icon">
                                                    <i class="'.$icon.'"></i>
                                                </div>';
                                            endif;
                                            $text .= '
                                            <h3>'.format_text($features_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                            <p>'.format_text($features_content, FORMAT_HTML, array('filter' => true)).'</p>
                                        </div>
                                    </div>';
                                }
                            }
                            $text .= '
                            </div>';

                            if($this->content->shape_img3):
                                $shape_img3 = $this->content->shape_img3;
                                $text .= '
                                <div class="opportunities-ellipse">
                                    <img src="'.edly_block_image_process($shape_img3).'" alt="'. strip_tags($this->content->title).'">
                                </div>';
                            endif; 
                            if($this->content->shape_img):
                                $shape_img = $this->content->shape_img;
                                $text .= '
                                <div class="opportunities-shape-1">
                                    <img src="'.edly_block_image_process($shape_img).'" alt="'. strip_tags($this->content->title).'">
                                </div>
                                ';
                            endif;

                            if($this->content->shape_img2):
                                $shape_img2 = $this->content->shape_img2;
                                $text .= '
                                <div class="opportunities-shape-2">
                                    <img src="'.edly_block_image_process($shape_img2).'" alt="'. strip_tags($this->content->title).'">
                                </div>';
                            endif;
                            $text .= '
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        $this->content->footer = '';
        $this->content->text   = $text;

        return $this->content;
    }

    function instance_allow_config() {
        return true;
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