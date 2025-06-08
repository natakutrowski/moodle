<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_success_overview_area extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_success_overview_area');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->class = 'success-overview-area pb-100';
        
            $this->config->title1       = 'Inspirational Stories Are Less About Success';
            $this->config->content1     = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Natoque interdum venenatis, volutpat in at volutpat, ut enim. Nisl mauris massa adipiscing ac mauris, habitant ullamcorper. Tempus quis tortor lectus consectetur at suspendisse.</p>';
            $this->config->icon1        = 'ri-arrow-right-line';
        
            $this->config->title2       = 'Opportunities are opened for learners to choose';
            $this->config->content2     = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Natoque interdum venenatis, volutpat in at volutpat, ut enim. Nisl mauris massa adipiscing ac mauris, habitant ullamcorper. Tempus quis tortor lectus consectetur at suspendisse.</p>';
            $this->config->icon2        = 'ri-arrow-right-line';
        
            $this->config->title3       = 'Academic Excellence and Cultural Diversity';
            $this->config->content3     = '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Natoque interdum venenatis, volutpat in at volutpat, ut enim. Nisl mauris massa adipiscing ac mauris, habitant ullamcorper. Tempus quis tortor lectus consectetur at suspendisse.</p>';
            $this->config->icon3        = 'ri-arrow-right-line';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        $this->content         =  new stdClass;

        $itemNumber = 4;
        if(isset($this->config->itemNumber)){
            $itemNumber = $this->config->itemNumber;
        }

        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';}

        $text = '';
        $text .= '
        <div class="'.$this->content->class.'">
            <div class="container">';
                for($i = 1; $i <= $itemNumber; $i++) {
                    $title           = 'title' . $i;
                    $content         = 'content' . $i;
                    $icon            = 'icon' . $i;

                    // Title
                    if(isset($this->config->$title)) { $title = $this->config->$title; }else{ $title = ''; }

                    // Content
                    if(isset($this->config->$content)) { $content = $this->config->$content; }else{ $content = ''; }

                    // Icon
                    if(isset($this->config->$icon)) { $icon = $this->config->$icon; }else{ $icon = ''; }

                    $text .= '
                    <div class="success-overview-content">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-lg-4 col-md-12">
                                <div class="heading-content">
                                    <h3><i class="'.$icon.'"></i> '.format_text($title, FORMAT_HTML, array('filter' => true)).'</h3>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-12">
                                <div class="paragraph-content">
                                    '.format_text($content, FORMAT_HTML, array('filter' => true)).'
                                </div>
                            </div>
                        </div>
                    </div>';
                } $text .= '
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