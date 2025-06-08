<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_categories extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_categories');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $edlyCourseHandler = new edlyCourseHandler();
            $edlyCategories = $edlyCourseHandler->edlyGetExampleCategoriesIds(8);
            $this->config = new \stdClass();           
            $this->config->style = 1;
            $this->config->class            = 'categories-area pb-75';
            $this->config->title            = 'Top Categories you want to <span>learn</span>';
            $this->config->top_title        = 'Top Categories';
            $this->config->bottom_content   =  'Enjoy the top notch learning methods and achieve next level skills! You are the creator of your own career &amp; we will guide you through that. <a href="#">Browse all categories.</a>';
           
            $this->config->img1 = $CFG->wwwroot.'/theme/edly/pix/categories/icon1.svg';
            $this->config->img2 = $CFG->wwwroot.'/theme/edly/pix/categories/icon2.svg';
            $this->config->img3 = $CFG->wwwroot.'/theme/edly/pix/categories/icon3.svg';
            $this->config->img4 = $CFG->wwwroot.'/theme/edly/pix/categories/icon4.svg';
            $this->config->img5 = $CFG->wwwroot.'/theme/edly/pix/categories/icon5.svg';
            $this->config->img6 = $CFG->wwwroot.'/theme/edly/pix/categories/icon6.svg';
            $this->config->img7 = $CFG->wwwroot.'/theme/edly/pix/categories/icon7.svg';
            $this->config->img8 = $CFG->wwwroot.'/theme/edly/pix/categories/icon8.svg';
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

        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';}

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        if(!empty($this->config->bottom_content)){$this->content->bottom_content = $this->config->bottom_content;} else {$this->content->bottom_content = '';}

        $style = 1;
        if(isset($this->config->style)){
            $style = $this->config->style;
        }
        $text = '';

        if($style == 2):
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container">
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                        <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                    </div>

                    <div class="row justify-content-center">';
                        $topcategory = core_course_category::top();
                        $col_class = "";
                        if ($data->items == 1) {
                            $col_class = "col-sm-12 col-lg-12";
                        } else if ($data->items == 2) {
                            $col_class = "col-sm-6 col-lg-6";
                        } else if ($data->items == 3) {
                            $col_class = "col-sm-6 col-lg-4";
                        } else {
                            $col_class = "col-lg-2 col-sm-6";
                        }
                        
                        if ($data->items > 0) {
                            for ($i = 1; $i <= $data->items; $i++) {
                                $img                    = 'img' . $i;
                                $categoryID     = 'category' . $i;
                                $category       = $DB->get_record('course_categories',array('id' => $data->$categoryID));

                                // Image
                                if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }

                                if ($DB->record_exists('course_categories', array('id' => $data->$categoryID))) {
                                    $chelper = new coursecat_helper();
                                    $categoryID = $category->id;
                                    $category = core_course_category::get($categoryID);
                                    $categoryname = $category->get_formatted_name();
                                    $children_courses = $category->get_courses();
                                    if($children_courses >= 1){
                                        $countNoOfCourses = '<p>'.format_text(count($children_courses), FORMAT_HTML, array('filter' => true)).'</p>';
                                    } else {
                                        $countNoOfCourses = '';
                                    }
                                    $text .= '
                                    <div class="'.$col_class.'" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                                    <a href="'.$CFG->wwwroot .'/course/index.php?categoryid='.$categoryID.'">
                                        <div class="categories-box">';
                                            if($img):
                                                $text .= '
                                                <div class="ellipse-shape">
                                                    <img src="'.$CFG->wwwroot .'/theme/edly/pix/ellipse-2.png" alt="'.strip_tags($categoryname).'"> 
                                                    <div class="icon">
                                                        <img src="'.edly_block_image_process($img).'" alt="'.strip_tags($categoryname).'">
                                                    </div>
                                                </div>';
                                            endif;
                                            $text .= '
                                            <h3>'.format_text($categoryname, FORMAT_HTML, array('filter' => true)).'</h3>
                                        </div>
                                    </a>
                                    </div>';
                                }
                            }
                        }
                        $text .= '
                    </div>';
                    
                    if($this->content->bottom_content):
                        $text .= '
                        <div class="categories-bottom-content">
                            <p>'.format_text($this->content->bottom_content).'</p>
                        </div>';
                    endif;
                    $text .= '
                </div>
            </div>';
        else:
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container">
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title).'</span>
                        <h2>'.format_text($this->content->title).'</h2>
                    </div>

                    <div class="row justify-content-center">';
                        $topcategory = core_course_category::top();
                        $col_class = "";
                        if ($data->items == 1) {
                            $col_class = "col-sm-12 col-lg-12";
                        } else if ($data->items == 2) {
                            $col_class = "col-sm-6 col-lg-6";
                        } else if ($data->items == 3) {
                            $col_class = "col-sm-6 col-lg-4";
                        } else {
                            $col_class = "col-lg-3 col-sm-6";
                        }
                        
                        if ($data->items > 0) {
                            for ($i = 1; $i <= $data->items; $i++) {
                                $img                    = 'img' . $i;
                                $categoryID     = 'category' . $i;
                                $category       = $DB->get_record('course_categories',array('id' => $data->$categoryID));

                                // Image
                                if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }

                                if ($DB->record_exists('course_categories', array('id' => $data->$categoryID))) {
                                    $chelper = new coursecat_helper();
                                    $categoryID = $category->id;
                                    $category = core_course_category::get($categoryID);
                                    $categoryname = $category->get_formatted_name();
                                    $children_courses = $category->get_courses();
                                    if($children_courses >= 1){
                                        $countNoOfCourses = '<p>'.format_text(count($children_courses)).'</p>';
                                    } else {
                                        $countNoOfCourses = '';
                                    }
                                    $text .= '
                                    <div class="'.$col_class.'" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                                        <a href="'.$CFG->wwwroot .'/course/index.php?categoryid='.$categoryID.'">
                                            <div class="categories-card">
                                                <div class="content">
                                                    <div class="icon">';
                                                        if($img):
                                                            $text .= '
                                                            <img src="'.edly_block_image_process($img).'" alt="'.strip_tags($categoryname).'">';
                                                        endif;
                                                        $text .= '
                                                    </div>
                                                    <h3>'.format_text($categoryname).'</h3>
                                                </div>
                                            </div>
                                        </a>
                                    </div>';
                                }
                            }
                        }
                        $text .= '
                    </div>
                </div>
            </div>';
        endif;

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