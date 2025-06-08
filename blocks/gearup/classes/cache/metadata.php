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
    /** @var null|false|mixed Cache. */
    protected $cdcache;
    /** @var null|mixed Cache. */
    protected $cdcachecontent;

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
            try {
                $resp = $this->client->get('/api/v1/quest/achievement-badges');
                return (array) $resp->data;
            } catch (client_exception $e) {
                return [];
            }
        } else if ($key === 'quest_narrators') {
            try {
                $resp = $this->client->get('/api/v1/quest/narrators');
                return (array) $resp->data;
            } catch (client_exception $e) {
                return [];
            }
        }

        $data = null;
        try {
            $data = $this->captured_data();
        } catch (\moodle_exception $e) {
            $data = null;
        }

        try {
            if ($data === null) {
                $resp = $this->client->get('/api/v1/quest/licence');
                $data = $resp->data;
                try {
                    $this->capture_data($data);
                } catch (\moodle_exception $e) { // @codingStandardsIgnoreLine
                }
            }
        } catch (api_exception $e) {
            if ($e->get_http_code() >= 400 && $e->get_http_code() < 500) {
                $this->uncapture_data();
            } else {
                $data = $this->captured_data(true);
            }
        } catch (client_exception $e) {
            $data = $this->captured_data(true);
        }

        $data = $data ?? (object) [];
        $lookinto = $data;
        if (!in_array($key,  ['expiry', 'expiry_cutoff', 'is_active', 'is_evaluation', 'package', 'product'])) {
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
        if (!self::$instance) {
            self::$instance = new static(di::get('api_client'));
        }
        return self::$instance;
    }

    /**
     * Capture data.
     *
     * @param mixed $data The data to capture.
     */
    protected function capture_data($data) {
        $this->cdcache = null;
        $this->cdcachecontent = null;

        $fs = get_file_storage();
        $record = (object) [
            'contextid' => context_system::instance()->id,
            'component' => 'block_gearup',
            'filearea' => 'metadata',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'tmp',
            'mimetype' => 'application/json',
        ];
        $tmpfile = $fs->create_file_from_string($record, json_encode($data));
        if ($file = $fs->get_file($record->contextid, $record->component, $record->filearea, $record->itemid, '/', 'file')) {
            $file->replace_file_with($tmpfile);
            $tmpfile->delete();
        } else {
            $tmpfile->rename('/', 'file');
            $file = $tmpfile;
        }
        $file->set_timemodified(time());

        $this->cdcache = $file;
        $this->cdcachecontent = $data;
    }

    /**
     * Captured data.
     *
     * @param bool $fallback Fallback.
     * @return object|null
     */
    protected function captured_data($fallback = false) {
        if ($this->cdcache === null) {
            $fs = get_file_storage();
            $file = $fs->get_file(context_system::instance()->id, 'block_gearup', 'metadata', 0, '/', 'file');
            $this->cdcache = $file;
            $this->cdcachecontent = $file ? json_decode($file->get_content(), false) : null;
        }

        $ts = time() - (1 << ($fallback ? 21 : 19));
        if (!$this->cdcache || $this->cdcache->get_timemodified() < $ts) {
            return null;
        }

        return $this->cdcachecontent ?: null;
    }

    /**
     * Uncapture data.
     */
    protected function uncapture_data() {
        $fs = get_file_storage();
        $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');
    }

}
