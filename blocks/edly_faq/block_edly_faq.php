<?php
global $CFG;
class block_edly_faq extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_faq');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->tabNumber = '2';
            $this->config->tab_title1 = 'Getting Started';
            $this->config->tab_title2 = 'Pricing & Planes';
            $this->config->tab_content1 = '
            <li class="accordion-item">
                <a class="accordion-title active" href="javascript:void(0)">
                    <i class="ri-arrow-down-s-line"></i>
                    How Do We Create Course Content?
                </a>
                <div class="accordion-content show">
                    <p>Break into a new field like some information technology or data science. No prior experience support necessary. Break into a new field like some information technology or data science. No prior experience support necessary.</p>
                </div>
            </li>
            <li class="accordion-item">
                <a class="accordion-title" href="javascript:void(0)">
                    <i class="ri-arrow-down-s-line"></i>
                    How Can I Manage My Account?
                </a>
                <div class="accordion-content">
                    <p>Break into a new field like some information technology or data science. No prior experience support necessary. Break into a new field like some information technology or data science. No prior experience support necessary.</p>
                </div>
            </li>';
            $this->config->tab_content2 = '
            <li class="accordion-item">
                <a class="accordion-title active" href="javascript:void(0)">
                    <i class="ri-arrow-down-s-line"></i>
                    How Do We Create Course Content?
                </a>
                <div class="accordion-content show">
                    <p>Break into a new field like some information technology or data science. No prior experience support necessary. Break into a new field like some information technology or data science. No prior experience support necessary.</p>
                </div>
            </li>
            <li class="accordion-item">
                <a class="accordion-title" href="javascript:void(0)">
                    <i class="ri-arrow-down-s-line"></i>
                    How Can I Manage My Account?
                </a>
                <div class="accordion-content">
                    <p>Break into a new field like some information technology or data science. No prior experience support necessary. Break into a new field like some information technology or data science. No prior experience support necessary.</p>
                </div>
            </li>';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        if ($this->content !== null) {
            return $this->content;
        }
  
        $this->content         =  new stdClass;

        $tabNumber = 2;
        if(isset($this->config->tabNumber)){
            $tabNumber = $this->config->tabNumber;
        } 
        $text = '';
        $text .= '
        <div class="faq-area ptb-100">
            <div class="container">
                <div class="tab faq-accordion-tab">
                    <ul class="tabs d-flex flex-wrap justify-content-center">';
                        for($i = 1; $i <= $tabNumber; $i++) {
                            $tab_title = 'tab_title' . $i;
                            $icon = 'icon' . $i;

                            if(isset($this->config->$tab_title)) {
                                $tab_title = $this->config->$tab_title;
                            }else{
                                $tab_title = '';
                            } 

                            if(isset($this->config->$icon)) {
                                $icon = $this->config->$icon;
                            }else{
                                $icon = '';
                            } 
                            $text .= '
                            <li><a href="#"><i class="'.$icon.'"></i> <span>'.format_text($tab_title, FORMAT_HTML, array('filter' => true)).'</span></a></li>';
                        }
                        $text .= '
                    </ul>

                    <div class="tab-content">
                    ';
                        for($i = 1; $i <= $tabNumber; $i++) {
                            $tab_content = 'tab_content' . $i;

                            if(isset($this->config->$tab_content)) {
                                $tab_content = $this->config->$tab_content;
                            }else{
                                $tab_content = '';
                            }
                            $text .= '
                            <div class="tabs-item">
                                <div class="faq-accordion">
                                    <ul class="accordion">
                                        '.format_text($tab_content, FORMAT_HTML, array('filter' => true)).'
                                    </ul>
                                </div>
                            </div>';
                        }
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