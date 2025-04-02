<?php
/**
 * Created by PhpStorm.
 * User: zeinab
 * Date: 9/21/17
 * Time: 6:32 PM
 */



defined('MOODLE_INTERNAL') || die();

define('QTYPE_SPEECHACE_DIALECT_US_ENGLISH','en-us');
define('QTYPE_SPEECHACE_DIALECT_UK_ENGLISH','en-gb');
define('QTYPE_SPEECHACE_DIALECT_FR_FRENCH','fr-fr');

define('QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_ALWAYS','always');
define('QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_RESULT','result');

define('QTYPE_SPEECHACE_YESOPTION',true);
define('QTYPE_SPEECHACE_NOOPTION',false);

define('QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE','textOne');
define('QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO','textTwo');
define('QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE','textThree');
define('QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE','selectOne');
define('QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO','selectTwo');


define('QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_TEXTONE', 'textOne_default');
define('QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_TEXTTWO', 'textTwo_default');
define('QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_TEXTTHREE', 'textThree_default');
define('QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_SELECTONE','selectOne_default');
define('QTYPE_SPEECHACE_SCOREMESSAGES_RESET_VALUE_SELECTTWO','selectTwo_default');




global $qtype_speechace_dialect_options;
global $qtype_speechace_show_expert_audio_options;
global $qtype_speechace_numericscore_options;
global $qtype_speechace_scoremessages_defaults;


$qtype_speechace_dialect_options = array(
    QTYPE_SPEECHACE_DIALECT_US_ENGLISH=>get_string('dialect_us_english','qtype_speechace'),
    QTYPE_SPEECHACE_DIALECT_UK_ENGLISH=>get_string('dialect_gb_english','qtype_speechace'),
    QTYPE_SPEECHACE_DIALECT_FR_FRENCH=>get_string('dialect_fr_french','qtype_speechace')
);
$qtype_speechace_show_expert_audio_options=  array(
    QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_ALWAYS => get_string('showexpertaudio_always', 'qtype_speechace'),
    QTYPE_SPEECHACE_SHOW_EXPERT_AUDIO_RESULT=> get_string('showexpertaudio_result','qtype_speechace')
);

$qtype_speechace_numericscore_options = array(
    QTYPE_SPEECHACE_YESOPTION=>get_string('yesoption','qtype_speechace'),
    QTYPE_SPEECHACE_NOOPTION=> get_string('nooption','qtype_speechace')

);

$qtype_speechace_scoremessages_defaults = array(
    QTYPE_SPEECHACE_SCOREMESSAGES_TEXTONE=>get_string('scoremessages_default_textOne','qtype_speechace'),
    QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTWO=>get_string('scoremessages_default_textTwo','qtype_speechace'),
    QTYPE_SPEECHACE_SCOREMESSAGES_TEXTTHREE=>get_string('scoremessages_default_textThree','qtype_speechace'),
    QTYPE_SPEECHACE_SCOREMESSAGES_SELECTONE=>80,
    QTYPE_SPEECHACE_SCOREMESSAGES_SELECTTWO=>70
);


