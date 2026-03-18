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

namespace block_gearup\cache;

use block_gearup\di;
use block_gearup\local\http\api_client;
use block_gearup\local\http\api_exception;
use block_gearup\local\http\client_exception;
use context_system;

/**
 * Metadata cache.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class metadata implements \cache_data_source {

    /** @var static Cached instance. */
    protected static $instance = null;

    /** @var api_client The client. */
    protected $client;
    /** @var (null|false|mixed)[] Cache. */
    protected $cdcache;

    /**
     * Constructor.
     *
     * @param api_client $client The client.
     */
    public function __construct(api_client $client) {
        $this->client = $client;
    }

    /**
     * Convert data for key.
     *
     * @param string $key The key.
     * @param mixed $data The data.
     * @return mixed
     */
    protected function convert_data_for_key($key, $data) {
        if (strpos($key, 'use_') === 0 || strpos($key, 'is_') === 0) {
            return (int) (bool) $data; // Cache does not support false.
        } else if (strpos($key, 'max_') === 0) {
            return (int) $data;
        } else if (strpos($key, 'list_') === 0) {
            return !is_array($data) ? [] : $data;
        } else if (strpos($key, 'expiry') === 0) {
            return (int) $data;
        }
        return $data;
    }

    /**
     * Load for key.
     *
     * @param string|int $key The key to load.
     * @return mixed|false The data, or false when not found.
     */
    public function load_for_cache($key) {
        if ($key === 'achievement_badges') {
            return $this->fetch_data('/api/v1/quest/achievement-badges', []);
        } else if ($key === 'chatlib') {
            return $this->fetch_data('/api/v1/quest/libs/chat', (object) []);
        } else if ($key === 'quest_narrators') {
            return $this->fetch_data('/api/v1/quest/narrators', []);
        } else if ($key === 'voices') {
            return $this->fetch_data('/api/v1/quest/voices', []);
        }

        $data = $this->fetch_data('/api/v1/quest/licence', (object) [], true);
        $lookinto = $data;
        if (!in_array($key, ['expiry', 'expiry_cutoff', 'is_active', 'is_evaluation', 'package', 'product'])) {
            $lookinto = $data->specs ?? (object) [];
        }

        $keytolookfor = strpos($key, 'list_') === 0 ? substr($key, 5) : $key;
        return $this->convert_data_for_key($key, $lookinto->{$keytolookfor} ?? null);
    }

    /**
     * Load for many.
     *
     * @param array $keys Array of keys.
     * @return array Array of values.
     */
    public function load_many_for_cache(array $keys) {
        $results = [];
        foreach ($keys as $key) {
            $results[] = $this->load_for_cache($key);
        }
        return $results;
    }

    /**
     * Returns instance.
     *
     * @param \cache_definition $definition
     * @return self
     */
    public static function get_instance_for_cache(\cache_definition $definition) {
        if (!self::$instance || PHPUNIT_TEST) {
            self::$instance = new static(di::get('api_client'));
        }
        return self::$instance;
    }

    /**
     * Fetch the data.
     *
     * @param string $uri The URI to fetch.
     * @param mixed $defaultvalue The default value.
     * @param bool $attemptlocal Attempt to use local.
     * @return mixed
     */
    protected function fetch_data($uri, $defaultvalue = null, $attemptlocal = false) {
        $key = sha1($uri);

        if ($attemptlocal) {
            $data = $this->captured_data($key, true);
            if ($data !== null) {
                return $data;
            }
        }

        try {
            $resp = $this->client->get($uri);
            $data = $resp->data;
            if (is_array($defaultvalue)) {
                $data = (array) $data;
            } else if (is_object($defaultvalue)) {
                $data = (object) $data;
            }

            try {
                $this->capture_data($key, $data);
            } catch (\moodle_exception $e) { // @codingStandardsIgnoreLine
            }
        } catch (api_exception $e) {
            if ($e->get_http_code() >= 400 && $e->get_http_code() < 500) {
                $this->uncapture_data($key);
            } else {
                $data = $this->captured_data($key);
            }
        } catch (client_exception $e) {
            $data = $this->captured_data($key);
        }

        return $data ?? $defaultvalue;
    }

    /**
     * Capture data.
     *
     * @param string $key The key.
     * @param mixed $data The data to capture.
     */
    protected function capture_data($key, $data) {
        $fs = get_file_storage();
        $record = (object) [
            'contextid' => context_system::instance()->id,
            'component' => 'block_gearup',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'tmp' . $key,
            'mimetype' => 'application/json',
        ];

        // There could be a race condition here, we do not care as we capture the exception upstream.
        $tmpfile = $fs->create_file_from_string($record, json_encode($data));
        if ($file = $fs->get_file($record->contextid, $record->component, $record->filearea, $record->itemid, '/', $key)) {
            $file->replace_file_with($tmpfile);
            $tmpfile->delete();
        } else {
            $tmpfile->rename('/', $key);
            $file = $tmpfile;
        }
        $file->set_timemodified(time());

        $this->cdcache[$key] = $file;
    }

    /**
     * Captured data.
     *
     * @param string $key The key.
     * @param bool $shortlived Whether shortlived.
     * @return object|null
     */
    protected function captured_data($key, $shortlived = false) {
        $file = $this->get_file($key);
        $ts = time() - (1 << ($shortlived ? 19 : 21));
        if (!$file) {
            return null;
        } else if ($file->get_timemodified() < $ts) {
            if (!$shortlived) {
                $this->uncapture_data($key);
            }
            return null;
        }

        $content = json_decode($file->get_content(), false);
        return $content ?: null;
    }

    /**
     * Get file.
     *
     * @param string $key The key.
     * @return \stored_file|null
     */
    protected function get_file($key) {
        if (!isset($this->cdcache[$key])) {
            $fs = get_file_storage();
            $file = $fs->get_file(context_system::instance()->id, 'block_gearup', 'metadata', 0, '/', $key);
            $this->cdcache[$key] = $file;
        }
        return $this->cdcache[$key] ?? null;
    }

    /**
     * Uncapture data.
     *
     * @param string $key The key.
     */
    protected function uncapture_data($key) {
        $file = $this->get_file($key);
        if ($file) {
            $file->delete();
        }
        $this->cdcache[$key] = null;
    }

}
