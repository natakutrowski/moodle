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
 * Wordcards module admin settings and defaults
 *
 * @package    mod
 * @subpackage wordcards
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use \mod_wordcards\constants;
use \mod_wordcards\utils;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext(constants::M_COMPONENT .  '/apiuser',
            get_string('apiuser', constants::M_COMPONENT), get_string('apiuser_details', constants::M_COMPONENT), '', PARAM_TEXT));

    $cloudpoodll_apiuser=get_config(constants::M_COMPONENT,'apiuser');
    $cloudpoodll_apisecret=get_config(constants::M_COMPONENT,'apisecret');
    $show_below_apisecret='';
//if we have an API user and secret we fetch token
    if(!empty($cloudpoodll_apiuser) && !empty($cloudpoodll_apisecret)) {
        $tokeninfo = utils::fetch_token_for_display($cloudpoodll_apiuser,$cloudpoodll_apisecret);
        $show_below_apisecret=$tokeninfo;
//if we have no API user and secret we show a "fetch from elsewhere on site" or "take a free trial" link
    }else{
        $amddata=['apppath'=>$CFG->wwwroot . '/' .constants::M_URL];
        $cp_components=['filter_poodll','qtype_cloudpoodll','mod_readaloud','mod_solo','mod_minilesson','mod_englishcentral','mod_pchat',
            'atto_cloudpoodll','tinymce_cloudpoodll', 'assignsubmission_cloudpoodll','assignfeedback_cloudpoodll','tiny_poodll'];
        foreach($cp_components as $cp_component){
            switch($cp_component){
                case 'filter_poodll':
                    $apiusersetting='cpapiuser';
                    $apisecretsetting='cpapisecret';
                    break;
                case 'mod_englishcentral':
                    $apiusersetting='poodllapiuser';
                    $apisecretsetting='poodllapisecret';
                    break;
                default:
                    $apiusersetting='apiuser';
                    $apisecretsetting='apisecret';
            }
            $cloudpoodll_apiuser=get_config($cp_component,$apiusersetting);
            if(!empty($cloudpoodll_apiuser)){
                $cloudpoodll_apisecret=get_config($cp_component,$apisecretsetting);
                if(!empty($cloudpoodll_apisecret)){
                    $amddata['apiuser']=$cloudpoodll_apiuser;
                    $amddata['apisecret']=$cloudpoodll_apisecret;
                    break;
                }
            }
        }
        $show_below_apisecret=$OUTPUT->render_from_template( constants::M_COMPONENT . '/managecreds',$amddata);
    }

    $settings->add(new admin_setting_configtext(constants::M_COMPONENT .  '/apisecret',
        get_string('apisecret', constants::M_COMPONENT),$show_below_apisecret , '', PARAM_TEXT));

    $regions = utils::get_region_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT .  '/awsregion', get_string('awsregion', constants::M_COMPONENT), '', 'useast1', $regions));

    $modes = utils::get_journeymode_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT .  '/journeymode', get_string('journeymode', constants::M_COMPONENT), '', constants::MODE_STEPSTHENFREE, $modes));

    $expiredays = utils::get_expiredays_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT .  '/expiredays', get_string('expiredays', constants::M_COMPONENT), '', '365', $expiredays));

    //TTS Language options
    $name = 'ttslanguage';
    $label = get_string($name, constants::M_COMPONENT);
    $details ="";
    $default = constants::M_LANG_ENUS;
    $options = utils::get_lang_options();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));

    //Definitions Language options
    $name = 'deflanguage';
    $label = get_string($name, constants::M_COMPONENT);
    $details ="";
    $default = "en";
    $lexicala = utils::get_rcdic_langs();
    $options=[];
    foreach($lexicala as $lexone){
        $options[$lexone['code']]=$lexone['name'];
    }
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    // Transcriber options
    $name = 'transcriber';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::TRANSCRIBER_AUTO;
    $options = utils::fetch_options_transcribers();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));


    // Reports Table
    $name = 'reportstable';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_USE_DATATABLES;
    $options = utils::fetch_options_reportstable();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    // Items per page options
    $name = 'itemsperpage';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = 10;
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . "/$name",
            $label, $details, $default, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT .  '/enablesetuptab',
            get_string('enablesetuptab', constants::M_COMPONENT), get_string('enablesetuptab_details',constants::M_COMPONENT), 0));


    //show images on flip screen
    $name = 'showimageflip';
    $label = get_string('showimageflip', constants::M_COMPONENT);
    $details = get_string($name, constants::M_COMPONENT);
    get_string($name . '_details', constants::M_COMPONENT);
    $default = 1;
    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . "/$name",
            $label, $details, $default));

    //front face flip
    $name = 'frontfaceflip';
    $label = get_string($name, constants::M_COMPONENT);
    get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_FRONTFACEFLIP_DEF;
    $options = utils::fetch_options_fontfaceflip();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
            $label, $details, $default, $options));

    //Listen and Choose - audio is term or def
    $name = 'lcoptions';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_LC_AUDIO_DEF;
    $options = utils::fetch_options_listenchoose();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    //Match Select - term or def at top
    $name = 'msoptions';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_MS_DEF_AT_TOP;
    $options = utils::fetch_options_matchselect();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    //Space Game - term as aliens, or definitions as aliens
    $name = 'sgoptions';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_SG_TERM_AS_ALIEN;
    $options = utils::fetch_options_spacegame();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    //Wordcards - term as thing to read, or model sentence as thing to read
    $name = 'scoptions';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_WC_TERM_AS_READABLE;
    $options = utils::fetch_options_speechcards();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));        

    // In free mode, how many items max to show to users.
    $name = 'def_wordstoshow';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = 6;
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . "/$name",
        $label, $details, $default, PARAM_INT));

    //animations
    $name = 'animations';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = constants::M_ANIM_FANCY ;
    $options = utils::fetch_options_animations();
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    //Video Examples
    $name = 'videoexamples';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = 1;
    $options = [0=>get_string('no'),1=>get_string('yes')];
    $settings->add(new admin_setting_configselect(constants::M_COMPONENT . "/$name",
        $label, $details, $default, $options));

    // Set the number of associations from which to consider the term learned
    $name = 'learnpoint';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $default = 4;
    $settings->add(new admin_setting_configtext(constants::M_COMPONENT . "/$name",
        $label, $details, $default, PARAM_INT));

    //show language chooser
    $name = 'showlangchooser';
    $label = get_string('showlangchooser', constants::M_COMPONENT);
    $details = get_string('showlangchooser_help', constants::M_COMPONENT);
    $default = 1;
    $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . "/$name",
            $label, $details, $default));    

    // Set the default free mode options
    $name = 'freemodesettings';
    $label = get_string($name, constants::M_COMPONENT);
    $details = get_string($name . '_details', constants::M_COMPONENT);
    $settings->add(new admin_setting_heading(constants::M_COMPONENT . "/$name", $label, $details));

    $freemodeoptions = constants::FREEMODE_ACTIVITIES;
    foreach ($freemodeoptions as $theoption) {
        $settings->add(new admin_setting_configcheckbox(constants::M_COMPONENT . '/freemode_' . $theoption,
        get_string('title_' . $theoption, constants::M_COMPONENT), "", 1));
    }
}
