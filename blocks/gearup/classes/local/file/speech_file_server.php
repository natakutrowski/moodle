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

namespace block_gearup\local\file;

use block_gearup\di;
use block_gearup\local\http\api_exception;
use context;
use core_text;

/**
 * Speech file server.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class speech_file_server {

    /** @var context */
    protected context $context;
    /** @var int */
    protected int $userid;
    /** @var int */
    protected int $missionid;
    /** @var string */
    protected string $storyline;
    /** @var int */
    protected int $messageid;
    /** @var object */
    protected $mission = null;

    /**
     * Constructor.
     *
     * @param context $context The context.
     * @param int $userid The user ID.
     * @param int $missionid The mission ID.
     * @param string $storyline The storyline (description, instructions, feedback).
     * @param int $messageid The message ID (0-based).
     */
    public function __construct(context $context, int $userid, int $missionid, string $storyline, int $messageid) {
        $this->context = $context;
        $this->userid = $userid;
        $this->missionid = $missionid;
        $this->storyline = $storyline;
        $this->messageid = $messageid;
    }

    /**
     * Can access.
     *
     * @return bool
     */
    public function can_access(): bool {
        if (!di::get('lm')->is_active() || !di::get('lm')->use_speech()) {
            return false;
        } else if (!in_array($this->storyline, ['description', 'instructions', 'feedback'])) {
            return false;
        }

        $repo = di::get('repository');
        $mh = di::get('mission_helper');
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($this->context);

        $canmanage = $ap->can_manage($this->userid);
        $missioninst = null;

        $mission = $this->get_mission($this->missionid);
        if (!$mission) {
            return false;
        } else if (!$mh->is_a_quest($mission)) {
            return false;
        } else if ($mission->get_context()->id != $this->context->id) {
            return false;
        } else if (!$mission->get_voice_id()) {
            return false;
        } else if (!$canmanage) {
            try {
                $missioninst = $repo->get_instance_by_subject_id($mission->get_id(), $this->userid);
            } catch (\moodle_exception $e) {
                return false;
            }
        }

        if (!$canmanage) {
            if (!$missioninst) {
                return false;
            }
            if ($this->storyline === 'instructions' && !$mh->has_started($missioninst)) {
                return false;
            } else if ($this->storyline === 'feedback' && !$mh->has_completed($missioninst)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the file.
     *
     * @return \stored_file|null
     */
    public function get_file(): ?\stored_file {
        $itemid = $this->missionid;
        $filearea = 'speech';
        $filepath = "/{$this->storyline}/";
        $filename = $this->messageid;

        $fs = get_file_storage();
        $file = $fs->get_file($this->context->id, 'block_gearup', $filearea, $itemid, $filepath, $filename);
        if (!$file) {
            $bindata = $this->generate_speech();
            $mimetype = 'audio/mp3';

            // Save the file when the generation failed.
            if ($bindata === null) {
                $bindata = 'Level Up Rocks! 25499x'; // Hash starts with 13370 :).
                $mimetype = 'text/plain';
            }

            $file = $fs->create_file_from_string((object) [
                'contextid' => $this->context->id,
                'component' => 'block_gearup',
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => $filepath,
                'filename' => $filename,
                'mimetype' => $mimetype,
            ], $bindata);
        }

        if (strpos($file->get_mimetype(), 'audio/') !== 0) {
            return null;
        }
        return $file;
    }

    /**
     * Get the mission.
     *
     * @return ?\block_gearup\local\mission\mission
     */
    protected function get_mission() {
        if (!$this->mission) {
            $repo = di::get('repository');
            $this->mission = $repo->get_mission($this->missionid);
        }
        return $this->mission;
    }

    /**
     * Generate the speech.
     *
     * @return ?string The binary data of the speech.
     */
    protected function generate_speech() {
        $mission = $this->get_mission();
        if (!$mission) {
            return null;
        } else if (!$mission->get_voice_id()) {
            return null;
        }

        $text = '';
        if ($this->storyline === 'description') {
            $text = $mission->get_description();
        } else if ($this->storyline === 'instructions') {
            $text = $mission->get_instructions();
        } else if ($this->storyline === 'feedback') {
            $text = $mission->get_feedback();
        }

        // Tokenise the story line, extract the message and remove the placeholders.
        $messages = array_filter(preg_split("/[\r\n]+/m", $text ?? ''), function ($t) {
            return !empty(trim($t));
        });
        $message = $messages[(int) $this->messageid] ?? '';
        $message = trim(str_replace(['[objectives]', '[rewards]', '[firstname]', '[fullname]'], '', $message));
        $message = core_text::substr($message, 0, 1000);

        if (empty($message)) {
            return null;
        }

        try {
            return di::get('api_client')->post('/api/v1/quest/speech.mp3', [
                'text' => $message,
                'voice_id' => $mission->get_voice_id(),
            ])->response ?? null;
        } catch (api_exception $e) {
            if ($e->get_http_code() >= 400 && $e->get_http_code() < 500) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Create from pluginfile arguments.
     *
     * @param context $context The context.
     * @param int $userid The user ID.
     * @param array $args The arguments.
     * @return static
     */
    public static function from_pluginfile(context $context, $userid, array $args) {
        $missionid = (int) (array_shift($args) ?? 0);
        $storyline = array_shift($args) ?? 'description';
        $cachebuster = (int) (array_shift($args) ?? 0);
        $filename = array_shift($args) ?? '0.mp3';
        $messageid = (int) (explode('.', $filename, 2)[0] ?? 0);

        if ($cachebuster > di::get('clock')->time()) {
            throw new \moodle_exception('Possible cache poisoning detected.');
        }

        return new static($context, $userid, $missionid, $storyline, $messageid);
    }

}
