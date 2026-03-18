<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

use block_gearup\form\html;
use block_gearup\local\speech\voice;
use block_gearup\local\visual\repository;
use block_gearup\local\visual\repository_with_context;
use block_gearup\di;
use context;
use core_collator;
use Locale;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form_utils {

    /**
     * Add an image group from a visual repository.
     *
     * @param object $mform The form.
     * @param string $name The name of the field.
     * @param string $label The label.
     * @param repository $repo The visual repository.
     * @param context $context The context.
     * @return object The element.
     */
    public static function add_image_group_from_repository($mform, $name, $label, repository $repo, ?context $context = null) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/gearup/classes/form/imagegroup.php');

        $visuals = $repo->get_visuals();
        $ctxvisuals = [];
        if ($context && $repo instanceof repository_with_context) {
            $ctxvisuals = $repo->get_visuals_in_context($context);
        }

        $options = [];
        if (!empty($ctxvisuals)) {
            $options[] = (object) [
                'label' => get_string('fromlibrary', 'block_gearup'),
                'options' => $ctxvisuals,
            ];
        }

        $options[] = (object) [
            'label' => get_string('frombuiltin', 'block_gearup'),
            'options' => $visuals,
        ];

        return $mform->addElement('block_gearup_imagegroup', $name, $label, $options);
    }

    /**
     * Add a JS AMD call in forms.
     *
     * Directly doing it in the form definition does not work with dynamic forms, so we are using
     * a custom element that registers the AMD call when the field is being rendered instead.
     *
     * @param object $mform The form.
     * @param string $module The AMD module.
     * @param string $function The AMD function name.
     * @param array $args The AMD function arguments.
     * @param string|null $fieldname The name of the field.
     * @return object The element.
     */
    public static function add_js_amd_call($mform, $module, $function, $args, $fieldname = null) {
        return $mform->addElement(html::register(), $fieldname, function () use ($module, $function, $args) {
            global $PAGE;
            $PAGE->requires->js_call_amd($module, $function, $args);
        });
    }

    /**
     * Add a voice selector to the form.
     *
     * @param object $mform The form.
     * @param string $fieldname The name of the field.
     * @return array The elements added to the form.
     */
    public static function add_voice_selector($mform, $fieldname) {
        $els = [];
        $allvoices = di::get('voices_repository')->get_voices();

        // Group by language code.
        $bycode = [];
        foreach ($allvoices as $voice) {
            $bycode[$voice->get_language_code()] ??= [];
            $bycode[$voice->get_language_code()][] = $voice;
        }

        // Placeholder voice field to ensure something is submitted when nothing is selected.
        $el0 = $mform->createElement(html::register(), 'voiceid_placeholder', function () use ($fieldname) {
            return \html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $fieldname,
                'value' => '',
            ]);
        });

        // Language field.
        $el1 = $mform->createElement('select',
            $fieldname . '_lang',
            get_string('language', 'core'),
            ['' => '--'] + array_combine(array_keys($bycode), array_map(function ($lang) {
                return Locale::getDisplayName($lang);
            },
            array_keys($bycode))),
            ['disabled' => 'disabled', 'class' => 'gu-max-w-64']
        );

        // Voice field.
        $options = array_reduce($allvoices, function ($carry, $voice) {
            $name = $voice->get_name();
            if ($voice->get_gender() === voice::GENDER_FEMALE) {
                $name .= ' 👩';
            } else {
                $name .= ' 👨';
            }
            $name .= ' (' . $voice->get_language_code() . ')';
            $carry[$voice->get_id()] = $name;
            return $carry;
        }, []);
        core_collator::ksort($options, core_collator::SORT_NATURAL);
        $options = ['' => '--'] + $options;
        $el2 = $mform->createElement('select',
            $fieldname,
            get_string('voice', 'block_gearup'),
            $options,
            ['style' => 'display: none;', 'class' => 'gu-max-w-64']
        );

        // Play button.
        $btnfieldid = \html_writer::random_id('voiceid_play');
        $el3 = $mform->createElement('static', $fieldname . '_play', '', \html_writer::div(\html_writer::tag('button',
            di::get('renderer')->pix_icon('t/go', get_string('play', 'block_gearup')),
            [
                'id' => $btnfieldid,
                'class' => 'btn btn-secondary btn-sm',
                'data-gu-action' => 'play-audio',
                'type' => 'button',
                'style' => 'display: none;',
            ]
        )), 'fitem');

        $els[] = $mform->addGroup([$el0, $el1, $el2, $el3],
            $fieldname . '_group',
            get_string('voice', 'block_gearup'),
            ' ',
            false
        );

        $el1->updateAttributes(['id' => 'id_voiceid_lang']);
        $el2->updateAttributes(['id' => 'id_voiceid']);

        $langfieldid = $el1->getAttribute('id');
        $voicefieldid = $el2->getAttribute('id');

        // Javascript to handle play and language change.
        $voicedata = array_values(array_map(function ($voice) {
            return [
                'id' => $voice->get_id(),
                'lang' => $voice->get_language_code(),
                'sample' => $voice->get_sample_url() ? $voice->get_sample_url()->out(false) : null,
            ];
        }, $allvoices));

        $jsonid = \html_writer::random_id();
        $els[] = $mform->addElement(html::register(), null, function ()
 use ($voicedata, $jsonid, $langfieldid, $voicefieldid, $btnfieldid) {
            global $PAGE;
            $PAGE->requires->js_amd_inline(<<<EOT
                require([], function() {
                    let audio;
                    const data = JSON.parse(document.getElementById('$jsonid').textContent);
                    const el = document.getElementById('$voicefieldid');
                    const orig = el.cloneNode(true);
                    const langEl = document.getElementById('$langfieldid');
                    const playBtn = document.getElementById('$btnfieldid');

                    function updatePlayButton() {
                        if (audio) {
                            audio.pause();
                            audio = null;
                        }

                        const voice = data.find(function(v) {
                            return v.id === el.value;
                        });

                        if (!voice || !voice.sample || !el.value || !langEl.value) {
                            playBtn.style.display = 'none';
                            playBtn.dataset.audio = '';
                            return;
                        }
                        playBtn.dataset.audio = voice.sample;
                        playBtn.style.display = '';
                    }

                    function updateVoiceField(show) {
                        if (!show) {
                            el.style.display = 'none';
                            el.value = '';
                            el.setAttribute('disabled', 'disabled');
                            updatePlayButton();
                            return;
                        }
                        el.removeAttribute('disabled');
                        el.style.display = '';
                        updatePlayButton();
                    }

                    function handleLangChange() {
                        const lang = langEl.value;
                        if (!lang) {
                            updateVoiceField(false);
                            return;
                        }
                        const nodes = Array.from(orig.childNodes).filter(function(node) {
                            if (node.tagName !== 'OPTION') {
                                return false;
                            }
                            const voice = data.find(function(v) {
                                return v.id === node.value;
                            });
                            if (!voice || voice.lang !== lang) {
                                return false;
                            }
                            return true;
                        });

                        if (!nodes.length) {
                            updateVoiceField(false);
                            return;
                        }

                        el.querySelectorAll('option').forEach(function(node) {
                            node.remove();
                        });
                        nodes.forEach(function(node) {
                            el.appendChild(node.cloneNode(true));
                        });
                        updateVoiceField(true);
                    }

                    function handleVoiceChange() {
                        updatePlayButton();
                    }

                    function handlePlayAudio(e) {
                        e.preventDefault();
                        if (!playBtn.dataset.audio) {
                            return;
                        }

                        if (audio) {
                            audio.pause();
                            return;
                        }

                        audio = document.createElement('audio');
                        audio.src = playBtn.dataset.audio;
                        audio.play();
                    }

                    function init() {
                        langEl.removeAttribute('disabled');
                        const voice = data.find(function(v) {
                            return v.id === el.value;
                        });
                        if (voice) {
                            langEl.value = voice.lang;
                            langEl.dispatchEvent(new Event('change'));
                        }
                        updateVoiceField(Boolean(langEl.value));
                    }

                    langEl.addEventListener('change', handleLangChange);
                    el.addEventListener('change', handleVoiceChange);
                    playBtn.addEventListener('click', handlePlayAudio);

                    init();

                });

            EOT);
            return di::get('renderer')->json_script($voicedata, $jsonid);
        });

        return $els;
    }
}
