<?php
global $CFG;
class block_edly_funfacts extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_funfacts');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->top_title = 'Start To Success';
            $this->config->title = 'Start your journey &amp; Achieve <span>your goal</span>';

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
        global $CFG, $DB;

        $this->content         =  new stdClass;
        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';}
        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}
        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        $funfactsnumber = 4;
        if(isset($this->config->funfactsnumber)){
            $funfactsnumber = $this->config->funfactsnumber;
        }

        if(isset($this->config->img ) && !empty($this->config->img )){
            $this->content->img  = $this->config->img ;
        }else{
            $this->content->img  = '';
        }
      
        $text = '';
        $text .= '
        <div class="'.$this->content->class.'">
            <div class="container">';
            if($this->content->title || $this->content->top_title):
                $text .= '
                <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                    <span class="sub">'.format_text( $this->content->top_title, FORMAT_HTML, array('filter' => true) ).'</span>
                    <h2>'.format_text( $this->content->title, FORMAT_HTML, array('filter' => true) ).'</h2>
                </div>';
            endif;
            $text .= '
                <div class="row justify-content-center align-items-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="row justify-content-center">';
                            for($i = 1; $i <= $funfactsnumber; $i++) {
                                $funfacts_title = 'funfacts_title' . $i;
                                $funfacts_number = 'funfacts_number' . $i;
                                $funfacts_prefix = 'funfacts_prefix' . $i;
                                $icon           = 'icon' . $i;

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

                                $text .= '
                                <div class="col-lg-6 col-sm-6" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                                    <div class="success-funfacts-card">
                                        <div class="content">
                                            <div class="icon bg-'.$i.'">
                                                <i class="'.$icon.'"></i>
                                            </div>
                                            
                                            <h3><span class="odometer" data-count="'.$funfacts_number.'">00</span><span class="sign">'.format_text($funfacts_prefix, FORMAT_HTML, array('filter' => true)).'</span></h3>
                                            <p>'.format_text($funfacts_title, FORMAT_HTML, array('filter' => true)).'</p>
                                        </div>
                                    </div>
                                </div>';
                            } $text .= '
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">';
                        if($this->content->img):
                            $img = $this->content->img;
                            $text .= '
                            <div class="success-image" data-aos="fade-up" data-aos-delay="90" data-aos-duration="900" data-aos-once="true">
                                <img src="'.edly_block_image_process($img).'" alt="'. strip_tags($this->content->title).'">
                            </div>';
                        endif; 
                        $text .= '
                    </div>
                </div>
            </div>
            <div class="divider"></div>
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