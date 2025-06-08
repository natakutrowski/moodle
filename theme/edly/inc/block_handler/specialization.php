<?php
/*
@edlyRef: @block_edly/block.php
*/

defined('MOODLE_INTERNAL') || die();

// print_object($this);
$edlyBlockType = $this->instance->blockname;

$edlyCollectionFullwidthTop =  array(
    "edly_banner_1",
    "edly_banner_2",
    "edly_partners",
    "edly_about_area",
    "edly_course_filter",
    "edly_categories",
    "edly_features_area",
    "edly_funfacts",
    "edly_features_area_two",
    "edly_about_area_two",
    "edly_newsletter",
    "edly_about_area_three",
    "edly_video_area",
    "edly_single_feedback",
    "edly_success_overview_area",
    "edly_gallery",
    "edly_contact_info",
    "edly_contact",
    "edly_faq",
    "edly_testimonial_area",
    
    "edly_blog_area",
    "edly_authors_area",
    "edly_testimonial_area_two",
    "edly_contact_features",
    "edly_funfacts_two",
    "edly_banner_3",
    "edly_about_area_four",
    "edly_career_area",
);

$edlyCollectionAboveContent =  array(
    "edly_contact_form",
    "edly_course_desc",
);

$edlyCollectionBelowContent =  array(
    "edly_course_rating",
    "edly_more_courses",
    "edly_course_instructor",
);

$edlyCollection = array_merge($edlyCollectionFullwidthTop, $edlyCollectionAboveContent, $edlyCollectionBelowContent);

if (empty($this->config)) {
    if(in_array($edlyBlockType, $edlyCollectionFullwidthTop)) {
        $this->instance->defaultregion = 'fullwidth-top';
        $this->instance->region = 'fullwidth-top';
        $DB->update_record('block_instances', $this->instance);
    }
    if(in_array($edlyBlockType, $edlyCollectionAboveContent)) {
        $this->instance->defaultregion = 'above-content';
        $this->instance->region = 'above-content';
        $DB->update_record('block_instances', $this->instance);
    }
    if(in_array($edlyBlockType, $edlyCollectionBelowContent)) {
        $this->instance->defaultregion = 'below-content';
        $this->instance->region = 'below-content';
        $DB->update_record('block_instances', $this->instance);
    }
}
