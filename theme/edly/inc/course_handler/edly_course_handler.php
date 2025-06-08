<?php
/*
* COURSE HANDLER
*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/course/renderer.php');
include_once($CFG->dirroot . '/course/lib.php');

class edlyCourseHandler {
    public function edlyGetCourseDetails($courseId) {
        global $CFG, $COURSE, $USER, $DB, $SESSION, $SITE, $PAGE, $OUTPUT;


        $courseId = (int)$courseId;
        if ($DB->record_exists('course', array('id' => $courseId))) {
        // @edlyComm: Initiate
        $edlyCourse = new \stdClass();
        $chelper = new coursecat_helper();
        $courseContext = context_course::instance($courseId);

        $courseRecord = $DB->get_record('course', array('id' => $courseId));
        $courseElement = new core_course_list_element($courseRecord);

        /* @edlyBreak */
        $courseId = $courseRecord->id;
        $courseShortName = $courseRecord->shortname;
        $courseFullName = $courseRecord->fullname;
        $courseSummary = $chelper->get_course_formatted_summary($courseElement, array('noclean' => true, 'para' => false));
        $courseFormat = $courseRecord->format;
        $courseAnnouncements = $courseRecord->newsitems;
        $courseStartDate = $courseRecord->startdate;
        $courseEndDate = $courseRecord->enddate;
        $courseVisible = $courseRecord->visible;
        $courseCreated = $courseRecord->timecreated;
        $courseUpdated = $courseRecord->timemodified;
        $courseRequested = $courseRecord->requested;
        $courseEnrolmentCount = count_enrolled_users($courseContext);
        $course_is_enrolled = is_enrolled($courseContext, $USER->id, '', true);

        /* @edlyBreak */
        $categoryId = $courseRecord->category;

        try {
            $courseCategory = core_course_category::get($categoryId);
            $categoryName = $courseCategory->get_formatted_name();
            $categoryUrl = $CFG->wwwroot . '/course/index.php?categoryid='.$categoryId;
        } catch (Exception $e) {
            $courseCategory = "";
            $categoryName = "";
            $categoryUrl = "";
        }

        /* @edlyBreak */
        $enrolmentLink = $CFG->wwwroot . '/enrol/index.php?id=' . $courseId;
        $courseUrl = new moodle_url('/course/view.php', array('id' => $courseId));
        // @edlyComm: Start Payment
        $enrolInstances = enrol_get_instances($courseId, true);

        $course_price = '';
        $course_currency = '';

        foreach($enrolInstances as $singleenrolInstances){
            if($singleenrolInstances->enrol == 'paypal'){
                $course_price = $singleenrolInstances->cost;
                $course_currency = $singleenrolInstances->currency;
            }elseif($singleenrolInstances->enrol == 'stripe'){
                $course_price = $singleenrolInstances->cost;
                $course_currency = $singleenrolInstances->currency;
            }elseif($singleenrolInstances->enrol == 'payfast'){
                $course_price = $singleenrolInstances->cost;
                $course_currency = $singleenrolInstances->currency;
            }elseif($singleenrolInstances->enrol == 'paymob'){
                $course_price = $singleenrolInstances->cost;
                $course_currency = $singleenrolInstances->currency;
            }else{
                $course_price =  $singleenrolInstances->cost;
                $course_currency = $singleenrolInstances->currency;
            }
        }
        
        $edlyArrayOfCosts = array();
            $edlyCourseContacts = array();
            if ($courseElement->has_course_contacts()) {
                foreach ($courseElement->get_course_contacts() as $key => $courseContact) {
                $edlyCourseContacts[$key] = new \stdClass();
                $edlyCourseContacts[$key]->userId = $courseContact['user']->id;
                $edlyCourseContacts[$key]->username = $courseContact['user']->username;
                $edlyCourseContacts[$key]->name = $courseContact['user']->firstname . ' ' . $courseContact['user']->lastname;
                $edlyCourseContacts[$key]->role = $courseContact['role']->displayname;
                $edlyCourseContacts[$key]->profileUrl = new moodle_url('/user/view.php', array('id' => $courseContact['user']->id, 'course' => SITEID));
                }
            }


        // @edlyComm: Process first image
//         $contentimages = $contentfiles = $CFG->wwwroot . '/theme/edly/images/edlyBg.png';
//         foreach ($courseElement->get_course_overviewfiles() as $file) {
//             $isimage = $file->is_valid_image();
//             $url = file_encode_url("{$CFG->wwwroot}/pluginfile.php",
//                     '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
//                     $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
//             if ($isimage) {
//                 $contentimages = $url;
//             } else {
//                 $contentfiles = $CFG->wwwroot . '/theme/edly/images/edlyBg.png';
//             }
//         }
        
        // Edit NK ---
		$defaultimage = $CFG->wwwroot . '/theme/edly/pix/defaultcourse.png'; // ou une image réelle
		$contentimages = $defaultimage;
		
		foreach ($courseElement->get_course_overviewfiles() as $file) {
			if ($file->is_valid_image()) {
				$url = file_encode_url("{$CFG->wwwroot}/pluginfile.php",
					'/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
					$file->get_filearea(). $file->get_filepath(). $file->get_filename(), false);
				$contentimages = $url;
				break; // on s’arrête dès qu’on trouve une image valide
			}
		}
		// ---

        /* Map data */
        $edlyCourse->courseId = $courseId;
        $edlyCourse->enrolments = $courseEnrolmentCount;
        $edlyCourse->categoryId = $categoryId;
        $edlyCourse->categoryName = $categoryName;
        $edlyCourse->categoryUrl = $categoryUrl;
        $edlyCourse->shortName = $courseShortName;
        $edlyCourse->fullName = format_text($courseFullName, FORMAT_HTML, array('filter' => true));
        $edlyCourse->summary = $courseSummary;
        $edlyCourse->imageUrl = $contentimages;
        $edlyCourse->format = $courseFormat;
        $edlyCourse->announcements = $courseAnnouncements;
        $edlyCourse->startDate = userdate($courseStartDate, get_string('strftimedatefullshort', 'langconfig'));
        $edlyCourse->endDate = userdate($courseEndDate, get_string('strftimedatefullshort', 'langconfig'));
        $edlyCourse->visible = $courseVisible;
        $edlyCourse->created = userdate($courseCreated, get_string('strftimedatefullshort', 'langconfig'));
        $edlyCourse->updated = userdate($courseUpdated, get_string('strftimedatefullshort', 'langconfig'));
        $edlyCourse->requested = $courseRequested;
        $edlyCourse->enrolmentLink = $enrolmentLink;
        $edlyCourse->url = $courseUrl;
        $edlyCourse->teachers = $edlyCourseContacts;
        $edlyCourse->course_price = $course_price;
        $edlyCourse->course_currency = $course_currency;
        $edlyCourse->course_is_enrolled = $course_is_enrolled;

        /* Render object */
        $edlyRender = new \stdClass();
        $edlyRender->enrolmentIcon = '';
        $edlyRender->enrolmentIcon1 = '';
        $edlyRender->announcementsIcon     =     '';
        $edlyRender->announcementsIcon1     =     '';
        $edlyRender->updatedDate           =     '';
        $edlyRender->updatedDate         =     userdate($courseUpdated, get_string('strftimedatefullshort', 'langconfig'));
        $edlyRender->title             =     '<h3><a href="'. $edlyCourse->url .'">'. $edlyCourse->fullName .'</a></h3>';
        $edlyRender->coverImage        =     '<img class="img-whp" src="'. $contentimages .'" alt="'.strip_tags($edlyCourse->fullName).'">';
        $edlyRender->ImageUrl = $contentimages;
        /* @edlyBreak */
        $edlyCourse->edlyRender = $edlyRender;
        return $edlyCourse;
        }
        return null;
    }

    public function edlyGetCourseDescription($courseId, $maxLength){
        global $CFG, $COURSE, $USER, $DB, $SESSION, $SITE, $PAGE, $OUTPUT;
    
        if ($DB->record_exists('course', array('id' => $courseId))) {
        $chelper = new coursecat_helper();
        $courseContext = context_course::instance($courseId);
    
        $courseRecord = $DB->get_record('course', array('id' => $courseId));
        $courseElement = new core_course_list_element($courseRecord);
    
        if ($courseElement->has_summary()) {
            $courseSummary = $chelper->get_course_formatted_summary($courseElement, array('noclean' => false, 'para' => false));
            if($maxLength != null) {
            if (strlen($courseSummary) > $maxLength) {
                $courseSummary = wordwrap($courseSummary, $maxLength);
                $courseSummary = substr($courseSummary, 0, strpos($courseSummary, "\n")) . '...';
            }
            }
            return $courseSummary;
        }
    
        }
        return null;
    }

    public function edlyListCategories(){
        global $DB, $CFG;
        $topcategory = core_course_category::top();
        $topcategorykids = $topcategory->get_children();
        $areanames = array();
        foreach ($topcategorykids as $areaid => $topcategorykids) {
            $areanames[$areaid] = $topcategorykids->get_formatted_name();
            foreach($topcategorykids->get_children() as $k=>$child){
                $areanames[$k] = $child->get_formatted_name();
            }
        }
        return $areanames;
    }

    public function edlyGetCategoryDetails($categoryId){
        global $CFG, $COURSE, $USER, $DB, $SESSION, $SITE, $PAGE, $OUTPUT;
    
        if ($DB->record_exists('course_categories', array('id' => $categoryId))) {
    
        $categoryRecord = $DB->get_record('course_categories', array('id' => $categoryId));
    
        $chelper = new coursecat_helper();
        $categoryObject = core_course_category::get($categoryId);
    
        $edlyCategory = new \stdClass();
    
        $categoryId = $categoryRecord->id;
        $categoryName = format_text($categoryRecord->name, FORMAT_HTML, array('filter' => true));
        $categoryDescription = $chelper->get_category_formatted_description($categoryObject);
    
        $categorySummary = format_string($categoryRecord->description, $striplinks = true,$options = null);
        $isVisible = $categoryRecord->visible;
        $categoryUrl = $CFG->wwwroot . '/course/index.php?categoryid=' . $categoryId;
        $categoryCourses = $categoryObject->get_courses();
        $categoryCoursesCount = count($categoryCourses);
    
        $categoryGetSubcategories = [];
        $categorySubcategories = [];
        if (!$chelper->get_categories_display_option('nodisplay')) {
            $categoryGetSubcategories = $categoryObject->get_children($chelper->get_categories_display_options());
        }
        foreach($categoryGetSubcategories as $k=>$edlySubcategory) {
            $edlySubcat = new \stdClass();
            $edlySubcat->id = $edlySubcategory->id;
            $edlySubcat->name = $edlySubcategory->name;
            $edlySubcat->description = $edlySubcategory->description;
            $edlySubcat->depth = $edlySubcategory->depth;
            $edlySubcat->coursecount = $edlySubcategory->coursecount;
            $categorySubcategories[$edlySubcategory->id] = $edlySubcat;
        }
    
        $categorySubcategoriesCount = count($categorySubcategories);
    
        /* Do image */
        $outputimage = '';
        //edlyComm: Fetching the image manually added to the coursecat description via the editor.
        $description = $chelper->get_category_formatted_description($categoryObject);
        $src = "";
        if ($description) {
            $dom = new DOMDocument();
            $dom->loadHTML($description);
            $xpath = new DOMXPath($dom);
            $src = $xpath->evaluate("string(//img/@src)");
        }
        if ($src && $description){
            $outputimage = $src;
        } else {
            foreach($categoryCourses as $child_course) {
            if ($child_course === reset($categoryCourses)) {
                foreach ($child_course->get_course_overviewfiles() as $file) {
                    if ($file->is_valid_image()) {
                        $imagepath = '/' . $file->get_contextid() . '/' . $file->get_component() . '/' . $file->get_filearea() . $file->get_filepath() . $file->get_filename();
                        $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath, false);
                        $outputimage  =  $imageurl;
                        // Use the first image found.
                        break;
                    }
                }
            }
            }
        }
    
        /* Map data */
        $edlyCategory->categoryId = $categoryId;
        $edlyCategory->categoryName = $categoryName;
        $edlyCategory->categoryDescription = $categoryDescription;
        $edlyCategory->categorySummary = $categorySummary;
        $edlyCategory->isVisible = $isVisible;
        $edlyCategory->categoryUrl = $categoryUrl;
        $edlyCategory->coverImage = $outputimage;
        $edlyCategory->ImageUrl = $outputimage;
        $edlyCategory->courses = $categoryCourses;
        $edlyCategory->coursesCount = $categoryCoursesCount;
        $edlyCategory->subcategories = $categorySubcategories;
        $edlyCategory->subcategoriesCount = $categorySubcategoriesCount;
        return $edlyCategory;
    
        }
    }

    public function edlyGetExampleCategories($maxNum) {
        global $CFG, $DB;
    
        $edlyCategories = $DB->get_records('course_categories', array(), $sort='', $fields='*', $limitfrom=0, $limitnum=$maxNum);
    
        $edlyReturn = array();
        foreach ($edlyCategories as $edlyCategory) {
        $edlyReturn[] = $this->edlyGetCategoryDetails($edlyCategory->id);
        }
        return $edlyReturn;
    }

    public function edlyGetExampleCategoriesIds($maxNum) {
        global $CFG, $DB;
    
        $edlyCategories = $this->edlyGetExampleCategories($maxNum);
    
        $edlyReturn = array();
        foreach ($edlyCategories as $key => $edlyCategory) {
        $edlyReturn[] = $edlyCategory->categoryId;
        }
        return $edlyReturn;
    }
}
