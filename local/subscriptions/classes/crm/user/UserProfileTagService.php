<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\automation\AutomationDispatcher;
use local_subscriptions\crm\automation\AutomationTriggerKeys;

final class UserProfileTagService {

    public function __construct(
        private readonly UserProfileRepository $repository
    ) {
    }

    public function get_for_profile(int $userid): array {
        return array_map(
            static fn(\stdClass $record): \stdClass => UserProfileTag::from_record($record)->to_object(),
            $this->repository->get_tags_for_profile($userid)
        );
    }

    public function add(int $userid, string $tag, int $createdby, bool $dispatchautomation = true): void {
        $tag = clean_param($tag, PARAM_ALPHAEXT);

        if (!in_array($tag, UserProfileTag::allowed_tags(), true)) {
            throw new \moodle_exception('crm_invalid_tag', 'local_subscriptions');
        }

        foreach ($this->get_for_profile($userid) as $current) {
            if ($current->tag === $tag) {
                return;
            }
        }

        $this->repository->add_tag($userid, $tag, $createdby);

        if ($dispatchautomation) {
            (new AutomationDispatcher())->dispatch(
                AutomationContext::for_user_action(
                    AutomationTriggerKeys::TAG_ADDED,
                    $userid,
                    $createdby,
                    [
                        'tag' => $tag,
                        'source' => 'crm_tag_service',
                    ]
                )
            );
        }
    }

    public function remove(int $userid, string $tag, bool $dispatchautomation = true): void {
        $tag = clean_param($tag, PARAM_ALPHAEXT);

        if (!in_array($tag, UserProfileTag::allowed_tags(), true)) {
            return;
        }

        $exists = false;

        foreach ($this->get_for_profile($userid) as $current) {
            if ($current->tag === $tag) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            return;
        }

        $this->repository->remove_tag($userid, $tag);

        if ($dispatchautomation) {
            (new AutomationDispatcher())->dispatch(
                AutomationContext::for_user_action(
                    AutomationTriggerKeys::TAG_REMOVED,
                    $userid,
                    0,
                    [
                        'tag' => $tag,
                        'source' => 'crm_tag_service',
                    ]
                )
            );
        }
    }
}