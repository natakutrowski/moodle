<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_video_area extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_video_area');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->title = 'A world class education for anyone, anywhere. Wonderful <span>choice</span>';
            $this->config->video = 'https://www.youtube.com/watch?v=ODfy2YIKS1M';
        }
    }

    public function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         =  new stdClass;

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->video)){$this->content->video = $this->config->video;} else {$this->content->video = '';}
        
        if(isset($this->config->img ) && !empty($this->config->img )){
            $this->content->img  = $this->config->img ;
        }else{
            $this->content->img  = '';
        } 
        $text = '';
        $text .= '
        <div class="success-story-area ptb-100">
            <div class="container">
                <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                    <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                </div>
                
                <div class="success-story-play text-center">';
                    if($this->content->img):
                        $img = $this->content->img;
                        $text .= '<img src="'.edly_block_image_process($img).'" alt="'. strip_tags($this->content->title).'">';
                    endif;

                    if($this->content->video):
                        $text .= '
                        <a href="'.$this->content->video.'" class="video-btn popup-video">
                            <i class="ri-play-mini-fill"></i>
                        </a>';
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