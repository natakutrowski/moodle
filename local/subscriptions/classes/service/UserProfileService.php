<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\UserProfileRepository;
use local_subscriptions\crm\user\UserProfileStats;
use local_subscriptions\crm\user\UserProfileViewModel;
use local_subscriptions\crm\user\UserProfileActionBuilder;
use local_subscriptions\crm\user\UserProfileTimelineBuilder;
use local_subscriptions\crm\user\UserProfileNoteService;
use local_subscriptions\crm\user\UserProfileTagService;
use local_subscriptions\crm\user\UserProfileLookupResult;
use local_subscriptions\crm\user\UserProfileNotFoundException;
use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;
use local_subscriptions\crm\user\inbox\UserInboxRepository;
use local_subscriptions\crm\user\inbox\UserInboxService;
use local_subscriptions\admin\Capabilities;

final class UserProfileService {

    public static function load(int $userid): \stdClass {
        $service = new self(new UserProfileRepository());

        return $service->load_view_model($userid)->to_legacy_object();
    }

    public function __construct(
        private readonly UserProfileRepository $repository
    ) {
    }

    public function load_view_model(int $userid): UserProfileViewModel {
        $lookup = $this->repository->resolve_user(
            $userid
        );

        if (!$lookup->is_active()) {
            throw new UserProfileNotFoundException(
                $lookup->get_userid(),
                $lookup->get_status()
            );
        }

        $user = $lookup->get_user();

        if ($user === null) {
            throw new \coding_exception(
                'An active UserProfile lookup must contain a Moodle user record.'
            );
        }

        $canviewinbox = Capabilities::can_view_inbox();

        $subscriptions = $this->repository->get_subscriptions((int)$user->id);
        $digitalpayments = $this->repository->get_digital_payments((int)$user->id, (string)$user->email);
        $courses = $this->repository->get_accessible_courses((int)$user->id);
        $noteservice = new UserProfileNoteService($this->repository);
        $tagservice = new UserProfileTagService($this->repository);

        $timelinebuilder = new UserProfileTimelineBuilder();
        $actionbuilder = new UserProfileActionBuilder();

        $timelinepage =
            $timelinebuilder
                ->build_page_for_user(
                    $user,
                    20,
                    0,
                    $canviewinbox
                );

        $timeline =
            $timelinebuilder
                ->to_legacy_objects(
                    $timelinepage->events
                );

        $stats = new UserProfileStats(
            $this->crm_status((int)$user->id, !empty($user->suspended)),
            count($subscriptions),
            count($digitalpayments),
            $this->repository->count_accessible_courses((int)$user->id),
            $this->repository->sum_spent_by_currency((int)$user->id, 'EUR'),
            $this->repository->sum_spent_by_currency((int)$user->id, 'RUB'),
            $this->repository->last_activity((int)$user->id)
        );

        $intelligence = (new UserIntelligenceBuilder())->build_for_user($user);

        $inbox = null;

        if ($canviewinbox) {
            $inboxservice = new UserInboxService(
                new UserInboxRepository()
            );

            $inbox = $inboxservice->get_for_user(
                (int)$user->id,
                5
            );
        }

        return new UserProfileViewModel(
            $user,
            $subscriptions,
            $digitalpayments,
            $stats,
            $noteservice->get_for_profile($userid, 20),
            $timeline,
            $courses,
            $timelinepage->hasmore,
            $timelinepage->next_offset(),
            $tagservice->get_for_profile($userid),
            array_map(
                static fn($action): \stdClass => $action->to_object(),
                $actionbuilder->build_for_profile($user, $digitalpayments)
            ),
            $intelligence,
            $inbox
        );
    }

    private function crm_status(int $userid, bool $usersuspended): string {
        if ($usersuspended) {
            return 'suspended';
        }

        if ($this->repository->has_subscription_status($userid, 'active')) {
            return 'active_customer';
        }

        if ($this->repository->has_subscription_status($userid, 'trial')) {
            return 'trial';
        }

        if ($this->repository->has_past_subscription($userid)) {
            return 'former_customer';
        }

        return 'lead';
    }
}