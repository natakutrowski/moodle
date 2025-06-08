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
 * Frequency evaluator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\time;

use coding_exception;
use DateInterval;
use DateTimeImmutable;

/**
 * Frequency evaluator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frequency_evaluator {

    /** No evaluation. */
    const MODE_NONE = 0;
    /** Different day. */
    const MODE_DAY = 1;
    /** Different week. */
    const MODE_WEEK = 2;
    /** Consecutive day. */
    const MODE_CONSEC_DAY = 10;
    /** Consecutive week day. */
    const MODE_CONSEC_WEEKDAY = 11;
    /** Consecutive week. */
    const MODE_CONSEC_WEEK = 12;

    /** @var int */
    protected $mode;
    /** @var DateTimeImmutable */
    protected $lasthit;
    /** @var DateTimeImmutable */
    protected $now;

    /** @var bool */
    protected $isevaluated = false;

    /** @var bool */
    protected $isearly = false;
    /** @var bool */
    protected $islate = false;
    /** @var bool */
    protected $isvalid = false;
    /** @var DateTimeImmutable|null */
    protected $dormantuntil = null;
    /** @var DateTimeImmutable|null */
    protected $stalefrom = null;

    /**
     * Constructor.
     *
     * @param int $mode The mode constant.
     * @param DateTimeImmutable $lasthit The last hit time.
     * @param DateTimeImmutable|null $now The current time.
     */
    public function __construct(int $mode, DateTimeImmutable $lasthit, ?DateTimeImmutable $now = null) {
        $this->mode = $mode;
        $this->lasthit = $lasthit;
        $this->now = ($now ?? new DateTimeImmutable('now'))->setTimezone($lasthit->getTimezone());

        if ($this->lasthit->getTimezone()->getName() !== $this->now->getTimezone()->getName()) {
            throw new coding_exception('Timezones must match');
        }
    }

    /**
     * Get the time until we can set this as dormant.
     *
     * @return DateTimeImmutable|null
     */
    public function get_dormant_until(): ?DateTimeImmutable {
        $this->evaluate();
        return $this->dormantuntil;
    }

    /**
     * Get the time from which this will be stale.
     *
     * @return DateTimeImmutable|null
     */
    public function get_stale_from(): ?DateTimeImmutable {
        $this->evaluate();
        return $this->stalefrom;
    }

    /**
     * Whether now is too early.
     *
     * @return bool
     */
    public function is_early(): bool {
        $this->evaluate();
        return $this->isearly;
    }

    /**
     * Whether now is too late.
     *
     * @return bool
     */
    public function is_late(): bool {
        $this->evaluate();
        return $this->islate;
    }

    /**
     * Whether now is within the parameters.
     *
     * @return bool
     */
    public function is_valid(): bool {
        $this->evaluate();
        return $this->isvalid;
    }

    /**
     * Evaluate the variables.
     */
    protected function evaluate() {
        if (!$this->isevaluated) {
            $this->isevaluated = true;
            $this->perform_evaluation();
        }
    }

    /**
     * Perform the evaluation.
     */
    protected function perform_evaluation() {
        $mode = $this->mode;
        $lasttime = $this->lasthit;
        $now = $this->now;

        $validfrom = null;
        $validbefore = null;

        if ($mode == static::MODE_NONE || $lasttime->getTimestamp() === 0) {
            // We accept all times, or there was never a previous time.
            $validfrom = $now;

        } else if ($lasttime > $now->setTime(0, 0)) {
            // The previous time was today, we know that we require at least 1 day interval
            // we can set an impossible date that will never be matched.
            $validfrom = $now->add(new DateInterval('P1Y'));

        } else {
            list($validfrom, $validbefore) = $this->get_time_gate($mode, $lasttime);
        }

        if (!$validfrom) {
            $validfrom = $now;  // Accept all.
        }

        $ismatchingfrom = $now >= $validfrom;
        $ismatchingbefore = !$validbefore || $now < $validbefore;

        $this->isearly = !$ismatchingfrom;
        $this->isvalid = $ismatchingfrom && $ismatchingbefore;
        $this->islate = !$ismatchingbefore;

        // Compute the future time gate.
        list($dormantuntil, $stalefrom) = $this->get_time_gate($mode, $now);
        $this->dormantuntil = $dormantuntil;
        $this->stalefrom = $stalefrom;
    }

    /**
     * Get time gate.
     *
     * The time game is the time between which the next event must
     * occurred in order to meet the mode in which we are. For instance,
     * if the mode is 'daily', then passing July 15, 13:05 will return
     * from [July 16, 0:00, July 17, 0:00].
     *
     * You must set the relevant time zone to the time!
     *
     * @param int $mode The mode constant.
     * @param DateTimeImmutable $now The time representing now.
     * @return array First is the value valid from (>= $validfrom), second
     *               is the value valid before (< $validbefore).
     */
    protected function get_time_gate($mode, DateTimeImmutable $now) {
        $validfrom = null;
        $validbefore = null;

        if ($mode == static::MODE_DAY || $mode == static::MODE_CONSEC_DAY) {
            $validfrom = $now->add(new DateInterval('P1D'))->setTime(0, 0);
            if ($mode == static::MODE_CONSEC_DAY) {
                $validbefore = $validfrom->add(new DateInterval('P1D'));
            }

        } else if ($mode == static::MODE_WEEK || $mode == static::MODE_CONSEC_WEEK) {
            $validfrom = $now->modify('monday next week')->setTime(0, 0);
            if ($mode == static::MODE_CONSEC_WEEK) {
                $validbefore = $validfrom->add(new DateInterval('P1W'));
            }

        } else if ($mode == static::MODE_CONSEC_WEEKDAY) {
            $validfrom = $now->modify('next weekday')->setTime(0, 0);
            $validbefore = $validfrom->add(new DateInterval('P1D'));
        }

        return [$validfrom, $validbefore];
    }

    /**
     * Get the form options.
     *
     * @return array
     */
    public static function get_form_options() {
        return [
            static::MODE_NONE => get_string('anytime', 'block_gearup'),
            static::MODE_DAY => get_string('fordifferentdays', 'block_gearup'),
            static::MODE_WEEK => get_string('fordifferentweeks', 'block_gearup'),
            static::MODE_CONSEC_DAY => get_string('forconsecdays', 'block_gearup'),
            static::MODE_CONSEC_WEEKDAY => get_string('forconsecweekdays', 'block_gearup'),
            static::MODE_CONSEC_WEEK => get_string('forconsecweeks', 'block_gearup'),
        ];
    }

}
