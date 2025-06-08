<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_testimonial_area extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_testimonial_area');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->style   = 1;
            $this->config->class = 'review-area bg-F9F6F2 ptb-100';
            $this->config->top_title = 'Our Review';
            $this->config->title = 'What our students have <span>to say</span>';

            $this->config->slider_title1                = 'Great Quality Trainer!';
            $this->config->slider_content1              = 'Break into a new field like some information technology or data science. No prior experience support necessary.';
            $this->config->slider_name1       = 'Thomson Martin';
            $this->config->slider_designation1       = 'QA Project Expert';

            $this->config->slider_title2          = 'Great Support & Quality Trainer!';
            $this->config->slider_content2        = 'Instructors from around the world teach millions of students on Edly. We provide the top best tools and skills.';
            $this->config->slider_name2    = 'James Andy';
            $this->config->slider_designation2       = 'Designer of MTX';

            $this->config->slider_title3          = 'Great Quality Trainer!';
            $this->config->slider_content3        = 'Break into a new field like some information technology or data science. No prior experience support necessary.';
            $this->config->slider_name3    = 'Chris Evans';
            $this->config->slider_designation3       = 'Project Management Expert';

            $this->config->slider_title4          = 'Great Support & Quality Trainer!';
            $this->config->slider_content4        = 'Instructors from around the world teach millions of students on Edly. We provide the top best tools and skills.';
            $this->config->slider_name4    = 'Alister Cock';
            $this->config->slider_designation4       = 'Python Expert';

            $this->config->body       = 'Career &amp; we will guide you through that. <a href="#">Register Free Now!</a>';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        $this->content         =  new stdClass;

        $sliderNumber = 4;
        if(isset($this->config->sliderNumber)){
            $sliderNumber = $this->config->sliderNumber;
        }

        $style = 1;
        if(isset($this->config->style)){
            $style = $this->config->style;
        }

        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->body)){$this->content->body = $this->config->body;} else {$this->content->body = '';}
       
        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){
            $this->content->shape_img  = $this->config->shape_img ;
        }else{
            $this->content->shape_img  = '';
        }

        if(isset($this->config->shape_img2 ) && !empty($this->config->shape_img2 )){
            $this->content->shape_img2  = $this->config->shape_img2 ;
        }else{
            $this->content->shape_img2  = '';
        }

        $text = '';
        if($style == 2):
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container">';
                    if($this->content->top_title || $this->content->title):
                        $text .= '
                        <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <span class="sub">'.format_text( $this->content->top_title, FORMAT_HTML, array('filter' => true) ).'</span>
                            <h2>'.format_text( $this->content->title, FORMAT_HTML, array('filter' => true) ).'</h2>
                        </div>';
                    endif;
                    $text .= '
                    <div class="review-grid row">';
                        for($i = 1; $i <= $sliderNumber; $i++) {
                            $img                    = 'img' . $i;
                            $slider_title           = 'slider_title' . $i;
                            $slider_content         = 'slider_content' . $i;
                            $slider_name            = 'slider_name' . $i;
                            $slider_designation     = 'slider_designation' . $i;

                            // Image
                            if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }

                            // Title
                            if(isset($this->config->$slider_title)) { $slider_title = $this->config->$slider_title; }else{ $slider_title = ''; }

                            // Content
                            if(isset($this->config->$slider_content)) { $slider_content = $this->config->$slider_content; }else{ $slider_content = ''; }

                            // Name
                            if(isset($this->config->$slider_name)) { $slider_name = $this->config->$slider_name; }else{ $slider_name = ''; }

                            // Designation
                            if(isset($this->config->$slider_designation)) { $slider_designation = $this->config->$slider_designation; }else{ $slider_designation = ''; }
                            $text .= '
                            <div class="col-xl-4 col-md-6 grid-item">
                                <div class="review-card" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">';
                                    if($img):
                                        $img = $img;
                                        $text .= '                    
                                        <img class="rounded-circle" src="'.edly_block_image_process($img).'" alt="'.strip_tags($slider_title).'">';
                                    endif;
                                    $text .= '
                                    <h3>'.format_text($slider_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                    <p>'.format_text($slider_content, FORMAT_HTML, array('filter' => true)).'</p>
                                    <div class="info">
                                        <h4>'.format_text($slider_name, FORMAT_HTML, array('filter' => true)).'</h4>
                                        <span>'.format_text($slider_designation, FORMAT_HTML, array('filter' => true)).'</span>
                                    </div>
                                </div>
                            </div>';
                        } $text .= '
                    </div>
                    <div class="review-bottom-content">
                        <p>'.format_text($this->content->body).'</p>
                    </div>
                </div>
            </div>';
        else:
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container-fluid">';
                    if($this->content->top_title || $this->content->title):
                        $text .= '
                        <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <span class="sub">'.format_text( $this->content->top_title, FORMAT_HTML, array('filter' => true) ).'</span>
                            <h2>'.format_text( $this->content->title, FORMAT_HTML, array('filter' => true) ).'</h2>
                        </div>';
                    endif;
                    $text .= '

                    <div class="review-slides owl-carousel owl-theme">';
                        for($i = 1; $i <= $sliderNumber; $i++) {
                            $img                    = 'img' . $i;
                            $slider_title           = 'slider_title' . $i;
                            $slider_content         = 'slider_content' . $i;
                            $slider_name            = 'slider_name' . $i;
                            $slider_designation     = 'slider_designation' . $i;

                            // Image
                            if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }

                            // Title
                            if(isset($this->config->$slider_title)) { $slider_title = $this->config->$slider_title; }else{ $slider_title = ''; }

                            // Content
                            if(isset($this->config->$slider_content)) { $slider_content = $this->config->$slider_content; }else{ $slider_content = ''; }

                            // Name
                            if(isset($this->config->$slider_name)) { $slider_name = $this->config->$slider_name; }else{ $slider_name = ''; }

                            // Designation
                            if(isset($this->config->$slider_designation)) { $slider_designation = $this->config->$slider_designation; }else{ $slider_designation = ''; }
                            $text .= '
                            <div class="review-card" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">';
                                if($img):
                                    $img = $img;
                                    $text .= '                    
                                    <img class="rounded-circle" src="'.edly_block_image_process($img).'" alt="'.$slider_title.'">';
                                endif;
                                $text .= '
                                <h3>'.format_text($slider_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                <p>'.format_text($slider_content, FORMAT_HTML, array('filter' => true)).'</p>
                                <div class="info">
                                    <h4>'.format_text($slider_name, FORMAT_HTML, array('filter' => true)).'</h4>
                                    <span>'.format_text($slider_designation, FORMAT_HTML, array('filter' => true)).'</span>
                                </div>
                            </div>';
                        } $text .= '

                    </div>
                    <div class="review-bottom-content">
                        <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                    </div>
                </div>

                <div class="review-shape-1">';
                    if($this->content->shape_img):
                        $shape_img = $this->content->shape_img;
                        $text .= '<img src="'.edly_block_image_process($shape_img).'" alt="'. strip_tags($this->content->title).'">';
                    endif;
                    $text .= '
                </div>
                <div class="review-shape-2">';
                    if($this->content->shape_img2):
                        $shape_img2 = $this->content->shape_img2;
                        $text .= '<img src="'.edly_block_image_process($shape_img2).'" alt="'. strip_tags($this->content->title).'">';
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