<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_career_area extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_career_area');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->top_title = 'Over 5500+ courses available';
            $this->config->title = 'Get job-ready for <span>an in</span> demand career';
            $this->config->content = 'Break into a new field like information technology o data science. No prior experience necessary to get started. Break a new field like information technology.';
            $this->config->bottom_title = '522,8910 <span>people are learning on edly today.</span>';

            $this->config->funfacts_title1  = 'Student enrolled';
            $this->config->funfacts_number1 = '56892';
            $this->config->icon1            = 'ri-user-follow-line';

            $this->config->funfacts_title2  = 'Classes completed';
            $this->config->funfacts_number2 = '24053';
            $this->config->icon2            = 'ri-check-double-fill';

            $this->config->funfacts_title3  = 'Learners report';
            $this->config->funfacts_number3 = '92';
            $this->config->funfacts_prefix3 = '%';
            $this->config->icon3            = 'ri-bar-chart-fill';

            $this->config->funfacts_title4  = 'Top instructors';
            $this->config->funfacts_number4 = '3098';
            $this->config->icon4            = 'ri-user-star-line';
        }
    }

    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         =  new stdClass;
        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->bottom_title)){$this->content->bottom_title = $this->config->bottom_title;} else {$this->content->bottom_title = '';}
        
        if(!empty($this->config->content)){$this->content->content = $this->config->content;} else {$this->content->content = '';}

        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){
            $this->content->shape_img  = $this->config->shape_img ;
        }else{
            $this->content->shape_img  = '';
        }
        
        $funfactsnumber = 4;
        if(isset($this->config->funfactsnumber)){
            $funfactsnumber = $this->config->funfactsnumber;
        }

        $text = '';
        $text .= '
        <div class="career-area ptb-100">
            <div class="container">
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-6 col-md-12" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <div class="career-content">
                            <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                            <h3>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h3>  
                            '.format_text($this->content->content, FORMAT_HTML, array('filter' => true)).'
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                        <div class="row justify-content-center">';
                            for($i = 1; $i <= $funfactsnumber; $i++) {
                                $funfacts_title = 'funfacts_title' . $i;
                                $funfacts_number = 'funfacts_number' . $i;
                                $funfacts_prefix = 'funfacts_prefix' . $i;
                                $icon             = 'icon' . $i;
                                $img                = 'funfacts_icon_img' . $i;
        
                                if(isset($this->config->$funfacts_title)) {
                                    $funfacts_title = $this->config->$funfacts_title;
                                    
                                }else{
                                    $funfacts_title = '';
                                }
                                if(isset($this->config->$funfacts_number)) {
                                    $funfacts_number = $this->config->$funfacts_number;
                                }else{
                                    $funfacts_number = '';
                                }
                                if(isset($this->config->$funfacts_prefix)) {
                                    $funfacts_prefix = $this->config->$funfacts_prefix;
                                }else{
                                    $funfacts_prefix = '';                                
                                }
                                if(isset($this->config->$icon)) {
                                    $icon = $this->config->$icon;
                                }else{
                                    $icon = '';
                                }
                                if(isset($this->config->$img)) {
                                    $img = $this->config->$img;
                                }else{
                                    $img = '';
                                }
        
                                $text .= '
                                <div class="col-lg-6 col-sm-6">
                                    <div class="career-funfacts-card">
                                        <div class="content">
                                            <div class="icon bg-'.$i.'">';
                                                if($img): $text .= '
                                                <div class="icon-img">
                                                    <img src="'.$img.'" alt="Icon">
                                                </div>
                                                ';
                                                else: $text .= '
                                                    <i class="'.$icon.'"></i>';
                                                endif;
                                                $text .= '
                                            </div>
                                            
                                            <h3><span class="odometer" data-count="'.$funfacts_number.'">00</span><span class="sign">'.format_text($funfacts_prefix, FORMAT_HTML, array('filter' => true)).'</span></h3>
                                            <p>'.format_text($funfacts_title, FORMAT_HTML, array('filter' => true)).'</p>
                                        </div>
                                    </div>
                                </div>';
                            } 
                            if($this->content->bottom_title):
                            $text .= '
                                <div class="col-lg-12 col-md-12">
                                    <div class="career-bottom-content">
                                        <h3>'.format_text($this->content->bottom_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                    </div>
                                </div>';
                            endif;
                            $text .='
                        </div>
                    </div>
                </div>
            </div>';

            if($this->content->shape_img):
                $shape_img = $this->content->shape_img;
                $text .= '
                <div class="career-shape">
                    <img src="'.edly_block_image_process($shape_img).'" alt="'. strip_tags($this->content->title).'">
                </div>';
            endif;
            $text .= '
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