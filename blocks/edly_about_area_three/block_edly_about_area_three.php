<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_about_area_three extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_about_area_three');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->top_title = 'Over 5500+ courses available';
            $this->config->title = 'Affordable online <span>courses &amp;</span> learning opportunities';
            $this->config->content = 'Break into a new field like information technology o data science. Noprior experience necessary to get started. Break a new field like information technology.';
            $this->config->button_text = 'View All Courses ';
            $this->config->button_link = $CFG->wwwroot . '/course';
            
            $this->config->features_title1 = 'Expert Instruction';
            $this->config->features_title2 = 'Lifetime Access';
            $this->config->features_title3 = 'Remote Learning';
            $this->config->features_title4 = 'Self Development';
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

        if(!empty($this->config->button_text)){$this->content->button_text = $this->config->button_text;} else {$this->content->button_text = '';}

        if(!empty($this->config->button_link)){$this->content->button_link = $this->config->button_link;} else {$this->content->button_link = '';}

        if(isset($this->config->img ) && !empty($this->config->img )){
            $this->content->img  = $this->config->img ;
        }else{
            $this->content->img  = '';
        } 

        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){
            $this->content->shape_img  = $this->config->shape_img ;
        }else{
            $this->content->shape_img  = '';
        }
        $text = '';
        $text .= '
        <div class="affordable-area ptb-100">
            <div class="container">
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-6 col-md-12" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                        <div class="affordable-image">';
                            if($this->content->img):
                                $img = $this->content->img;
                                $text .= '<img src="'.edly_block_image_process($img).'" alt="'. strip_tags($this->content->title).'">';
                            endif;
                            
                            if($this->content->shape_img):
                                $shape_img = $this->content->shape_img;
                                $text .= '
                                <div class="ellipse">
                                    <img src="'.edly_block_image_process($shape_img).'"  alt="'. strip_tags($this->content->title).'">
                                </div>';
                            endif;
                            $text .= '
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12" data-aos="fade-up" data-aos-delay="90" data-aos-duration="900" data-aos-once="true">
                        <div class="affordable-content">
                            <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                            <h3>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h3>
                            <p>'.format_text($this->content->content, FORMAT_HTML, array('filter' => true)).'</p>
                            <div class="row justify-content-center">';
                                if ($data->items > 0) {
                                    for ($i = 1; $i <= $data->items; $i++) {
                                        $img                    = 'img' . $i;
                                        $features_title         = 'features_title' . $i;
                
                                        // Image
                                        if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }
                
                                        // Title
                                        if(isset($this->config->$features_title)) { $features_title = $this->config->$features_title; }else{ $features_title = ''; }
                                        if($i % 2 != 0){
                                            $text .= '<div class="col-lg-6 col-sm-6">
                                            <ul class="list">';
                                        }
                                        $text .= '
                                            <li>';
                                                if($img):
                                                    $img = $img;
                                                    $text .= '                    
                                                    <img src="'.edly_block_image_process($img).'" alt="'.strip_tags($features_title).'">';
                                                endif;
                                                $text .= '
                                                <span>'.format_text($features_title, FORMAT_HTML, array('filter' => true)).'</span>
                                            </li>';
                                        if($i % 2 == 0){
                                            $text .= '</ul>
                                            </div>';
                                        }
                                    }
                                }
                                $text .= '
                            </div>';

                            if(!empty($this->content->button_text) && !empty($this->content->button_link)){
                                $text .= '
                                <div class="affordable-btn">
                                    <a href="'.$this->content->button_link.'" class="default-btn">
                                        '.format_text($this->content->button_text, FORMAT_HTML, array('filter' => true)).'
                                    </a>
                                </div>';
                            }
                            $text .= '
                        </div>
                    </div>
                </div>
            </div>

            <div class="divider-two"></div>
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