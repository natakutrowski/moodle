<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_about_area_two extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_about_area_two');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->top_title = 'Join as Instructor';
            $this->config->title = 'Become an instructor <span>Join</span> the millions learning';
            $this->config->button_text = 'Start Teaching Today ';
            $this->config->button_link = $CFG->wwwroot . '/course';
        }
    }

    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}
        
        if(!empty($this->config->button_text)){$this->content->button_text = $this->config->button_text;} else {$this->content->button_text = '';}

        if(!empty($this->config->button_link)){$this->content->button_link = $this->config->button_link;} else {$this->content->button_link = '';}

        if(isset($this->config->img ) && !empty($this->config->img )){ $this->content->img  = $this->config->img; }else{ $this->content->img  = ''; }

        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){ $this->content->shape_img  = $this->config->shape_img ; }else{ $this->content->shape_img  = ''; }

        if(isset($this->config->shape_img2 ) && !empty($this->config->shape_img2 )){ $this->content->shape_img2  = $this->config->shape_img2 ; }else{ $this->content->shape_img2  = ''; }

        if(isset($this->config->shape_img3 ) && !empty($this->config->shape_img3 )){ $this->content->shape_img3  = $this->config->shape_img3 ; }else{ $this->content->shape_img3  = ''; }
        
        $text = '';
        $text .= '
        <div class="overview-area '.$this->content->class.'">
            <div class="container">
                <div class="overview-inner-area">
                    <div class="row justify-content-center align-items-center">
                        <div class="col-lg-5 col-md-12" data-aos="fade-right" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <div class="overview-image">';
                                if($this->content->img):
                                    $img = $this->content->img;
                                    $text .= '<img src="'.edly_block_image_process($img).'" alt="'. strip_tags($this->content->title).'">';
                                endif;

                                if($this->content->shape_img):
                                    $shape_img = $this->content->shape_img;
                                    $text .= '
                                    <div class="wrap-shape-1">
                                        <img src="'.edly_block_image_process($shape_img).'" alt="'. strip_tags($this->content->title).'">
                                    </div>';
                                endif;

                                if($this->content->shape_img2):
                                    $shape_img2 = $this->content->shape_img2;
                                    $text .= '
                                    <div class="wrap-shape-2">
                                        <img src="'.edly_block_image_process($shape_img2).'" alt="'. strip_tags($this->content->title).'">
                                    </div>';
                                endif;
                                $text .= '
                            </div>
                        </div>
                        <div class="col-lg-7 col-md-12" data-aos="fade-left" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <div class="overview-content">
                                <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                                <h3>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h3>';

                                if(!empty($this->content->button_text) && !empty($this->content->button_link)){
                                    $text .= '
                                    <div class="overview-btn">
                                        <a href="'.$this->content->button_link.'" class="default-btn">'.format_text($this->content->button_text, FORMAT_HTML, array('filter' => true)).'</a>
                                    </div>';
                                }
                                $text .= '
                            </div>
                        </div>
                    </div>';
                    
                    if($this->content->shape_img3):
                        $shape_img3 = $this->content->shape_img3;
                        $text .= '
                        <div class="ellipse-shape">
                            <img src="'.edly_block_image_process($shape_img3).'" alt="'. strip_tags($this->content->title).'">
                        </div>';
                    endif;
                    $text .= '
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