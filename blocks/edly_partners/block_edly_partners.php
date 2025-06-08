<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_partners extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_partners');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();            
            $this->config->style = 1;
            $this->config->class = 'ptb-100';
            $this->config->body = 'Access 5,000+ courses, 100+ SkillSets and 1,900+ Guided Projects from top universities and companies. <a href="#">Apply Now!</a>';
        }
    }

    public function get_content() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/filelib.php');

        if ($this->content !== null) {
          return $this->content;
        }

        $this->content         =  new stdClass;

        if(!empty($this->config->class)){
            $this->content->class = $this->config->class;
        }else{
            $this->content->class = '';
        }

        if(!empty($this->config->body)){
            $this->content->body = $this->config->body;
        }else{
            $this->content->body = '';
        }
        
        $style = 1;
        if(isset($this->config->style)){
            $style = $this->config->style;
        }
        $text = '';
        if($style == 2):
            $text .= '
            <!-- Start Partner Area -->
            <div class="partner-area '.$this->content->class.'">
                <div class="container">
                    <div class="partner-inner-area">
                        <div class="partner-slides owl-carousel owl-theme">';
                            $fs = get_file_storage();
                            $files = $fs->get_area_files($this->context->id, 'block_edly_partners', 'content');
                            foreach ($files as $file) {
                                $filename = $file->get_filename();
                                if ($filename <> '.') {
                                    $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $filename);
                                    $text .= '
                                    <div class="partner-card">
                                        <img src="'. $url.'" alt="'. strip_tags($filename).'">
                                    </div>';
                                }
                            }
                            $text .= '
                        </div>';
                        if($this->content->body):
                            $text .= '
                            <div class="partner-bottom-content">
                                <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                            </div>';
                        endif;
                        $text .= '
                    </div>
                </div>
            </div>';
        else:
            $text .= '
            <!-- Start Partner Area -->
            <div class="partner-area '.$this->content->class.'">
                <div class="container">
                    <div class="partner-slides owl-carousel owl-theme">';
                        $fs = get_file_storage();
                        $files = $fs->get_area_files($this->context->id, 'block_edly_partners', 'content');
                        foreach ($files as $file) {
                            $filename = $file->get_filename();
                            if ($filename <> '.') {
                                $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $filename);
                                $text .= '
                                <div class="partner-card">
                                    <img src="'. $url.'" alt="'. strip_tags($filename).'">
                                </div>';
                            }
                        }
                        $text .= '
                    </div>';
                    if($this->content->body):
                        $text .= '
                        <div class="partner-bottom-content">
                            <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                        </div>';
                    endif;
                    $text .= '
                </div>
            </div>';
        endif;

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