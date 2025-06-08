<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_newsletter extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_newsletter');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->class = 'ptb-100';
            $this->config->title = 'Subscribe to our <span>Newsletter</span>';
            $this->config->body = 'Break into a new field like information technology or data science.';
            $this->config->placeholder = 'Enter your email address';
            $this->config->btn = 'Subscribe';
            $this->config->action_url = '';
        }
    }

    public function get_content() {
        global $CFG, $DB, $COURSE, $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;

        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';}

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->body)){$this->content->body = $this->config->body;} else {$this->content->body = '';}

        if(!empty($this->config->placeholder)){$this->content->placeholder = $this->config->placeholder;} else {$this->content->placeholder = '';}

        if(!empty($this->config->btn)){$this->content->btn = $this->config->btn;} else {$this->content->btn = '';}

        if(!empty($this->config->action_url)){$this->content->action_url = $this->config->action_url;} else {$this->content->action_url = '';}

        $img = 'img';
        if(isset($this->config->$img) && !empty($this->config->$img)){ $this->content->img = $this->config->$img; }else{ $this->content->img = '';}

        $shape_img = 'shape_img';
        if(isset($this->config->$shape_img) && !empty($this->config->$shape_img)){ $this->content->shape_img = $this->config->$shape_img; }else{ $this->content->shape_img = ''; }

        $text = '';
        $text .= '
        <div class="subscribe-area '.$this->content->class.'">
            <div class="container">
                <div class="subscribe-inner-area">
                    <div class="row justify-content-center align-items-center">
                        <div class="col-lg-7 col-md-12">
                            <div class="subscribe-content">
                                <h3>'.format_text( $this->content->title, FORMAT_HTML, array('filter' => true) ).'</h3>
                                <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>';

                                if($this->content->action_url):
                                    $text .= '
                                    <form action="'.$this->content->action_url.'" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="newsletter-form validate" target="_blank">
                                        <input type="text" value="" name="EMAIL" class="email input-newsletter" id="mce-EMAIL" placeholder="'.format_text($this->content->placeholder, FORMAT_HTML).'" required>
                                        <button type="submit" name="subscribe" id="mc-embedded-subscribe" class="button default-btn">'.format_text($this->content->btn, FORMAT_HTML, array('filter' => true)).'</button>
                                    </form>';
                                endif;
                                $text .= '
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-12">
                            <div class="subscribe-image">';
                                if($this->content->img):
                                    $img = $this->content->img;
                                    $text .= '                    
                                    <img src="'.edly_block_image_process($img).'" alt="'. strip_tags($this->content->title).'">';
                                endif;
                                $text .= '
                            </div>
                        </div>
                    </div>
                    <div class="subscribe-shape">';
                        if($this->content->shape_img):
                            $shape_img = $this->content->shape_img;
                            $text .= '                    
                            <img src="'.edly_block_image_process($shape_img).'" alt="'. strip_tags($this->content->title).'">';
                        endif;
                        $text .= '
                    </div>
                </div>
            </div>
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