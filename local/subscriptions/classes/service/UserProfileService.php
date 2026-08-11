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
use local_subscriptions\crm\user\UserProfileStats;
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

    /**
     * Loads a CRM User360 profile from a customer email. If a Moodle account
     * exists, the canonical Moodle-backed profile is returned. Otherwise a
     * Commerce-only profile is built from Legacy digital purchases.
     */
    public static function load_by_email(string $email): \stdClass {
        $service = new self(new UserProfileRepository());
        return $service->load_view_model_by_email($email)->to_legacy_object();
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

    public function load_view_model_by_email(string $email): UserProfileViewModel {
        global $DB;

        $email = trim(\core_text::strtolower($email));
        if ($email === '' || !validate_email($email)) {
            throw new UserProfileNotFoundException(0, 'missing');
        }

        $moodleuser = $this->repository->get_user_by_email($email);
        if ($moodleuser !== null) {
            return $this->load_view_model((int)$moodleuser->id);
        }

        $digitalpayments = $this->repository->get_digital_payments_by_email($email, 100);
        if ($digitalpayments === []) {
            throw new UserProfileNotFoundException(0, 'missing');
        }

        $firstname = '';
        $lastname = '';
        $firstpurchase = 0;
        $lastactivity = 0;
        foreach ($digitalpayments as $purchase) {
            if ($firstname === '' && trim((string)($purchase->firstname ?? '')) !== '') {
                $firstname = trim((string)$purchase->firstname);
            }
            if ($lastname === '' && trim((string)($purchase->lastname ?? '')) !== '') {
                $lastname = trim((string)$purchase->lastname);
            }
            $created = (int)($purchase->creation_date ?? 0);
            $updated = (int)($purchase->last_update ?? 0);
            if ($created > 0 && ($firstpurchase === 0 || $created < $firstpurchase)) {
                $firstpurchase = $created;
            }
            $lastactivity = max($lastactivity, $created, $updated, (int)($purchase->payment_date ?? 0));
        }

        // Synthetic presentation object only: this record is never persisted in {user}.
        $user = (object)[
            'id' => 0,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'country' => '',
            'timecreated' => $firstpurchase,
            'lastaccess' => 0,
            'suspended' => 0,
            'deleted' => 0,
        ];

        $snapshot = (new CommerceCustomerReadService($DB))->build_for_email($email);
        $adapter = new CommerceCustomerCrmAdapter();
        $commercepurchases = $adapter->purchase_rows($snapshot);

        if ($snapshot->has_purchases()) {
            $stats = $adapter->stats($snapshot, 0, $lastactivity, false, null);
        } else {
            $stats = $this->legacy_digital_guest_stats($digitalpayments, $lastactivity);
        }

        $timelinebuilder = new UserProfileTimelineBuilder();
        $timelinepage = $timelinebuilder->build_page_for_user(
            $user,
            100,
            0,
            false,
            $snapshot
        );

        $actions = [];
        $canmanagedigital = has_capability(
            Capabilities::MANAGE_DIGITAL,
            \context_system::instance()
        );
        foreach ($digitalpayments as $purchase) {
            if (!$canmanagedigital) {
                break;
            }
            $status = strtoupper(trim((string)($purchase->status ?? '')));
            if (empty($purchase->id) || !in_array($status, ['PAID', 'COMPLETED'], true)) {
                continue;
            }
            $actions[] = (object)[
                'key' => 'purchase_resend_' . (int)$purchase->id,
                'label' => get_string('command_action_purchase_resend_email', 'local_subscriptions') . ' #' . (int)$purchase->id,
                'url' => (new \moodle_url(
                    \local_subscriptions\subscription_config::digital_purchase_resend_email_admin_page(),
                    [
                        'id' => (int)$purchase->id,
                        'sesskey' => sesskey(),
                        'returnurl' => (new \moodle_url(
                            \local_subscriptions\subscription_config::admin_user_view_page(),
                            ['email' => $email]
                        ))->out_as_local_url(false),
                    ]
                ))->out(false),
                'icon' => 'email',
                'style' => 'secondary',
                'danger' => false,
            ];
            break;
        }

        $model = new UserProfileViewModel(
            $user,
            [],
            $digitalpayments,
            $stats,
            [],
            $timelinebuilder->to_legacy_objects($timelinepage->events),
            [],
            false,
            0,
            [],
            $actions,
            null,
            null,
            $commercepurchases,
            $snapshot->to_array(),
            true
        );

        return $model;
    }

    /**
     * Builds guest statistics directly from Legacy digital purchases when no
     * Native Commerce shadow/purchase exists yet.
     */
    private function legacy_digital_guest_stats(array $digitalpayments, int $lastactivity): UserProfileStats {
        $paidcount = 0;
        $spenteur = 0.0;
        $spentrub = 0.0;

        foreach ($digitalpayments as $purchase) {
            $status = strtoupper(trim((string)($purchase->status ?? '')));
            if (!in_array($status, ['PAID', 'COMPLETED'], true)) {
                continue;
            }
            $paidcount++;
            $currency = strtoupper(trim((string)($purchase->currency ?? '')));
            $amount = (float)($purchase->price ?? 0);
            if ($currency === 'EUR') {
                $spenteur += $amount;
            } else if ($currency === 'RUB') {
                $spentrub += $amount;
            }
        }

        return new UserProfileStats(
            $paidcount > 0 ? 'active_customer' : 'former_customer',
            0,
            count($digitalpayments),
            0,
            $spenteur,
            $spentrub,
            $lastactivity,
            count($digitalpayments),
            $paidcount,
            0,
            0,
            0,
            0,
            true
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
