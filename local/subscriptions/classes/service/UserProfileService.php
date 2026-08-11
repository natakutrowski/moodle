<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\crm\CommerceCustomerCrmAdapter;
use local_subscriptions\commerce\customer\readmodel\CommerceCustomerReadService;
use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;
use local_subscriptions\crm\user\UserProfileActionBuilder;
use local_subscriptions\crm\user\UserProfileLookupResult;
use local_subscriptions\crm\user\UserProfileNotFoundException;
use local_subscriptions\crm\user\UserProfileNoteService;
use local_subscriptions\crm\user\UserProfileRepository;
use local_subscriptions\crm\user\UserProfileTagService;
use local_subscriptions\crm\user\UserProfileTimelineBuilder;
use local_subscriptions\crm\user\UserProfileViewModel;
use local_subscriptions\crm\user\inbox\UserInboxRepository;
use local_subscriptions\crm\user\inbox\UserInboxService;

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
        $lookup = $this->repository->resolve_user($userid);

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
        $courses = $this->repository->get_accessible_courses((int)$user->id);
        $noteservice = new UserProfileNoteService($this->repository);
        $tagservice = new UserProfileTagService($this->repository);
        $actionbuilder = new UserProfileActionBuilder();

        global $DB;

        $snapshot = (new CommerceCustomerReadService($DB))->build(
            (int)$user->id,
            (string)$user->email
        );
        $adapter = new CommerceCustomerCrmAdapter();
        $commercepurchases = $adapter->purchase_rows($snapshot);

        // Keep Legacy arrays as fallback/adapters during the 7.95 transition.
        $subscriptions = $this->repository->get_subscriptions((int)$user->id);
        $digitalpayments = $this->repository->get_digital_payments(
            (int)$user->id,
            (string)$user->email
        );

        $timelinebuilder = new UserProfileTimelineBuilder();
        $timelinepage = $timelinebuilder->build_page_for_user(
            $user,
            20,
            0,
            $canviewinbox,
            $snapshot
        );
        $timeline = $timelinebuilder->to_legacy_objects($timelinepage->events);

        $legacystatus = $this->legacy_crm_status((int)$user->id);
        $stats = $adapter->stats(
            $snapshot,
            $this->repository->count_accessible_courses((int)$user->id),
            $this->repository->last_activity((int)$user->id),
            !empty($user->suspended),
            $legacystatus
        );

        $intelligence = (new UserIntelligenceBuilder())->build_for_user($user);

        $inbox = null;
        if ($canviewinbox) {
            $inbox = (new UserInboxService(new UserInboxRepository()))
                ->get_for_user((int)$user->id, 5);
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
            $inbox,
            $commercepurchases,
            $snapshot->to_array()
        );
    }

    private function legacy_crm_status(int $userid): string {
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
