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
 * Observer.
 *
 * @module     block_gearup/observer
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';
import Ajax from 'core/ajax';
import * as Video from 'block_gearup/video';

/**
 * Init for page.
 *
 * @param {String} propsId
 */
export function initForPage(propsId) {
    const props = JSON.parse(document.getElementById(propsId).textContent);
    const types = props.types || [];
    if (types.includes('watch_video') || types.includes('watch_time')) {
        startVideoObserver(props);
    }
}

/**
 * Start video observer.
 *
 * @param {Object} props The props.
 */
async function startVideoObserver(props) {
    const config = props.config || {};
    const videoJsConfig = config.media_videojs || {};

    const videoCssClass = videoJsConfig.videocssclass;
    if (!videoCssClass) {
        return;
    }

    let onWatched;
    if (props.types.includes('watch_video')) {
        onWatched = (data) => {
            Ajax.call([{
                methodname: 'block_gearup_video_watched',
                args: {
                    contextid: props.contextid,
                    videoid: data.src,
                }
            }])[0].then(() => {
                return;
            }).catch(() => {
                return;
            });
        };
    }

    let onTimeWatched;
    if (props.types.includes('watch_time')) {
        onTimeWatched = (data) => {
            Ajax.call([{
                methodname: 'block_gearup_time_watched',
                args: {
                    contextid: props.contextid,
                    sourceid: data.src,
                    duration: data.time,
                }
            }])[0].then(() => {
                return;
            }).catch(() => {
                return;
            });
        };
    }

    try {
        const vids = await Video.findVideoNodes({videoJsClass: videoCssClass});
        vids.forEach((v) => {
            Video.observeVideo(v, {
                onWatched,
                onTimeWatched
            });
        });
    } catch (e) {
        log.error(e);
    }
}

