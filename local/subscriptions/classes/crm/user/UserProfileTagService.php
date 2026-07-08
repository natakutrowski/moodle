<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

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

    public function add(int $userid, string $tag, int $createdby): void {
        $tag = clean_param($tag, PARAM_ALPHAEXT);

        if (!in_array($tag, UserProfileTag::allowed_tags(), true)) {
            throw new \moodle_exception('crm_invalid_tag', 'local_subscriptions');
        }

        $this->repository->add_tag($userid, $tag, $createdby);
    }

    public function remove(int $userid, string $tag): void {
        $tag = clean_param($tag, PARAM_ALPHAEXT);

        if (!in_array($tag, UserProfileTag::allowed_tags(), true)) {
            return;
        }

        $this->repository->remove_tag($userid, $tag);
    }
}