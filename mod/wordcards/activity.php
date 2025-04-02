<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Displays the global scatter.
 *
 * @package mod_wordcards
 * @author  Frédéric Massart - FMCorz.net
 */

require_once(__DIR__ . '/../../config.php');

use mod_wordcards\constants;

$cmid = required_param('id', PARAM_INT);
// the step that the user is requesting
$nextstep = optional_param('nextstep', mod_wordcards_module::STATE_STEP1, PARAM_TEXT);
// the most recent step they came from
$oldstep = optional_param('oldstep', mod_wordcards_module::STATE_TERMS, PARAM_TEXT);

// request a reattempt
$reattempt = optional_param('reattempt', 0, PARAM_INT);
// cancel an attempt
$cancelattempt = optional_param('cancelattempt', 0, PARAM_INT);
// embed mode
$embed = optional_param('embed', 0, PARAM_INT);

$mod = mod_wordcards_module::get_by_cmid($cmid);
$course = $mod->get_course();
$cm = $mod->get_cm();

$PAGE->set_url('/mod/wordcards/activity.php', ['id' => $cmid, 'oldstep' => $oldstep, 'nextstep' => $nextstep, 'embed' => $embed]);
require_login($course, true, $cm);
$mod->require_view();

// create a new attempt and set it to STATE_TERMS (which should be bumped up to STATE_STEP1 shortly after)
if($mod->can_attempt()){
    // mark all terms as seen
    // we do this even on re-attempts, because terms may be inserted by sneaky teachers
    $mod->mark_terms_as_seen();

    if($reattempt) {
        $mod->create_reattempt();
    }
}

// fetch current step
list($currentstate) = $mod->get_state();

// is teacher?
$isteacher = ($mod->can_manage() || $mod->can_viewreports());

// cancel attempt?
// must be not a reattempt (url fiddling by someone) and not on END or TERM. But a teacher is always on END so we let teachers through
// because they may be in "switch role to student" mode
if (!$reattempt && $cancelattempt) {

    if ($currentstate !== mod_wordcards_module::STATE_END) {
            $mod->remove_attempt();
            // if there is no attempt, then when view calls "resume_progress" it will create a new attempt because all the terms are seen
            // so in that case we mark them as unseen
        if ($mod->get_latest_attempt() === false) {
            $mod->mark_terms_as_unseen();
        }
        redirect(new moodle_url('/mod/wordcards/view.php', ['id' => $cm->id, 'embed' => $embed]));
    }
}



// we use the suggested step if they are finished or a teacher
// otherwsie we use their currentstate (the step they are up to)
// $currentstate = the latest step they have acccess to
// $currentstep = the step we have agreed to display
if ($currentstate == mod_wordcards_module::STATE_END) {
    // we have endded, but we can go wherever we need to
    $currentstep = $nextstep;
} else {
    if ($currentstate != $nextstep) {
        $mod->resume_progress($currentstate);
        list($currentstep) = $mod->get_state();
    } else {
        $currentstep = $nextstep;
    }
}

// redirect to finished if this state end
if ($currentstep == mod_wordcards_module::STATE_END) {
    redirect(new moodle_url('/mod/wordcards/finish.php', ['id' => $cm->id, 'embed' => $embed, 'sesskey' => sesskey()]));
}


// do we need this anymore?
if ($currentstep == mod_wordcards_module::STATE_TERMS) {
    redirect(new moodle_url('/mod/wordcards/view.php', ['id' => $cm->id, 'embed' => $embed]));
}

// get our practicetype an wordpool
$practicetype = $mod->get_practicetype($currentstep);
$wordpool = $mod->get_wordpool($currentstep);

// if its  review type and we have no review words, we just use a learn pool,
// we used to skip such tabs, but grading would get messed up
if($wordpool == mod_wordcards_module::WORDPOOL_REVIEW) {
    $reviewpoolempty = !$mod->are_there_words_to_review();// $mod->get_review_terms(mod_wordcards_module::STATE_STEP2) ? false : true;
    if($reviewpoolempty){
        $wordpool = mod_wordcards_module::WORDPOOL_LEARN;
    };
}

// depending on wordpool set page title
$pagetitle = format_string($mod->get_mod()->name, true, $course->id);
if($wordpool == mod_wordcards_module::WORDPOOL_REVIEW) {
    $pagetitle .= ': ' . get_string('reviewactivity', 'mod_wordcards');
}else{
    $pagetitle .= ': ' .  get_string('learnactivity', 'mod_wordcards');
}


// iif it looks like we have had some vocab updates, request an update of the lang speech model
if($mod->get_mod()->hashisold) {
    $mod->set_region_passagehash();
}


$PAGE->navbar->add($pagetitle, $PAGE->url);
$PAGE->set_heading(format_string($course->fullname, true, $course->id));
$PAGE->set_title($pagetitle);

$config = get_config(constants::M_COMPONENT);
// get our page layout
if ($mod->get_mod()->foriframe == 1  || $embed == 1) {
    $PAGE->set_pagelayout('embedded');
}else if ($config->enablesetuptab || $embed == 2) {
    $PAGE->set_pagelayout('popup');
    $PAGE->add_body_class('poodll-wordcards-embed');
} else {
    $PAGE->set_pagelayout('incourse');
}

switch ($practicetype) {

    case mod_wordcards_module::PRACTICETYPE_SPEECHCARDS:
    case mod_wordcards_module::PRACTICETYPE_SPEECHCARDS_REV:
        // this library is licensed with the hippocratic license (https://github.com/EthicalSource/hippocratic-license/)
        // which is high minded but not GPL3 compat. so cant be distributed with plugin. Hence we load it from CDN
        if($config->animations == constants::M_ANIM_FANCY) {
            $PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css'));
        }
        break;
    default:
}

// this library is licensed with the hippocratic license (https://github.com/EthicalSource/hippocratic-license/)
// which is high minded but not GPL3 compat. so cant be distributed with plugin. Hence we load it from CDN
if($config->animations == constants::M_ANIM_FANCY) {
    $PAGE->requires->css(new moodle_url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css'));
}

$renderer = $PAGE->get_renderer('mod_wordcards');

echo $renderer->header();
$heading = $renderer->heading($pagetitle, 3, 'main');
$displaytext = \html_writer::div($heading, constants::M_CLASS . '_center');
echo $displaytext;

// show module intro if this is an old moodle version
if( $CFG->version < 2022041900) {
    if (!empty($mod->get_mod()->intro)) {
        echo $renderer->box(format_module_intro('wordcards', $mod->get_mod(), $cm->id), 'generalbox', 'intro');
    }
}

echo $renderer->navigation($mod, $currentstep);
// get wordpool
switch ($practicetype){

    case mod_wordcards_module::PRACTICETYPE_MATCHSELECT:
    case mod_wordcards_module::PRACTICETYPE_MATCHTYPE:
    case mod_wordcards_module::PRACTICETYPE_DICTATION:
    case mod_wordcards_module::PRACTICETYPE_LISTENCHOOSE:
    case mod_wordcards_module::PRACTICETYPE_SPACEGAME:
    case mod_wordcards_module::PRACTICETYPE_SPEECHCARDS:
    case mod_wordcards_module::PRACTICETYPE_SCATTER:
        $definitions = $mod->get_learn_terms($mod->fetch_step_termcount($currentstep));
        break;

    case mod_wordcards_module::PRACTICETYPE_WORDPREVIEW:
        $definitions = $mod->get_learn_terms(0);
        break;

    case mod_wordcards_module::PRACTICETYPE_WORDPREVIEW_REV:
        $countonly = false;
        $definitions = $mod->get_allreview_terms($countonly);
        break;

    case mod_wordcards_module::PRACTICETYPE_MATCHSELECT_REV:
    case mod_wordcards_module::PRACTICETYPE_MATCHTYPE_REV:
    case mod_wordcards_module::PRACTICETYPE_DICTATION_REV:
    case mod_wordcards_module::PRACTICETYPE_LISTENCHOOSE_REV:
    case mod_wordcards_module::PRACTICETYPE_SPACEGAME_REV:
    case mod_wordcards_module::PRACTICETYPE_SPEECHCARDS_REV:
    case mod_wordcards_module::PRACTICETYPE_SCATTER_REV:
    default:
        $definitions = $mod->get_review_terms($mod->fetch_step_termcount($currentstep));
        break;

}
switch ($practicetype){

    case mod_wordcards_module::PRACTICETYPE_MATCHSELECT:
    case mod_wordcards_module::PRACTICETYPE_MATCHTYPE:
    case mod_wordcards_module::PRACTICETYPE_DICTATION:
    case mod_wordcards_module::PRACTICETYPE_LISTENCHOOSE:
    case mod_wordcards_module::PRACTICETYPE_WORDPREVIEW:
    case mod_wordcards_module::PRACTICETYPE_MATCHSELECT_REV:
    case mod_wordcards_module::PRACTICETYPE_MATCHTYPE_REV:
    case mod_wordcards_module::PRACTICETYPE_DICTATION_REV:
    case mod_wordcards_module::PRACTICETYPE_LISTENCHOOSE_REV:
    case mod_wordcards_module::PRACTICETYPE_WORDPREVIEW_REV:
        echo $renderer->a4e_page($mod, $practicetype, $definitions, constants::CURRENTMODE_STEPS, $currentstep);
        break;
    case mod_wordcards_module::PRACTICETYPE_SPACEGAME:
    case mod_wordcards_module::PRACTICETYPE_SPACEGAME_REV:
        echo $renderer->spacegame_page($mod, $definitions, constants::CURRENTMODE_STEPS, $currentstep);
        break;

    case mod_wordcards_module::PRACTICETYPE_SPEECHCARDS:
    case mod_wordcards_module::PRACTICETYPE_SPEECHCARDS_REV:
        echo $renderer->speechcards_page($mod, $definitions, constants::CURRENTMODE_STEPS, $currentstep);
        break;
    // no longer using this
    case mod_wordcards_module::PRACTICETYPE_SCATTER:
    case mod_wordcards_module::PRACTICETYPE_SCATTER_REV:
    default:
        echo $renderer->scatter_page($mod, $wordpool, $currentstep);
}
echo $renderer->cancel_attempt_button($mod);

// Add the ids of all terms in my words pool to the page markup so that JS can see them.
$mywordspool = new \mod_wordcards\my_words_pool($course->id);
echo html_writer::div(
    '', '',
    ['id' => "my-words-ids", 'data-my-words-term-ids' => json_encode(array_keys($mywordspool->get_words()))]
);

echo $renderer->footer();
