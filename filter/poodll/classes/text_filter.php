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

namespace filter_poodll;

/*
 * __________________________________________________________________________
 *
 * PoodLL filter for Moodle 2.9 and above
 *
 *  This filter will replace any PoodLL filter string with the appropriate PoodLL widget
 *
 * @package    filter
 * @subpackage poodll
 * @copyright  2012 Justin Hunt  {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * __________________________________________________________________________
 */

 if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'poodll_base_text_filter');
} else {
    class_alias('\moodle_text_filter', 'poodll_base_text_filter');
}

class text_filter extends \poodll_base_text_filter {

    protected $adminconfig = null;
    protected $courseconfig = null;

    /**
     * Apply the filter to the text
     *
     * @see filter_manager::apply_filter_chain()
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     * @return string text after processing
     */
    public function filter($text, array $options = []) {
        if (!is_string($text)) {
            // non string data can not be filtered anyway
            return $text;
        }
        $newtext = $text;

        // No links or poodll curlys then .. bail
        $havelinks = !(stripos($text, '</a>') === false);
        $havepoodllcurlys = (strpos($text, '{POODLL:') !== false);
        if (!$havelinks) {
            if (!$havepoodllcurlys) {
                return $text;
            }
        }

        // get config
        $this->adminconfig = get_config('filter_poodll');

        // text links and poodll curlies can occasionally clash if they both attack the same link
        // since curlys generally use js, it will happen after exts, and we can not easily tell from php that they will clash
        // it's mostly handled ok now by cutting out ?params in mediaparser.js which deals with cors issues caused by params
        // but it would be cooler if we did not have to do that because then we could use Polly URLs in mediaparser templates

        // if text has poodll curly brackets, lets parse
        if ($havepoodllcurlys) {
            // check for poodll curly brackets notation
            $search = '/{POODLL:.*?}/is';
            if (!is_string($text)) {
                // non string data can not be filtered anyway
                return $text;
            }
            $newtext = preg_replace_callback($search, [$this, 'filter_poodll_process'], $newtext);
        }

        // if the text has a poodll player widget we want to  prevent other filters from messing with it
        // so we will add class/es that tell the filters to ignore media in between a div containing poodllplayerwidgetnoshow
        if (preg_match('/class\s*=\s*[\'"](?:[^\'"]*\s+)?poodllplayerwidgetnoshow(?:\s+[^\'"]*)?[\'"]/i', $newtext)) {
            $newtext = \filter_poodll\filtertools::add_nomediaplugin_class_to_playerwidgets($newtext);
        }

        // if text has links
        if ($havelinks) {
            // get handle extensions
            $exts = \filter_poodll\filtertools::fetch_extensions();
            $handleexts = [];
            foreach ($exts as $ext) {
                if ($ext != 'youtube' && $this->fetchconf('handle' . $ext)) {
                    $handleexts[] = $ext;
                }
            }
            // do all the non youtube extensions in one foul swoop
            if (!empty($handleexts)) {
                $handleextstring = implode('|', $handleexts);
                // $oldsearch = '/<a\s[^>]*href="([^"#\?]+\.(' .  $handleextstring. '))(\?d=([\d]{1,4})x([\d]{1,4}))?"[^>]*>([^>]*)<\/a>/is';
                $search = '/<a\s[^>]*href="([^"#\?]+\.(' . $handleextstring . '))(.*?)"[^>]*>([^>]*)<\/a>/is';
                $newtext = preg_replace_callback($search, [$this, 'filter_poodll_allexts_callback'], $newtext);
            }

            // check for legacy pdl links
            $search = '/<a\s[^>]*href="([^"#\?]+\.(pdl))(.*?)"[^>]*>([^>]*)<\/a>/is';
            $newtext = preg_replace_callback($search, [$this, 'filter_poodll_pdl_callback'], $newtext);

            // check for youtube
            if ($this->fetchconf('handleyoutube')) {
                $search =
                        '/<a\s[^>]*href="(?:https?:\/\/)?(?:www\.)?youtu(?:\.be|be\.com)\/(?:watch\?v=|v\/)?([\w-]{10,})(?:.*?)<\/a>/is';
                $newtext = preg_replace_callback($search, [$this, 'filter_poodll_youtube_callback'], $newtext);
            }
        }// end of if $havelinks

        // add nomediaplugin or nopoodll tags if we need to
        // } else if (preg_match('/<(a|video|audio)\s[^>]*/', $tag, $tagmatches) && $sizeofmatches > 1 &&
        // (empty($validtag) || $tagname === strtolower($tagmatches[1]))) {
        // Looking for a starting tag. Ignore tags embedded into each other.
        // $validtag = $tag;
        // $tagname = strtolower($tagmatches[1]);
        // } else {

        // return the correct thing to wherever called us
        if (is_null($newtext) || $newtext === $text) {
            // error or not filtered
            return $text;
        }
        return $newtext;
    }

    private function fetchconf($prop) {
        global $COURSE;

        // I don't know why we need this whole courseconfig business.
        // we are supposed to be able to just call $this->localconfig / $this->localconfig[$propertyname]
        // as per here:https://docs.moodle.org/dev/Filters#Local_configuration , but its always empty
        // at least at course context, in mod context it works ...
        // I just gave up and do it myself and stuff it in $this->courseconfig . bug?? Justin 20150106
        if ($this->localconfig && !empty($this->localconfig)) {
            $this->courseconfig = $this->localconfig;
        }

        if ($this->courseconfig === null) {
            $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'filter_poodll', 'local_config');
            $contextid = \context_course::instance($COURSE->id)->id;
            $data = $cache->get($contextid);
            if ($data === false) {
                $data = filter_get_local_config('poodll', $contextid);
                $cache->set($contextid, $data);
            }
            $this->courseconfig = $data;
        }

        if ($this->courseconfig && isset($this->courseconfig[$prop]) && $this->courseconfig[$prop] != 'sitedefault') {
            return $this->courseconfig[$prop];
        } else {
            return isset($this->adminconfig->{$prop}) ? $this->adminconfig->{$prop} : false;
        }
    }

    /**
     * Replace youtube links with player
     *
     * @param  $link
     * @return string
     */
    private function filter_poodll_youtube_callback($link) {
        return $this->filter_poodll_process($link, 'youtube');
    }

    /**
     * Replace links with player/widget
     *
     * @param  $link
     * @return string
     */
    private function filter_poodll_allexts_callback($link) {
        return $this->filter_poodll_process($link, $link[2]);
    }

    /**
     * Replace legacy pdl links with widget
     *
     * @param  $link
     * @return string
     */
    function filter_poodll_pdl_callback($link) {
        global $CFG;

        // strip the .pdl extension
        $len = strlen($link[1]);
        $trimpoint = strpos($link[1], ".pdl");
        $key = substr($link[1], 0, $trimpoint);
        if (strpos($key, "https://") === 0 && $len > 12) {
            $key = substr($key, 8);
        } else if (strpos($key, "http://") === 0 && $len > 11) {
            $key = substr($key, 7);
        }
        $fstring = '';

        // see if there is a parameter to this widget
        $pos = strpos($key, "_");
        $param = "";

        // if yes, trim it off the key and get its value
        if ($pos) {
            $param = substr($key, $pos + 1);
            $key = substr($key, 0, $pos);
        }

        // Depending on the widget, make up a filter string
        switch ($key) {
            case "audiorecorder":
                $fstring = "{POODLL:type=audiorecorder}";
                break; // Not implemented.
            case "videorecorder":
                $fstring = "{POODLL:type=videorecorder}";
                break; // Not implemented.
            case "whiteboardsimple":
                $fstring = "{POODLL:type=whiteboard,mode=simple,standalone=true}";
                break; // Not implemented.
            case "whiteboardfull":
                $fstring = "{POODLL:type=whiteboard,mode=normal,standalone=true}";
                break; // Not implemented.
            case "snapshot":
                $fstring = "{POODLL:type=snapshot}";
                break;
            case "stopwatch":
                $fstring = "{POODLL:type=stopwatch}";
                break;
            case "dice":
                $fstring = "{POODLL:type=dice,dicecount=$param}";
                break;
            case "calculator":
                $fstring = "{POODLL:type=calculator}";
                break;
            case "countdown":
                $fstring = "{POODLL:type=countdown,initseconds=$param}";
                break;
            case "counter":
                $fstring = "{POODLL:type=counter}";
                break;
            case "flashcards":
                $fstring = "{POODLL:type=flashcards,cardset=$param}";
                break;
        }

        // resolve the string and return it
        if (!empty($fstring)) {
            return self::filter_poodll_process([$fstring]);
        } else {
            return '';
        }
    }

    /*
    *    Main callback function
    *
    */
    function filter_poodll_process(array $link, $ext = false) {
        global $CFG, $COURSE, $USER, $PAGE, $DB;

        $lm = new \filter_poodll\licensemanager();
        $registrationstatus = $lm->validate_license();
        if ($registrationstatus != \filter_poodll\licensemanager::FILTER_POODLL_IS_REGISTERED) {
            return $lm->fetch_unregistered_content($registrationstatus);
        }

        // get the Poodll configs
        $conf = get_object_vars(get_config('filter_poodll'));
        // setup context, but only fetch it when we need to
        $context = false;

        // we use this to see if its a web service calling this,
        // in which case we return the alternate content
        $iswebservice = false;
        $climode = defined('CLI_SCRIPT') && CLI_SCRIPT;
        if (!$climode) {
            $iswebservice = strpos($PAGE->url, $CFG->wwwroot . '/webservice/') === 0;
        }

        // get our filter props
        if ($ext) {
            $filterprops = \filter_poodll\filtertools::fetch_filter_properties_fromurl($link, $ext, $iswebservice);
        } else {
            $filterprops = \filter_poodll\filtertools::fetch_filter_properties($link[0]);
        }

        // if we have no props, quit
        if (empty($filterprops)) {
            return "";
        }

        // if this was a link that was filtered, and the content was not text , we don't like it
        // it most likely should be let alone or hidden as it is an icon to accompany the player
        // in teh case of attachements/resources/podcasts etc
        if ($ext) {
            if (empty($filterprops['TITLE'])) {
                return $link[0];
            }
        }

        // if this was a link but it had a "nopoodll" class on it then we ought to ignore it
        if ($ext) {
            if (preg_match('/class="[^"]*nopoodll/i', $link[0])) {
                return $link[0];
            }
        }

        // if we want to ignore the filter (for "how to use poodll" or "cut and paste" this style use) we let it go
        // to use this, make the last parameter of the filter passthrough=1
        if (!empty($filterprops['passthrough'])) {
            return str_replace(",passthrough=1", "", $link[0]);
        }

        // set a default end tag of none
        $endtag = false;

        // determine which template we are using
        // If we have an extension then it is from link
        // get our template info
        if ($ext) {
            // in a special case it is possible for the player to be specified in the url params
            // however usually it will be from preferences
            if (array_key_exists('player', $filterprops) && !empty($filterprops['player'])) {
                $playerkey = $filterprops['player'];
            } else {
                $playerkey = $this->fetchconf('useplayer' . $ext);
            }
            $tempindex = 0;
            $templatenumbers = \filter_poodll\filtertools::fetch_template_indexes($conf);
            foreach ($templatenumbers as $templatenumber) {
                if ($conf['templatekey_' . $templatenumber] == $playerkey) {
                    $tempindex = $templatenumber;
                    break;
                }
            }
            if (!$tempindex) {
                return "";
            }
        } else {
            // else its from a  poodll filter string
            for ($tempindex = 1; $tempindex <= $conf['templatecount']; $tempindex++) {
                if ($filterprops['type'] == $conf['templatekey_' . $tempindex]) {
                    break;
                } else if ($filterprops['type'] == $conf['templatekey_' . $tempindex] . '_end') {
                    $endtag = true;
                    break;
                }
            }
            // no key could be found if got all the way to templatecount
            if ($tempindex == $conf['templatecount'] + 1) {
                return '';
            }
        }

        // fetch our template
        if ($endtag) {
            $poodlltemplate = $conf['templateend_' . $tempindex];
            $alternatecontent = $conf['templatealternate_' . $tempindex];
        } else {
            $poodlltemplate = $conf['template_' . $tempindex];
            $alternatecontent = $conf['templatealternate_end_' . $tempindex];
        }

        // fetch dataset info
        $datasetbody = $conf['dataset_' . $tempindex];
        $datasetvars = $conf['datasetvars_' . $tempindex];

        // js custom script
        // we really just want to be sure anything that appears in custom script
        // is stored in $filterprops and passed to js. we dont replace it server side because
        // of caching
        $jscustomscript = $conf['templatescript_' . $tempindex];

        // if we have a download variable in the template
        $filterprops['CANDOWNLOAD'] = 'hide';
        if ($CFG->filter_poodll_download_media_ok) {
            $context = \context_course::instance($COURSE->id);
            if (has_capability('filter/poodll:candownloadmedia', $context)) {
                $filterprops['CANDOWNLOAD'] = 'filter_poodll_download_button';
            };
        }

        // replace the specified names with spec values
        foreach ($filterprops as $name => $value) {
            $poodlltemplate = str_replace('@@' . $name . '@@', $value, $poodlltemplate);
            $datasetvars = str_replace('@@' . $name . '@@', $value, $datasetvars);
            $alternatecontent = str_replace('@@' . $name . '@@', $value, $alternatecontent);
        }

        // fetch defaults for this template
        $defaults = $conf['templatedefaults_' . $tempindex];
        if (!empty($defaults)) {
            $defaults = "{POODLL:" . $defaults . "}";
            $defaultprops = \filter_poodll\filtertools::fetch_filter_properties($defaults);
            // replace our defaults, if not spec in the the filter string
            if (!empty($defaultprops)) {
                foreach ($defaultprops as $name => $value) {
                    if (!array_key_exists($name, $filterprops)) {
                        // if we have options as defaults, lets just take the first one
                        if (strpos($value, '|') !== false) {
                            $valuearray = explode('|', $value);
                            $value = $valuearray[0];
                        }
                        $poodlltemplate = str_replace('@@' . $name . '@@', strip_tags($value), $poodlltemplate);
                        $datasetvars = str_replace('@@' . $name . '@@', strip_tags($value), $datasetvars);
                        $alternatecontent = str_replace('@@' . $name . '@@', strip_tags($value), $alternatecontent);
                        // stash for using in JS later
                        $filterprops[$name] = $value;
                    }
                }
            }
        }

        // If we have autoid lets deal with that
        $autoid = 'filterpoodll_' . time() . (string) rand(100, 32767);
        $poodlltemplate = str_replace('@@AUTOID@@', $autoid, $poodlltemplate);
        $alternatecontent = str_replace('@@AUTOID@@', $autoid, $alternatecontent);
        // stash this for passing to js
        $filterprops['AUTOID'] = $autoid;

        // If we need a Cloud Poodll token, lets fetch it
        if (strpos($poodlltemplate, '@@CLOUDPOODLLTOKEN@@') &&
                !empty($conf['cpapiuser']) &&
                !empty($conf['cpapisecret'])) {
            $lm = new \filter_poodll\licensemanager();
            $tokenobject = $lm->fetch_token($conf['cpapiuser'], $conf['cpapisecret']);
            if(isset($tokenobject->token)){
                $token = $tokenobject->token;
            }else{
                $token = false;
            }
            if(!$token){$token = 'NO_TOKEN RETRIEVED';
            }
            $poodlltemplate = str_replace('@@CLOUDPOODLLTOKEN@@', $token, $poodlltemplate);
            // stash this for passing to js
            $filterprops['CLOUDPOODLLTOKEN'] = $token;
        }

        // If this is a renderer call, lets do it
        // it will be a function in a renderer with a name that begins with "embed_" .. e.g "embed_something"
        // the args filterprops will be a pipe delimited string of args, eg {POODLL:type="mod_ogte",function="embed_table",args="arg1|arg2|arg3"}
        // if the args string contains "cloudpoodlltoken" it will be replaced with the actual cloud poodll token.
        if(isset($filterprops['renderer']) && isset($filterprops['function']) && strpos($filterprops['function'], 'embed_') === 0){
            try {
                if(!isset($token)){$token = false;
                }
                $somerenderer = $PAGE->get_renderer($filterprops['renderer']);
                $args = [];
                if(isset($filterprops['args'])){
                    $argsstring = str_replace('cloudpoodlltoken', $token, $filterprops['args']);
                    $argsarray = explode('|', $argsstring);
                }
                $renderedcontent = call_user_func_array([$somerenderer, $filterprops['function']], $argsarray);
                $poodlltemplate = str_replace('@@renderedcontent@@', $renderedcontent, $poodlltemplate);
            } catch (Exception $e) {
                $poodlltemplate = str_replace('@@renderedcontent@@', 'failed to render!!!', $poodlltemplate);
            }
        }

        // If template requires a MOODLEPAGEID lets give them one
        // this is a bit redundant now it can be done now with @@URLPARAM:id@@
        $moodlepageid = optional_param('id', 0, PARAM_INT);
        $poodlltemplate = str_replace('@@MOODLEPAGEID@@', $moodlepageid, $poodlltemplate);
        $datasetvars = str_replace('@@MOODLEPAGEID@@', $moodlepageid, $datasetvars);
        $alternatecontent = str_replace('@@MOODLEPAGEID@@', $moodlepageid, $alternatecontent);
        // stash this for passing to js
        $filterprops['MOODLEPAGEID'] = $moodlepageid;

        // we should stash our wwwroot too
        $poodlltemplate = str_replace('@@WWWROOT@@', $CFG->wwwroot, $poodlltemplate);
        $datasetvars = str_replace('@@WWWROOT@@', $CFG->wwwroot, $datasetvars);
        $alternatecontent = str_replace('@@WWWROOT@@', $CFG->wwwroot, $alternatecontent);
        // actually this is available from JS anyway M.cfg.wwwroot . But lets make it easy for people
        $filterprops['WWWROOT'] = $CFG->wwwroot;

        // if we have urlparam variables e.g @@URLPARAM:id@@
        if (strpos($poodlltemplate . ' ' . $datasetvars . ' ' . $alternatecontent . ' ' . $jscustomscript, '@@URLPARAM:') !==
                false) {
            $urlparamstubs = explode('@@URLPARAM:', $poodlltemplate);
            $dvstubs = explode('@@URLPARAM:', $datasetvars);

            if ($dvstubs) {
                $urlparamstubs = array_merge($urlparamstubs, $dvstubs);
            }
            $jsstubs = explode('@@URLPARAM:', $jscustomscript);
            if ($jsstubs) {
                $urlparamstubs = array_merge($urlparamstubs, $jsstubs);
            }
            $altstubs = explode('@@URLPARAM:', $alternatecontent);
            if ($altstubs) {
                $urlparamstubs = array_merge($urlparamstubs, $altstubs);
            }

            // URL Param Props
            $count = 0;
            foreach ($urlparamstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $urlprop = substr($propstub, 0, $end);
                if (empty($urlprop)) {
                    continue;
                }

                // check if it exists in the params to the url and if so, set it.
                $propvalue = optional_param($urlprop, '', PARAM_TEXT);
                $poodlltemplate = str_replace('@@URLPARAM:' . $urlprop . '@@', $propvalue, $poodlltemplate);
                $datasetvars = str_replace('@@URLPARAM:' . $urlprop . '@@', $propvalue, $datasetvars);
                $alternatecontent = str_replace('@@URLPARAM:' . $urlprop . '@@', $propvalue, $alternatecontent);
                // stash this for passing to js
                $filterprops['URLPARAM:' . $urlprop] = $propvalue;
            }//end of for each
        }//end of if we have@@URLPARAM

        // if we have string variables e.g @@STRING:name@@
        if (strpos($poodlltemplate . ' ' . $datasetvars . ' ' . $alternatecontent . ' ' . $jscustomscript, '@@STRING:') !==
        false) {
            $strstubs = explode('@@STRING:', $poodlltemplate);

            // STRING Props
            $count = 0;
            $stringmanager = get_string_manager();
            foreach ($strstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $strprop = substr($propstub, 0, $end);
                if (empty($strprop)) {
                    continue;
                }

                // check if str exists and set it.
                if ($stringmanager->string_exists($strprop, 'filter_poodll')) {
                    $propvalue = get_string($strprop, 'filter_poodll');
                } else {
                    $propvalue = ucwords(str_replace(['-', '_'], " ", $strprop));
                }

                $poodlltemplate = str_replace('@@STRING:' . $strprop . '@@', $propvalue, $poodlltemplate);
                $datasetvars = str_replace('@@STRING:' . $strprop . '@@', $propvalue, $datasetvars);
                $alternatecontent = str_replace('@@STRING:' . $strprop . '@@', $propvalue, $alternatecontent);
                // stash this for passing to js
                $filterprops['STRING:' . $strprop] = $propvalue;
            }//end of for each
        }//end of if we have @@STRING.

        // if we have course variables e.g @@COURSE:ID@@
        if (strpos($poodlltemplate . ' ' . $datasetvars . ' ' . $alternatecontent . ' ' . $jscustomscript, '@@COURSE:') !==
                false) {
            $coursevars = get_object_vars($COURSE);
            // custom fields
            if(!empty($filterprops['courseid']) && is_numeric($filterprops['courseid'] )) {
                $thecourse = get_course($filterprops['courseid']);
                if($thecourse) {
                    $coursevars = get_object_vars($thecourse);
                }
            }else{
                $coursevars = get_object_vars($COURSE);
                $filterprops['courseid'] = $COURSE->id;
            }
            if($coursevars){
                // custom fields
                if(class_exists('\core_customfield\handler')) {
                    $handler = \core_customfield\handler::get_handler('core_course', 'course');
                    $customfields = $handler->get_instance_data($filterprops['courseid']);
                    foreach ($customfields as $customfield) {
                        if (empty($customfield->get_value())) {
                            continue;
                        }
                        $shortname = $customfield->get_field()->get('shortname');
                        $coursevars[$shortname] = $customfield->get_value();
                    }
                }
            }
            $coursepropstubs = explode('@@COURSE:', $poodlltemplate);
            $dstubs = explode('@@COURSE:', $datasetvars);
            if ($dstubs) {
                $coursepropstubs = array_merge($coursepropstubs, $dstubs);
            }
            $jstubs = explode('@@COURSE:', $jscustomscript);
            if ($jstubs) {
                $coursepropstubs = array_merge($coursepropstubs, $jstubs);
            }
            $altstubs = explode('@@COURSE:', $alternatecontent);
            if ($altstubs) {
                $coursepropstubs = array_merge($coursepropstubs, $altstubs);
            }

            // Course Props
            $profileprops = false;
            $count = 0;
            foreach ($coursepropstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $coursepropallcase = substr($propstub, 0, $end);
                $courseprop = strtolower($coursepropallcase);

                // check if it exists in course
                if (array_key_exists($courseprop, $coursevars)) {
                    $propvalue = $coursevars[$courseprop];
                } else if ($courseprop == 'contextid') {
                    if (!$context) {
                        $context = \context_course::instance($COURSE->id);
                    }
                    if ($context) {
                        $propvalue = $context->id;
                    }
                }
                // if we have a propname and a propvalue, do the replace
                if (!empty($courseprop) && !is_null($propvalue)) {
                    $poodlltemplate = str_replace('@@COURSE:' . $coursepropallcase . '@@', $propvalue, $poodlltemplate);
                    $datasetvars = str_replace('@@COURSE:' . $coursepropallcase . '@@', $propvalue, $datasetvars);
                    $alternatecontent = str_replace('@@COURSE:' . $coursepropallcase . '@@', $propvalue, $alternatecontent);
                    // stash this for passing to js
                    $filterprops['COURSE:' . $coursepropallcase] = $propvalue;
                }
            }
        }//end of if @@COURSE

        // if we have user variables e.g @@USER:FIRSTNAME@@
        // It is a bit wordy, because trying to avoid loading a lib
        // or making a DB call if unneccessary
        if (strpos($poodlltemplate . ' ' . $datasetvars . ' ' . $jscustomscript . ' ' . $alternatecontent, '@@USER:') !==
                false) {
            $uservars = get_object_vars($USER);
            $userpropstubs = explode('@@USER:', $poodlltemplate);
            $dstubs = explode('@@USER:', $datasetvars);
            if ($dstubs) {
                $userpropstubs = array_merge($userpropstubs, $dstubs);
            }
            $jstubs = explode('@@USER:', $jscustomscript);
            if ($jstubs) {
                $userpropstubs = array_merge($userpropstubs, $jstubs);
            }
            $altstubs = explode('@@USER:', $alternatecontent);
            if ($altstubs) {
                $userpropstubs = array_merge($userpropstubs, $altstubs);
            }

            // User Props
            $profileprops = false;
            $count = 0;
            foreach ($userpropstubs as $propstub) {
                // we don't want the first one, its junk
                $count++;
                if ($count == 1) {
                    continue;
                }
                // init our prop value
                $propvalue = false;

                // fetch the property name
                // user can use any case, but we work with lower case version
                $end = strpos($propstub, '@@');
                $userpropallcase = substr($propstub, 0, $end);
                $userprop = strtolower($userpropallcase);

                // check if it exists in user, else look for it in profile fields
                if (array_key_exists($userprop, $uservars)) {
                    $propvalue = $uservars[$userprop];
                } else {
                    if (!$profileprops) {
                        require_once("$CFG->dirroot/user/profile/lib.php");
                        $profileprops = get_object_vars(profile_user_record($USER->id));
                    }
                    if ($profileprops && array_key_exists($userprop, $profileprops)) {
                        $propvalue = $profileprops[$userprop];
                    } else {
                        switch ($userprop) {
                            case 'picurl':
                                require_once("$CFG->libdir/outputcomponents.php");
                                global $PAGE;
                                $userpicture = new \user_picture($USER);
                                $propvalue = $userpicture->get_url($PAGE);
                                break;

                            case 'pic':
                                global $OUTPUT;
                                $propvalue = $OUTPUT->user_picture($USER, ['popup' => true]);
                                break;
                        }
                    }
                }

                // if we have a propname and a propvalue, do the replace
                if (!empty($userprop) && !is_null($propvalue)) {
                    // echo "userprop:" . $userprop . '<br/>propvalue:' . $propvalue;
                    $poodlltemplate = str_replace('@@USER:' . $userpropallcase . '@@', $propvalue, $poodlltemplate);
                    $datasetvars = str_replace('@@USER:' . $userpropallcase . '@@', $propvalue, $datasetvars);
                    $alternatecontent = str_replace('@@USER:' . $userpropallcase . '@@', $propvalue, $alternatecontent);
                    // stash this for passing to js
                    $filterprops['USER:' . $userpropallcase] = $propvalue;
                }
            }
        }//end of of we @@USER

        // if we have a dataset body
        // we split the $data_vars string passed in by user (which should have had all the replacing done)
        // into the vars array. This is passed to get_records_sql and the returned result is stored
        // in filter props. If its a single record, its available to the body area.
        // otherwise it needs to be accessewd from javascript in the DATASET variable
        $filterprops['DATASET'] = false;
        if ($datasetbody) {
            $vars = [];
            if ($datasetvars) {
                $vars = explode(',', $datasetvars);
            }
            // turn numeric vars into numbers (not strings)
            $queryvars = [];
            for ($i = 0; $i < sizeof($vars); $i++) {
                if (is_numeric($vars[$i])) {
                    $queryvars[] = intval($vars[$i]);
                } else {
                    $queryvars[] = $vars[$i];
                }
            }
            try {
                $alldata = $DB->get_records_sql($datasetbody, $queryvars);
                if ($alldata) {
                    $filterprops['DATASET'] = $alldata;
                    // replace the specified names with spec values, if its a one element array
                    if (sizeof($filterprops['DATASET']) == 1) {
                        $thedata = get_object_vars(array_pop($alldata));
                        foreach ($thedata as $name => $value) {
                            $poodlltemplate = str_replace('@@DATASET:' . $name . '@@', $value, $poodlltemplate);
                            $alternatecontent = str_replace('@@DATASET:' . $name . '@@', $value, $alternatecontent);
                        }
                    }
                }
            } catch (Exception $e) {
                // do nothing;
            }
        }//end of if dataset

        // if this is a web service/mobile app we just return the alternate content
        if ($iswebservice && !empty($alternatecontent)) {
            return $alternatecontent;
        }

        // If this is the end tag we don't need to subsequent CSS and JS stuff. We already did it.
        if ($endtag) {
            return $poodlltemplate;
        }

        // get the conf info we need for this template
        $thescript = $conf['templatescript_' . $tempindex];
        $defaults = $conf['templatedefaults_' . $tempindex];
        $requirejs = $conf['templaterequire_js_' . $tempindex];
        $requirecss = $conf['templaterequire_css_' . $tempindex];
        $requirecss = str_replace('@@WWWROOT@@', $CFG->wwwroot, $requirecss);
        $requirejs = str_replace('@@WWWROOT@@', $CFG->wwwroot, $requirejs);

        // are we AMD and Moodle 2.9 or more?
        $requireamd = $conf['template_amd_' . $tempindex];

        // figure out if this is https or http. We don't want to scare the browser
        if (!$climode && strpos($PAGE->url->out(), 'https:') === 0) {
            $scheme = 'https:';
        } else {
            $scheme = 'http:';
        }

        // massage the js URL depending on schemes and rel. links etc. Then insert it
        // with AMD we set these as dependencies, so we don't need this song and dance
        if (!$requireamd) {
            $filterprops['JSLINK'] = false;
            if ($requirejs) {
                if (strpos($requirejs, '//') === 0) {
                    $requirejs = $scheme . $requirejs;
                } else if (strpos($requirejs, '/') === 0) {
                    $requirejs = $CFG->wwwroot . $requirejs;
                }

                // for load method: NO AMD
                $PAGE->requires->js(new \moodle_url($requirejs));

                // for load method: AMD
                // $require_js = substr($require_js, 0, -3);
                $filterprops['JSLINK'] = $requirejs;
            }

        }

        // massage the CSS URL depending on schemes and rel. links etc.
        if (!empty($requirecss)) {
            if (strpos($requirecss, '//') === 0) {
                $requirecss = $scheme . $requirecss;
            } else if (strpos($requirecss, '/') === 0) {
                $requirecss = $CFG->wwwroot . $requirecss;
            }
        }

        // set up our revision flag for forcing cache refreshes etc
        if (!empty($conf['revision'])) {
            $revision = $conf['revision'];
        } else {
            $revision = '0';
        }

        // if not too late: load css in header
        // if too late: inject it there via JS
        $filterprops['CSSLINK'] = false;
        $filterprops['CSSCUSTOM'] = false;

        // require any scripts from the template
        $customcssurl = false;
        if ($conf['templatestyle_' . $tempindex]) {
            $customcssurl = new \moodle_url('/filter/poodll/templatecss.php', ['t' => $tempindex, 'rev' => $revision]);
        }

        if (!$PAGE->headerprinted && !$PAGE->requires->is_head_done()) {
            if ($requirecss) {
                $PAGE->requires->css(new \moodle_url($requirecss));
            }
            if ($customcssurl) {
                $PAGE->requires->css($customcssurl);
            }
        } else {
            if ($requirecss) {
                $filterprops['CSSLINK'] = $requirecss;
            }
            if ($customcssurl) {
                $filterprops['CSSCUSTOM'] = $customcssurl->out();
            }

        }

        // Tell javascript which template this is
        $filterprops['TEMPLATEID'] = $tempindex;

        $jsmodule = [
                'name' => 'filter_poodll',
                'fullpath' => '/filter/poodll/module.js',
                'requires' => ['json'],
        ];

        // AMD or not, and then load our js for this template on the page
        if ($requireamd) {
            $generator = new \filter_poodll\templatescriptgenerator($tempindex);
            $templateamdscript = $generator->get_template_script();

            // props can't be passed at much length , Moodle complains about too many
            // so we do this ... lets hope it don't break things
            $jsonstring = json_encode($filterprops);
            $propshtml = \html_writer::tag('input', '',
                    ['id' => 'filter_poodll_amdopts_' . $filterprops['AUTOID'], 'type' => 'hidden', 'value' => $jsonstring]);
            $poodlltemplate = $propshtml . $poodlltemplate;

            // load define for this template. Later it will be called from loadtemplate
            if (!empty($templateamdscript)) {
                $PAGE->requires->js_amd_inline($templateamdscript);
            }
            // for AMD template script
            $PAGE->requires->js_call_amd('filter_poodll/template_amd', 'loadtemplate',
                    [['AUTOID' => $filterprops['AUTOID']]]);
            // echo $filterprops['AUTOID'] . PHP_EOL;

        } else {

            // require any scripts from the template
            $customjsurl = new \moodle_url('/filter/poodll/templatejs.php', ['t' => $tempindex, 'rev' => $revision]);
            $PAGE->requires->js($customjsurl);

            // for no AMD
            $PAGE->requires->js_init_call('M.filter_poodll_templates.loadtemplate', [$filterprops], false, $jsmodule);
        }

        // finally return our template text
        return $poodlltemplate;
    }//end of function

}//end of class
