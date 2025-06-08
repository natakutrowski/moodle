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
 * Video.
 *
 * @module     block_gearup/video
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';

/**
 * Get the videos on page.
 *
 * @param {String} videoJsClass The video.js CSS class.
 * @return {Array}
 */
function getVideoNodesOnPage(videoJsClass) {
    const nodes = document.getElementsByClassName(videoJsClass);
    return Array.prototype.slice.call(nodes).map(n => ({
        type: 'videojs',
        ready: Boolean(n.player),
        node: n
    }));
}

/**
 * The thing.
 *
 * @param {Object} options The options.
 * @return {Array}
 */
export async function findVideoNodes(options) {
    const startedAt = new Date();

    const fn = async() => {
        const vids = getVideoNodesOnPage(options.videoJsClass || 'video-js');
        const elapsed = new Date() - startedAt;
        const canTryAgain = elapsed < 5000;

        if (canTryAgain && vids.some(v => !v.ready)) {
            log.debug(`Some videos aren't ready. (elapsed: ${elapsed}ms)`);
            await new Promise(resolve => setTimeout(resolve, 250));
            return fn();
        }

        const readyVids = vids
            .filter(v => v.ready)
            .filter(v => v.type === 'videojs' && v.node.player.currentType().startsWith('video/'));
        log.debug(`Identified ${readyVids.length} video(s) in ${elapsed}ms.`);
        return readyVids;
    };

    return await fn();
}

/**
 * @param {Object} v The video.
 * @param {Object} options The options.
 */
export function observeVideo(v, options) {
    if (v.type !== 'videojs' || !v.ready) {
        return;
    }

    const player = v.node.player;
    var totalWatchTime = 0;

    var watchedTriggered = false;
    const watchedAtRatio = options.watchedAtRatio || 0.85;
    const onWatchedCallback = options.onWatched || (() => null);

    var pauseTimeWatchedBuffering = false;
    var timeWatchedBuffer = 0;
    var timeWatchedLastTime = 0;
    const timeWatchedBufferSize = 5;
    const onTimeWatchedCallback = options.onTimeWatched || (() => null);

    var seekedLandedTimeout = null;

    const play = () => {
        var totalDuration = player.duration();
        const targetWatchedDuration = Math.min(totalDuration * 0.5, 20); // 20s or 50% of the video.

        const pauseListener = () => {
            player.off('timeupdate', timeupdate);
            player.off('pause', ended);
            player.off('ended', ended);
            reportTimeWatched();
        };

        const reportTimeWatched = () => {
            if (timeWatchedBuffer < 1) {
                return;
            }
            const flat = Math.floor(timeWatchedBuffer);
            onTimeWatchedCallback({time: flat, src: player.src()});
            timeWatchedBuffer -= flat;
        };

        const timeupdate = () => {
            const now = player.currentTime();
            const ratio = now / totalDuration;

            if (!watchedTriggered) {
                const hasReachedAcceptableRatio = ratio >= watchedAtRatio;
                const hasWatchedEnough = totalWatchTime >= targetWatchedDuration;
                if (hasReachedAcceptableRatio && hasWatchedEnough) {
                    watchedTriggered = true;
                    onWatchedCallback({ratio: ratio, src: player.src()});
                }
            }

            if (!pauseTimeWatchedBuffering && timeWatchedLastTime <= now) {
                const additionalWatchTime = now - timeWatchedLastTime;
                totalWatchTime += additionalWatchTime;
                timeWatchedBuffer += additionalWatchTime;
                timeWatchedLastTime = now;
                if (timeWatchedBuffer > timeWatchedBufferSize) {
                    reportTimeWatched();
                }
            }
        };

        const ended = () => {
            pauseListener();
        };

        const pause = () => {
            pauseListener();
        };

        const seeking = () => {
            clearTimeout(seekedLandedTimeout);
            pauseTimeWatchedBuffering = true;
        };

        const seeked = () => {
            clearTimeout(seekedLandedTimeout);
            seekedLandedTimeout = setTimeout(() => {
                pauseTimeWatchedBuffering = false;
                timeWatchedLastTime = player.currentTime();
            }, 150);
        };

        player.on('seeking', seeking);
        player.on('seeked', seeked);
        player.on('timeupdate', timeupdate);
        player.on('pause', pause);
        player.on('ended', ended);
    };

    player.on('play', play);
}