<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_single_feedback extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_single_feedback');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->title = 'Thomson Martin';
            $this->config->subtitle = 'Designer of MTX';
            $this->config->content = 'From high school students, tertiary pupils, graduates or post-graduate learners, learners of any levels can easily find a suitable online program for themselves. Its now convenient than the past to take an online course for improving the degree, becoming working professionals.';
        }
    }

    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         =  new stdClass;

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}
        
        if(!empty($this->config->subtitle)){$this->content->subtitle = $this->config->subtitle;} else {$this->content->subtitle = '';}
        
        if(!empty($this->config->content)){$this->content->content = $this->config->content;} else {$this->content->content = '';}

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

        <div class="straight-quotes-area ptb-100">
            <div class="container">
                <div class="straight-quotes-content text-center">
                    <div class="quotes-image">';
                        if($this->content->img):
                            $img = $this->content->img;
                            $text .= '<img src="'.edly_block_image_process($img).'" alt="'. strip_tags($this->content->title).'">';
                        endif;

                        if($this->content->shape_img):
                            $shape_img = $this->content->shape_img;
                            $text .= '
                            <div class="icon">
                                <img src="'.edly_block_image_process($shape_img).'" alt="'. strip_tags($this->content->title).'">
                            </div>';
                        endif;
                        $text .= '
                    </div>
                    <div class="quotes-content">
                        <p>'.format_text($this->content->content, FORMAT_HTML, array('filter' => true)).'</p>
                        <div class="info">
                            <h3>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h3>
                            <span>'.format_text($this->content->subtitle, FORMAT_HTML, array('filter' => true)).'</span>
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