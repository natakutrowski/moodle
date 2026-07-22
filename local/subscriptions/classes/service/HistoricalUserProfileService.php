<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\HistoricalUserProfileViewModel;
use local_subscriptions\crm\user\UserProfileLookupResult;
use local_subscriptions\crm\user\UserProfileNotFoundException;
use local_subscriptions\crm\user\UserProfileNoteService;
use local_subscriptions\crm\user\UserProfileRepository;
use local_subscriptions\crm\user\UserProfileTagService;
use local_subscriptions\crm\user\UserProfileTimelineBuilder;
use local_subscriptions\admin\Capabilities;

/**
 * Loads a read-only CRM profile for a deleted Moodle user.
 */
final class HistoricalUserProfileService {

    public function __construct(
        private readonly UserProfileRepository $repository
    ) {
    }

    /**
     * Creates the service with its default dependencies.
     */
    public static function create(): self {
        return new self(
            new UserProfileRepository()
        );
    }

    /**
     * Loads the historical profile of a deleted Moodle user.
     *
     * @throws UserProfileNotFoundException
     * @throws \coding_exception
     */
    public function load(
        int $userid
    ): HistoricalUserProfileViewModel {
        $lookup = $this->repository->resolve_user(
            $userid
        );

        if ($lookup->is_missing()) {
            throw new UserProfileNotFoundException(
                $userid,
                UserProfileLookupResult::STATUS_MISSING
            );
        }

        if ($lookup->is_active()) {
            throw new \coding_exception(
                'HistoricalUserProfileService can only load deleted users.'
            );
        }

        $deleteduser = $lookup->get_user();

        $noteservice = new UserProfileNoteService(
            $this->repository
        );

        $tagservice = new UserProfileTagService(
            $this->repository
        );

        $timelinebuilder =
            new UserProfileTimelineBuilder();

        $historicaluser = (object)[
            'id' => $userid,
            'email' => (string)(
                $deleteduser->email
                ?? ''
            ),
        ];

        $timelinepage =
            $timelinebuilder
                ->build_page_for_user(
                    $historicaluser,
                    20,
                    0,
                    Capabilities::can_view_inbox()
                );

        $timeline =
            $timelinebuilder
                ->to_legacy_objects(
                    $timelinepage->events
                );


        return new HistoricalUserProfileViewModel(
            $userid,
            $deleteduser,
            $this->repository->get_subscriptions(
                $userid
            ),
            $this->repository->get_historical_digital_payments(
                $userid,
                50
            ),
            $noteservice->get_for_profile(
                $userid,
                50
            ),
            $tagservice->get_for_profile(
                $userid
            ),
            $this->repository->count_historical_courses(
                $userid
            ),
            $this->repository->get_revenue_by_currency(
                $userid
            ),
            $this->repository->last_activity(
                $userid
            ),
            $timeline,
            $timelinepage->hasmore,
            $timelinepage->next_offset(),
        );
    }
}