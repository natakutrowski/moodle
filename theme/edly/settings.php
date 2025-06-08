<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * edly.
 *
 * @package    theme_edly
 * @copyright  2021 HiBootstrap
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

$edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {

    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingedly', get_string('configtitle', 'theme_edly'));

    /*
    * ----------------------
    * General setting
    * ----------------------
    */
    $page = new admin_settingpage('theme_edly_general', get_string('generalsettings', 'theme_edly'));

        // Preloader
        $setting = new admin_setting_configselect('theme_edly/preloader', get_string('preloader', 'theme_edly') , get_string('preloader_desc', 'theme_edly') , null, array(
            '0' => 'Visible',
            '1' => 'Hidden'
        ));
        $page->add($setting);

        // Favicon
        $name='theme_edly/favicon';
        $title = get_string('favicon', 'theme_edly');
        $description = get_string('favicon_desc', 'theme_edly');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Global Banner
        $setting = new admin_setting_configselect('theme_edly/hide_global_banner', get_string('hide_global_banner', 'theme_edly') , get_string('hide_global_banner_desc', 'theme_edly') , null, array(
            '0' => 'Visible',
            '1' => 'Hidden'
        ));
        $page->add($setting);

        // Back to Top
        $setting = new admin_setting_configselect('theme_edly/back_to_top', get_string('back_to_top', 'theme_edly') , get_string('back_to_top_desc', 'theme_edly') , null, array(
            '0' => 'Visible',
            '1' => 'Hidden'
        ));
        $page->add($setting);

        // Preset files setting.
        $name = 'theme_edly/preset';
        $title = get_string('preset', 'theme_edly');
        $description = get_string('preset_desc', 'theme_edly');
        $default = 'default.scss';

        $context = context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'theme_edly', 'preset', 0, 'itemid, filepath, filename', false);

        $choices = [];
        foreach ($files as $file) {
            $choices[$file->get_filename()] = $file->get_filename();
        }
        // These are the built in presets from Boost.
        $choices['default.scss'] = 'default.scss';
        $choices['plain.scss'] = 'plain.scss';

        $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Preset files setting.
        $name = 'theme_edly/presetfiles';
        $title = get_string('presetfiles','theme_edly');
        $description = get_string('presetfiles_desc', 'theme_edly');

        $setting = new admin_setting_configstoredfile($name, $title, $description, 'preset', 0,
            array('maxfiles' => 20, 'accepted_types' => array('.scss')));
        $page->add($setting);

        // Primary Color 
        $name = 'theme_edly/brandcolor';
        $title = get_string('brandcolor', 'theme_edly');
        $description = get_string('brandcolor_desc', 'theme_edly');
        $default = '#F4197D';
        $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Secondary Color 
        $name = 'theme_edly/secondarycolor';
        $title = get_string('secondarycolor', 'theme_edly');
        $description = get_string('secondarycolor_desc', 'theme_edly');
        $default = '#1EA69A';
        $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Gradient Color 
        $name = 'theme_edly/gradientcolor1';
        $title = 'Gradient Color Left';
        $description = "Add theme gradient color";
        $default = '#FD1E43';
        $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Gradient Color 
        $name = 'theme_edly/gradientcolor2';
        $title = 'Gradient Color Right';
        $description = "Add theme gradient color";
        $default = '#F4197D';
        $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Gradient Color 
        $name = 'theme_edly/soft_gradientcolor';
        $title = 'Soft Gradient Color';
        $description = "Add theme soft gradient color";
        $default = '#FFADCF';
        $setting = new admin_setting_configcolourpicker($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Footer Color 
        $name = 'theme_edly/footer_bg';
        $title = get_string('footer_bg', 'theme_edly');
        $default = '#F5F6F9';
        $setting = new admin_setting_configcolourpicker($name, $title, '', $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Hide Dashboard Sidebar
        $setting = new admin_setting_configselect('theme_edly/hide_dashboard_sidebar', 'Dashboard Sidebar' , '' , null, array(
            '0' => 'Visible',
            '1' => 'Hidden'
        ));
        $page->add($setting);

        // Hide Course Curriculum For Guest Access
        $setting = new admin_setting_configselect('theme_edly/hide_guest_access_curriculum', get_string('hide_guest_access_curriculum', 'theme_edly') , get_string('hide_guest_access_curriculum_desc', 'theme_edly') , null, array(
            '0' => 'Visible',
            '1' => 'Hidden'
        ));
        $page->add($setting);

        // Course Card Content Limit
        $name = 'theme_edly/cdwl';
        $title = 'Course Card Description Limit';
        $setting = new admin_setting_configtext($name, $title, '', '15');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Free Course Price
        $name = 'theme_edly/free_course_price';
        $title = get_string('free_course_price', 'theme_edly');
        $setting = new admin_setting_configtext($name, $title, '', '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Students
        $name = 'theme_edly/total_student';
        $title = 'Course Total Students Text';
        $setting = new admin_setting_configtext($name, $title, '', 'Students');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Site Currency
        $name = 'theme_edly/site_currency';
        $title = get_string('site_currency', 'theme_edly');
        $setting = new admin_setting_configtext($name, $title, '', '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Enroll Button
        $name = 'theme_edly/enroll_btn';
        $title = 'Enroll Button Text';
        $setting = new admin_setting_configtext($name, $title, '', '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        $name = 'theme_edly/hide_banner';
        $title = get_string('hide_banner', 'theme_edly');
        $description = get_string('hide_banner_desc', 'theme_edly');
        $setting = new admin_setting_configtextarea ($name, $title, $description, '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        
        $name = 'theme_edly/hide_page_bottom_content';
        $title = get_string('hide_page_bottom_content', 'theme_edly');
        $description = get_string('hide_page_bottom_content_desc', 'theme_edly');
        $setting = new admin_setting_configtextarea ($name, $title, $description, '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

    $settings->add($page);

    /*
    * ----------------------
    * Logo settings
    * ----------------------
    */
    $page = new admin_settingpage('theme_edly_logo', get_string('logo_settings', 'theme_edly'));

        // Header logos
        $page->add(new admin_setting_heading('theme_edly/header_logos', get_string('header_logos', 'theme_edly'), NULL));

        // Logotype
        $setting = new admin_setting_configselect('theme_edly/logo_visibility',
            get_string('logo_visibility', 'theme_edly'), '', null,
            array(
                '0' => 'Visible',
                '1' => 'Hidden'
            ));
        $page->add($setting);

        // Main Logo
        $name='theme_edly/main_logo';
        $title = get_string('main_logo', 'theme_edly');
        $description = get_string('main_logo_desc', 'theme_edly');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'main_logo');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Logo Width
        $setting = new admin_setting_configtext('theme_edly/logo_image_width', get_string('logo_image_width','theme_edly'), get_string('logo_image_width_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Logo Height
        $setting = new admin_setting_configtext('theme_edly/logo_image_height', get_string('logo_image_height','theme_edly'), get_string('logo_image_height_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Mobile Logo
        $name='theme_edly/mobile_logo';
        $title = get_string('mobile_logo', 'theme_edly');
        $description = get_string('mobile_logo_desc', 'theme_edly');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'mobile_logo');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Mobile Logo Width
        $setting = new admin_setting_configtext('theme_edly/mobile_logo_width', get_string('mobile_logo_width','theme_edly'), get_string('mobile_logo_width_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Logo Height
        $setting = new admin_setting_configtext('theme_edly/mobile_logo_height', get_string('mobile_logo_height','theme_edly'), get_string('mobile_logo_height_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Footer logo
        $page->add(new admin_setting_heading('theme_edly/footer_logo_sec', get_string('footer_logo_sec', 'theme_edly'), NULL));

        // Logotype
        $setting = new admin_setting_configselect('theme_edly/footer_logo_visibility',
            get_string('footer_logo_visibility', 'theme_edly'), '', null,
            array(
                '0' => 'Visible',
                '1' => 'Hidden'
            ));
        $page->add($setting);

        // Footer  Logo
        $name='theme_edly/main_footer_logo';
        $title = get_string('main_footer_logo', 'theme_edly');
        $description = get_string('main_footer_logo_desc', 'theme_edly');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'main_footer_logo');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Logo Width
        $setting = new admin_setting_configtext('theme_edly/footer_logo_width', get_string('footer_logo_width','theme_edly'), get_string('footer_logo_width_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Logo Height
        $setting = new admin_setting_configtext('theme_edly/footer_logo_height', get_string('footer_logo_height','theme_edly'), get_string('footer_logo_height_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

    $settings->add($page);

    /*
    * ----------------------
    * Header settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_edly_header', get_string('header_settings', 'theme_edly'));

        // Top Header
        $setting = new admin_setting_configselect('theme_edly/top_header', get_string('top_header', 'theme_edly'), '', null,
            array(
                '1' => 'Show',
                '0' => 'Hide'
            ));
        $page->add($setting);

        // Top Header Content
        $name = 'theme_edly/top_header_content';
        $title = get_string('top_header_content', 'theme_edly');
        $default = 'Keep learning with free resources during COVID-19. <a href="#" class="read-more">Learn more <i class="ri-arrow-right-line"></i></a>';
        $description = '';
        $setting = new admin_setting_configtextarea($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Top Header Content
        $name = 'theme_edly/top_header_right_content';
        $title = get_string('top_header_right_content', 'theme_edly');
        $default = '<li><a href="#">Become An Instructor</a></li>';
        $description = '';
        $setting = new admin_setting_configtextarea($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Search
        $setting = new admin_setting_configselect('theme_edly/header_search', get_string('header_search', 'theme_edly'),
        get_string('header_search_desc', 'theme_edly'), null,
            array(
                '1' => 'Show',
                '0' => 'Hide'
            ));
        $page->add($setting);

        // Navbar Search Placeholder Title.
        $name = 'theme_edly/search_placeholder';
        $title = get_string('search_placeholder', 'theme_edly');
        $default = 'Search for anything';
        $description = '';
        $setting = new admin_setting_configtext($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Left Button Text
        $setting = new admin_setting_configtextarea('theme_edly/header_left_btn_text', get_string('header_left_btn_text','theme_edly'), get_string('header_left_btn_text_desc', 'theme_edly'), 'Get Started');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Left Button URL
        $setting = new admin_setting_configtext('theme_edly/header_left_btn_url', get_string('header_left_btn_url','theme_edly'), get_string('header_left_btn_url_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Button Icon
        $setting = new admin_setting_configselect('theme_edly/header_btn_icon_edly_icon_class',
        get_string('header_btn_icon', 'theme_edly'),
        get_string('header_btn_icon_desc', 'theme_edly'), 'ri-user-3-line', $edlyFontList);
        $page->add($setting);

        // Button URL
        $setting = new admin_setting_configtext('theme_edly/header_btn_url', get_string('header_btn_url','theme_edly'), get_string('header_btn_url_desc', 'theme_edly'), '', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

    $settings->add($page);

    /*
    * ----------------------
    * Banner settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_edly_banner', get_string('banner_settings', 'theme_edly'));
        // Banner Shape Image 1
        $name='theme_edly/banner_shape_image';
        $title = get_string('banner_shape_image', 'theme_edly');
        $description = get_string('banner_shape_image_desc', 'theme_edly');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'banner_shape_image');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Banner Shape Image 2
        $name='theme_edly/banner_shape_image2';
        $title = get_string('banner_shape_image2', 'theme_edly');
        $description = get_string('banner_shape_image2_desc', 'theme_edly');
        $setting = new admin_setting_configstoredfile($name, $title, $description, 'banner_shape_image2');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    $settings->add($page);

    // Social settings
    $page = new admin_settingpage('theme_edly_social_settings', get_string('social_settings', 'theme_edly'));

        // New Window
        $setting = new admin_setting_configselect('theme_edly/social_target', get_string('social_target', 'theme_edly') , get_string('social_target_desc', 'theme_edly') , null, array(
            '0' => 'Open URLs in the same page',
            '1' => 'Open URLs in a new window'
        ));
        $page->add($setting);

        // Facebook URL
        $setting = new admin_setting_configtext('theme_edly/edly_facebook_url', get_string('edly_facebook_url', 'theme_edly') , get_string('edly_facebook_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Twitter URL
        $setting = new admin_setting_configtext('theme_edly/edly_twitter_url', get_string('edly_twitter_url', 'theme_edly') , get_string('edly_twitter_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Instagram URL
        $setting = new admin_setting_configtext('theme_edly/edly_instagram_url', get_string('edly_instagram_url', 'theme_edly') , get_string('edly_instagram_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Pinterest URL
        $setting = new admin_setting_configtext('theme_edly/edly_pinterest_url', get_string('edly_pinterest_url', 'theme_edly') , get_string('edly_pinterest_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Dribbble URL
        $setting = new admin_setting_configtext('theme_edly/edly_dribbble_url', get_string('edly_dribbble_url', 'theme_edly') , get_string('edly_dribbble_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // TikTok URL
        $setting = new admin_setting_configtext('theme_edly/edly_tiktok_url', get_string('edly_tiktok_url', 'theme_edly') , get_string('edly_tiktok_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Google URL
        $setting = new admin_setting_configtext('theme_edly/edly_google_url', get_string('edly_google_url', 'theme_edly') , get_string('edly_google_url_desc', 'theme_edly') , '#', PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // YouTube URL
        $setting = new admin_setting_configtext('theme_edly/edly_youtube_url', get_string('edly_youtube_url', 'theme_edly') , get_string('edly_youtube_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // VK URL
        $setting = new admin_setting_configtext('theme_edly/edly_vk_url', get_string('edly_vk_url', 'theme_edly') , get_string('edly_vk_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // 500px URL
        $setting = new admin_setting_configtext('theme_edly/edly_500px_url', get_string('edly_500px_url', 'theme_edly') , get_string('edly_500px_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Behance URL
        $setting = new admin_setting_configtext('theme_edly/edly_behance_url', get_string('edly_behance_url', 'theme_edly') , get_string('edly_behance_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Digg URL
        $setting = new admin_setting_configtext('theme_edly/edly_digg_url', get_string('edly_digg_url', 'theme_edly') , get_string('edly_digg_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Flickr URL
        $setting = new admin_setting_configtext('theme_edly/edly_flickr_url', get_string('edly_flickr_url', 'theme_edly') , get_string('edly_flickr_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Foursquare URL
        $setting = new admin_setting_configtext('theme_edly/edly_foursquare_url', get_string('edly_foursquare_url', 'theme_edly') , get_string('edly_foursquare_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // LinkedIn URL
        $setting = new admin_setting_configtext('theme_edly/edly_linkedin_url', get_string('edly_linkedin_url', 'theme_edly') , get_string('edly_linkedin_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Medium URL
        $setting = new admin_setting_configtext('theme_edly/edly_medium_url', get_string('edly_medium_url', 'theme_edly') , get_string('edly_medium_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Meetup URL
        $setting = new admin_setting_configtext('theme_edly/edly_meetup_url', get_string('edly_meetup_url', 'theme_edly') , get_string('edly_meetup_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Snapchat URL
        $setting = new admin_setting_configtext('theme_edly/edly_snapchat_url', get_string('edly_snapchat_url', 'theme_edly') , get_string('edly_snapchat_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Tumblr URL
        $setting = new admin_setting_configtext('theme_edly/edly_tumblr_url', get_string('edly_tumblr_url', 'theme_edly') , get_string('edly_tumblr_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Vimeo URL
        $setting = new admin_setting_configtext('theme_edly/edly_vimeo_url', get_string('edly_vimeo_url', 'theme_edly') , get_string('edly_vimeo_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // WeChat URL
        $setting = new admin_setting_configtext('theme_edly/edly_wechat_url', get_string('edly_wechat_url', 'theme_edly') , get_string('edly_wechat_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // WhatsApp URL
        $setting = new admin_setting_configtext('theme_edly/edly_whatsapp_url', get_string('edly_whatsapp_url', 'theme_edly') , get_string('edly_whatsapp_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // WordPress URL
        $setting = new admin_setting_configtext('theme_edly/edly_wordpress_url', get_string('edly_wordpress_url', 'theme_edly') , get_string('edly_wordpress_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Weibo URL
        $setting = new admin_setting_configtext('theme_edly/edly_weibo_url', get_string('edly_weibo_url', 'theme_edly') , get_string('edly_weibo_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Telegram URL
        $setting = new admin_setting_configtext('theme_edly/edly_telegram_url', get_string('edly_telegram_url', 'theme_edly') , get_string('edly_telegram_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Moodle URL
        $setting = new admin_setting_configtext('theme_edly/edly_moodle_url', get_string('edly_moodle_url', 'theme_edly') , get_string('edly_moodle_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Amazon URL
        $setting = new admin_setting_configtext('theme_edly/edly_amazon_url', get_string('edly_amazon_url', 'theme_edly') , get_string('edly_amazon_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Slideshare URL
        $setting = new admin_setting_configtext('theme_edly/edly_slideshare_url', get_string('edly_slideshare_url', 'theme_edly') , get_string('edly_slideshare_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // SoundCloud URL
        $setting = new admin_setting_configtext('theme_edly/edly_soundcloud_url', get_string('edly_soundcloud_url', 'theme_edly') , get_string('edly_soundcloud_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // LeanPub URL
        $setting = new admin_setting_configtext('theme_edly/edly_leanpub_url', get_string('edly_leanpub_url', 'theme_edly') , get_string('edly_leanpub_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Xing URL
        $setting = new admin_setting_configtext('theme_edly/edly_xing_url', get_string('edly_xing_url', 'theme_edly') , get_string('edly_xing_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Bitcoin URL
        $setting = new admin_setting_configtext('theme_edly/edly_bitcoin_url', get_string('edly_bitcoin_url', 'theme_edly') , get_string('edly_bitcoin_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Twitch URL
        $setting = new admin_setting_configtext('theme_edly/edly_twitch_url', get_string('edly_twitch_url', 'theme_edly') , get_string('edly_twitch_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Github URL
        $setting = new admin_setting_configtext('theme_edly/edly_github_url', get_string('edly_github_url', 'theme_edly') , get_string('edly_github_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Gitlab URL
        $setting = new admin_setting_configtext('theme_edly/edly_gitlab_url', get_string('edly_gitlab_url', 'theme_edly') , get_string('edly_gitlab_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Forumbee URL
        $setting = new admin_setting_configtext('theme_edly/edly_forumbee_url', get_string('edly_forumbee_url', 'theme_edly') , get_string('edly_forumbee_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Trello URL
        $setting = new admin_setting_configtext('theme_edly/edly_trello_url', get_string('edly_trello_url', 'theme_edly') , get_string('edly_trello_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Weixin URL
        $setting = new admin_setting_configtext('theme_edly/edly_weixin_url', get_string('edly_weixin_url', 'theme_edly') , get_string('edly_weixin_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
        // Slack URL
        $setting = new admin_setting_configtext('theme_edly/edly_slack_url', get_string('edly_slack_url', 'theme_edly') , get_string('edly_slack_url_desc', 'theme_edly') , null, PARAM_NOTAGS, 50);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

    $settings->add($page);

    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_edly_footer', get_string('footersettings', 'theme_edly'));

     // Footer Content
     $setting = new admin_setting_configtextarea('theme_edly/footer_info', get_string('footer_info', 'theme_edly') , get_string('footer_info_desc', 'theme_edly') , 'Footer Info Text. HTML is allowed. 
        <p>
            Break into a new field like information technology or data prior experience necessary.
        </p>
        <ul class="info-list">
            <li><span>Location :</span> 32D, Jenmark road, Franklin. USA</li>
            <li><span>Phone :</span> <a href="tel:11098812345678">+11 0988 1234 5678</a></li>
            <li><span>Email :</span> <a href="mailto:contact@info.com">contact@info.com</a></li>
        </ul>', PARAM_RAW);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

    // Footer column 1
    $page->add(new admin_setting_heading('theme_edly/footer_col_1', get_string('footer_col_1', 'theme_edly') , NULL));
    // Footer column title
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_1_title', get_string('footer_col_title', 'theme_edly') , get_string('footer_col_title_desc', 'theme_edly') , 'Our Company', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Footer column body
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_1_body', get_string('footer_col_body', 'theme_edly') , get_string('footer_col_body_desc', 'theme_edly') , 'Body text for the first column.', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $page->add(new admin_setting_heading('theme_edly/footer_col_2', get_string('footer_col_2', 'theme_edly') , NULL));
    // Footer column 2
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_2_title', get_string('footer_col_title', 'theme_edly') , get_string('footer_col_title_desc', 'theme_edly') , 'About Us', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    
    // Footer column body
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_2_body', get_string('footer_col_body', 'theme_edly') , get_string('footer_col_body_desc', 'theme_edly') , 'Body text for the second column.', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Footer column 3
    $page->add(new admin_setting_heading('theme_edly/footer_col_3', get_string('footer_col_3', 'theme_edly') , NULL));

    // Footer column title
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_3_title', get_string('footer_col_title', 'theme_edly') , get_string('footer_col_title_desc', 'theme_edly') , 'Resources', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer column body
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_3_body', get_string('footer_col_body', 'theme_edly') , get_string('footer_col_body_desc', 'theme_edly') , 'Body text for the third column.', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer column 4
    $page->add(new admin_setting_heading('theme_edly/footer_col_4', get_string('footer_col_4', 'theme_edly') , NULL));
    
    // Footer column title
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_4_title', get_string('footer_col_title', 'theme_edly') , get_string('footer_col_title_desc', 'theme_edly') , 'Quick Link', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer column body
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_4_body', get_string('footer_col_body', 'theme_edly') , get_string('footer_col_body_desc', 'theme_edly') , 'Body text for the fourth column.', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);
    // Footer column 5
    $page->add(new admin_setting_heading('theme_edly/footer_col_5', get_string('footer_col_5', 'theme_edly') , NULL));

    // Footer column title 5
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_5_title', get_string('footer_col_title', 'theme_edly') , get_string('footer_col_title_desc', 'theme_edly') , '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer column body 5
    $setting = new admin_setting_configtextarea('theme_edly/footer_col_5_body', get_string('footer_col_body', 'theme_edly') , get_string('footer_col_body_desc', 'theme_edly') , '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Footer Copyright Text
    $name = 'theme_edly/footer_copyright';
    $title = get_string('footer_copyright', 'theme_edly');
    $description = '';
    $setting = new admin_setting_configtextarea ($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    /*
    * ----------------------
    * Advanced settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_edly_advanced', get_string('advancedsettings', 'theme_edly'));

    // Raw SCSS to include before the content.
    $setting = new admin_setting_configtextarea('theme_edly/scsspre',
        get_string('rawscsspre', 'theme_edly'), get_string('rawscsspre_desc', 'theme_edly'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS to include after the content.
    $setting = new admin_setting_configtextarea('theme_edly/scss', get_string('rawscss', 'theme_edly'), get_string('rawscss_desc', 'theme_edly'), '', PARAM_RAW);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}