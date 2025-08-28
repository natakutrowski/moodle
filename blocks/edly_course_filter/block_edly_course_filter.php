<?php
require_once($CFG->dirroot. '/course/renderer.php');
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
global $CFG;
class block_edly_course_filter extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_course_filter');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();
            $this->config->style = 1;
            $this->config->class = 'courses-area ptb-100';
            $this->config->title = 'Discover Your Perfect Program In Our Courses';
            $this->config->top_title = 'Popular Courses';
            $this->config->total_student_title = 'Students';
            $this->config->body = 'Enjoy the top notch learning methods and achieve next level skills! You are the creator of your own career &amp; we will guide you through that. <a href="#">Register Free Now!</a>';
        }
    }

    public function get_content() {
        global $CFG, $DB, $COURSE, $USER, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';} 

        if(!empty($this->config->total_student_title)){$this->content->total_student_title = $this->config->total_student_title;} else {$this->content->total_student_title = '';}

        if(!empty($this->config->class)){$this->content->class = $this->config->class;} else {$this->content->class = '';} 

        if(!empty($this->config->body)){$this->content->body = $this->config->body;} else {$this->content->body = '';} 

        if(isset($this->config->shape_img ) && !empty($this->config->shape_img )){ $this->content->shape_img  = $this->config->shape_img ;
        }else{ $this->content->shape_img  = ''; }

        $categories = array();
        if(!empty($this->config->courses)){
            $coursesArr = $this->config->courses;
            $courses = new stdClass();
            foreach ($coursesArr as $key => $course) {
                $courseObj = new stdClass();
                $courseObj->id = $course;
                $courseRecord = $DB->get_record('course', array('id' => $courseObj->id), 'category');
                $courseCategory = $DB->get_record('course_categories',array('id' => $courseRecord->category));
                $courseCategory = core_course_category::get($courseCategory->id);
                $courseObj->category = $courseCategory->id;
                $courseObj->category_name = $courseCategory->get_formatted_name();
                $courses->$course = $courseObj;
            }
            $categories = array();
            foreach ($courses as $key => $course) {
                $categories[$course->category] = $course->category_name;
            }
            $categories = array_unique($categories);
        }

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
                        if(!empty($this->config->courses)){
                            $chelper = new coursecat_helper();
                            $total_courses = count($coursesArr);
                            foreach ($courses as $course) {
                                if ($DB->record_exists('course', array('id' => $course->id))) {
                                    $edlyCourseHandler = new edlyCourseHandler();
                                    $edlyCourse = $edlyCourseHandler->edlyGetCourseDetails($course->id);

                                    // Get Teacher Name
                                    $teacher = '';
                                    foreach($edlyCourse->teachers as $teacher):
                                        $teacher = $teacher->name;
                                    endforeach;

                                    $edlyCourseDescription = strip_tags($edlyCourseHandler->edlyGetCourseDescription($course->id, 99999999999999));
                                    $edlyWords = explode(' ', $edlyCourseDescription);
                                    $wordLimit = get_config('theme_edly', 'cdwl') ?: 15;
                                    $edlyCourseDescription = implode(' ', array_slice($edlyWords, 0, $wordLimit));

                                    
                                    $text .= '
                                    <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                                        <div class="courses-box">
                                            <div class="courses-image">
                                                <a href="'. $edlyCourse->url .'">
                                                    '.$edlyCourse->edlyRender->coverImage.'
                                                </a>';
                                                if($edlyCourse->course_price) {
                                                    $text .= '
                                                    <div class="price">'.format_text(get_config('theme_edly', 'site_currency') .''.$edlyCourse->course_price, FORMAT_HTML, array('filter' => true)).'</div>';
                                                }else{
                                                    $text .= '
                                                    <div class="price">'.format_text(get_config('theme_edly', 'free_course_price'), FORMAT_HTML, array('filter' => true) ).'</div>';
                                                } $text .= '
                                            </div>

                                            <div class="courses-content">
                                                <ul class="list">
                                                    <li>
                                                        <i class="ri-user-2-line"></i>
                                                        '.$edlyCourse->enrolments.' '.$this->content->total_student_title.'
                                                    </li>
                                                    <li>
                                                        <i class="ri-book-2-line"></i>
                                                        '. $edlyCourse->edlyRender->updatedDate .'
                                                    </li>
                                                </ul>
                                                <h3>
                                                    <a href="'. $edlyCourse->url .'">'.format_text($edlyCourse->fullName, FORMAT_HTML, array('filter' => true)).'</a>
                                                </h3>
                                                <div class="bottom-list d-flex justify-content-between align-items-center">
                                                    <p>'.format_text($edlyCourseDescription, FORMAT_HTML, array('filter' => true)).'</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            }
                        }
                        $text .= '
                    </div>
                    <div class="courses-bottom-content">
                        <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                    </div>
                </div>';

                if($this->content->shape_img):
                    $shape_img = $this->content->shape_img;
                    $text .= '
                    <div class="courses-pot-shape">
                        <img src="'.edly_block_image_process($shape_img).'" class="shape shape-1" alt="'. strip_tags($this->content->title).'">
                    </div>';
                endif;
                $text .= '
            </div>';
        else:
            $text .= '
            <div class="'.$this->content->class.'">
                <div class="container">
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                        <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                    </div>

                    <div class="row justify-content-center">';
                        if(!empty($this->config->courses)){
                            $chelper = new coursecat_helper();
                            $total_courses = count($coursesArr);
                            foreach ($courses as $course) {
                                if ($DB->record_exists('course', array('id' => $course->id))) {
                                    $edlyCourseHandler = new edlyCourseHandler();
                                    $edlyCourse = $edlyCourseHandler->edlyGetCourseDetails($course->id);

                                    // // Get Teacher Name
                                    // $teacher = '';
                                    // foreach($edlyCourse->teachers as $teacher):
                                    //     $teacher = $teacher->name;
                                    // endforeach;

                                    // $edlyCourseDescription = strip_tags($edlyCourseHandler->edlyGetCourseDescription($course->id, 99999999999999));
                                    // $edlyWords = explode(' ', $edlyCourseDescription);
                                    // $wordLimit = get_config('theme_edly', 'cdwl') ?: 15;
                                    // $edlyCourseDescription = implode(' ', array_slice($edlyWords, 0, $wordLimit));
                                    
                                    
                                    // Get Teacher Name (prend le premier si présent).
                                    $teacher = '';
                                    if (!empty($edlyCourse->teachers) && is_array($edlyCourse->teachers)) {
                                        $first = reset($edlyCourse->teachers);
                                        if (is_object($first) && isset($first->name)) {
                                            $teacher = (string)$first->name;
                                        }
                                    }

                                    // Description (peut être null) -> forcer string avant strip_tags().
                                    $desc = $edlyCourseHandler->edlyGetCourseDescription($course->id, 99999999999999);
                                    $desc = (string)($desc ?? '');
                                    $edlyCourseDescription = strip_tags($desc);

                                    // Limite de mots, avec garde‑fous.
                                    $wordLimit = (int)get_config('theme_edly', 'cdwl');
                                    if ($wordLimit <= 0) {
                                        $wordLimit = 15;
                                    }

                                    // Découpe proprement (évite explode sur string vide / espaces multiples).
                                    $edyWords = $edlyCourseDescription === '' ? [] : preg_split('/\s+/', trim($edlyCourseDescription));
                                    if (!is_array($edyWords)) {
                                        $edyWords = [];
                                    }

                                    $edlyCourseDescription = implode(' ', array_slice($edyWords, 0, $wordLimit));
                                                                    
                                    
                                    
                                    $text .= '
                                    <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                                        <div class="courses-card">
                                            <div class="courses-image">
                                                <a href="'. $edlyCourse->url .'">
                                                    '.$edlyCourse->edlyRender->coverImage.'
                                                </a>
                                            </div>
                        
                                            <div class="courses-content">
                                                <div class="top-content">
                                                    <div class="info d-flex align-items-center">
                                                        <div class="title">
                                                            <i class="ri-user-3-line"></i>
                                                            <span>'.format_text($teacher, FORMAT_HTML, array('filter' => true)).'</span>
                                                        </div>
                                                    </div>
                                                    <h3>
                                                        <a href="'. $edlyCourse->url .'">'.format_text($edlyCourse->fullName, FORMAT_HTML, array('filter' => true)).'</a>
                                                    </h3>';
                                                    if($edlyCourse->course_price) {
                                                        $text .= '
                                                        <div class="price">'.format_text(get_config('theme_edly', 'site_currency') .''.$edlyCourse->course_price, FORMAT_HTML, array('filter' => true)).'</div>';
                                                    }else{
                                                        $text .= '
                                                        <div class="price">'.format_text(get_config('theme_edly', 'free_course_price'), FORMAT_HTML, array('filter' => true) ).'</div>';
                                                    } $text .= '
                                                </div>
                        
                                                <div class="middle-content">
                                                    <p>'.format_text($edlyCourseDescription, FORMAT_HTML, array('filter' => true)).'</p>
                                                </div>
                        
                                                <ul class="bottom-list d-flex align-items-center justify-content-between">
                                                    <li>
                                                        <i class="ri-time-line"></i>
                                                        '. $edlyCourse->edlyRender->updatedDate .'
                                                    </li>
                                                    <li>
                                                        <i class="ri-user-2-line"></i>
                                                        '.$edlyCourse->enrolments.' '.$this->content->total_student_title.'
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            }
                        }
                        $text .= '
                    </div>
                    <div class="courses-bottom-content">
                        <p>'.format_text($this->content->body, FORMAT_HTML, array('filter' => true)).'</p>
                    </div>
                </div>';

                if($this->content->shape_img):
                    $shape_img = $this->content->shape_img;
                    $text .= '
                    <div class="courses-pot-shape">
                        <img src="'.edly_block_image_process($shape_img).'" class="shape shape-1" alt="'. strip_tags($this->content->title).'">
                    </div>';
                endif;
                $text .= '
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