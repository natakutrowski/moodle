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
 * Usage reporter.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\plugin;

use block_gearup\local\http\api_client;
use block_gearup\local\http\client_exception;

/**
 * Usage reporter.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usage_reporter {

    /** @var api_client The API client. */
    protected $client;
    /** @var usage_report_maker The maker. */
    protected $maker;

    /**
     * Constructor.
     *
     * @param usage_report_maker $maker The usage report maker.
     * @param api_client $client The API client.
     */
    public function __construct(usage_report_maker $maker, api_client $client) {
        $this->maker = $maker;
        $this->client = $client;
    }

    /**
     * Make usage report.
     *
     * @return object Where keys represent usage.
     * @return bool Whether successful or not.
     */
    public function report() {
        $usage = $this->maker->make();

        $localsiteid = get_config('block_gearup', 'usagereportid');
        if (!empty($localsiteid)) {
            $usage->local_site_id = $localsiteid;
        }

        try {
            $resp = $this->client->post('/api/v1/quest/usage', $usage);

        } catch (client_exception $e) {
            debugging('Error while reporting usage: ' . $e->getMessage());
            return false;
        }

        set_config('lastusagereport', time(), 'block_gearup');
        if ($resp->data && !empty($resp->data->local_site_id) && $resp->data->local_site_id !== $localsiteid) {
            set_config('usagereportid', $resp->data->local_site_id, 'block_gearup');
        }

        return true;
    }

}
