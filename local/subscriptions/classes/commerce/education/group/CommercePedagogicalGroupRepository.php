<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;

final class CommercePedagogicalGroupRepository {
    private const CONFIG_TABLE = 'local_subs_commerce_ped_gcfg';
    private const GROUP_TABLE = 'local_subs_commerce_ped_group';
    private const MEMBER_TABLE = 'local_subs_commerce_ped_gmem';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function get_configuration(int $promotionid): CommercePedagogicalGroupConfiguration {
        $record = $this->db->get_record(self::CONFIG_TABLE, ['promotionid' => $promotionid], '*', IGNORE_MISSING);
        if (!$record) {
            return new CommercePedagogicalGroupConfiguration(
                $promotionid, false, 6, null, null, 0, 0
            );
        }
        return new CommercePedagogicalGroupConfiguration(
            (int)$record->promotionid,
            (bool)$record->enabled,
            (int)$record->groupsize,
            $record->createdby !== null ? (int)$record->createdby : null,
            $record->modifiedby !== null ? (int)$record->modifiedby : null,
            (int)$record->timecreated,
            (int)$record->timemodified,
            isset($record->tutorname) && $record->tutorname !== null
                ? (string)$record->tutorname
                : null
        );
    }

    public function save_configuration(CommercePedagogicalGroupConfiguration $configuration): void {
        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($configuration->get_promotion_id());
        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion for group configuration.');
        }

        $existing = $this->db->get_record(
            self::CONFIG_TABLE,
            ['promotionid' => $configuration->get_promotion_id()],
            'id,timecreated,createdby',
            IGNORE_MISSING
        );
        $record = (object)[
            'promotionid' => $configuration->get_promotion_id(),
            'enabled' => $configuration->is_enabled() ? 1 : 0,
            'groupsize' => $configuration->get_group_size(),
            'createdby' => $configuration->get_created_by(),
            'modifiedby' => $configuration->get_modified_by(),
            'timecreated' => $configuration->get_time_created(),
            'timemodified' => $configuration->get_time_modified(),
        ];
        if ($existing) {
            $record->id = (int)$existing->id;
            $record->timecreated = (int)$existing->timecreated;
            $record->createdby = $existing->createdby !== null ? (int)$existing->createdby : null;
            $this->db->update_record(self::CONFIG_TABLE, $record);
        } else {
            $this->db->insert_record(self::CONFIG_TABLE, $record);
        }
    }

    /** @return CommercePedagogicalGroup[] */
    public function groups_for_promotion(int $promotionid, bool $activeonly = false): array {
        $conditions = ['promotionid' => $promotionid];
        if ($activeonly) {
            $conditions['active'] = 1;
        }
        $records = $this->db->get_records(
            self::GROUP_TABLE,
            $conditions,
            'position ASC, id ASC'
        );
        return array_values(array_map(fn(\stdClass $r) => $this->hydrate_group($r), $records));
    }

    /** @return CommercePedagogicalGroup[] */
    public function groups_for_product(
        int $promotionid,
        int $productid,
        bool $activeonly = false
    ): array {
        $conditions = [
            'promotionid' => $promotionid,
            'productid' => $productid,
        ];
        if ($activeonly) {
            $conditions['active'] = 1;
        }

        $records = $this->db->get_records(
            self::GROUP_TABLE,
            $conditions,
            'position ASC, id ASC'
        );

        return array_values(array_map(
            fn(\stdClass $record): CommercePedagogicalGroup =>
                $this->hydrate_group($record),
            $records
        ));
    }

    public function member_user_ids(int $groupid): array {
        $records = $this->db->get_records(
            self::MEMBER_TABLE,
            [
                'groupid' => $groupid,
                'active' => 1,
            ],
            'id ASC',
            'id,userid'
        );

        return array_map(
            static fn(\stdClass $record): int => (int)$record->userid,
            array_values($records)
        );
    }

    public function get_group(int $groupid): ?CommercePedagogicalGroup {
        $record = $this->db->get_record(self::GROUP_TABLE, ['id' => $groupid], '*', IGNORE_MISSING);
        return $record ? $this->hydrate_group($record) : null;
    }

    public function save_group(CommercePedagogicalGroup $group): CommercePedagogicalGroup {
        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($group->get_promotion_id());
        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion for group.');
        }

        $moodlegroup = $this->db->get_record(
            'groups',
            ['id' => $group->get_moodle_group_id()],
            'id,courseid',
            MUST_EXIST
        );
        if ((int)$moodlegroup->courseid !== $promotion->get_course_id()) {
            throw new \coding_exception('Moodle group must belong to the promotion course.');
        }

        $record = (object)[
            'promotionid' => $group->get_promotion_id(),
            'moodlegroupid' => $group->get_moodle_group_id(),
            'productid' => $group->get_product_id(),
            'displayname' => $group->get_display_name(),
            'position' => $group->get_position(),
            'tutorid' => $group->get_tutor_id(),
            'tutorname' => $group->get_tutor_name(),
            'supportlang' => $group->get_support_language(),
            'telegramref' => $group->get_telegram_reference(),
            'levelupxp' => $group->get_levelup_xp(),
            'active' => $group->is_active() ? 1 : 0,
            'createdby' => $group->get_created_by(),
            'modifiedby' => $group->get_modified_by(),
            'timecreated' => $group->get_time_created(),
            'timemodified' => $group->get_time_modified(),
        ];

        if ($group->get_id() === null) {
            $record->id = $this->db->insert_record(self::GROUP_TABLE, $record);
        } else {
            $record->id = $group->get_id();
            $this->db->update_record(self::GROUP_TABLE, $record);
        }
        return $this->get_group((int)$record->id)
            ?? throw new \coding_exception('Pedagogical group could not be reloaded.');
    }

    public function member_count(int $groupid): int {
        return $this->db->count_records(self::MEMBER_TABLE, ['groupid' => $groupid, 'active' => 1]);
    }

    public function group_for_user(int $promotionid, int $userid): ?CommercePedagogicalGroup {
        $sql = "SELECT g.*
                  FROM {" . self::GROUP_TABLE . "} g
                  JOIN {" . self::MEMBER_TABLE . "} m ON m.groupid = g.id
                 WHERE g.promotionid = :promotionid
                   AND g.active = 1
                   AND m.userid = :userid
                   AND m.active = 1";
        $record = $this->db->get_record_sql($sql, [
            'promotionid' => $promotionid,
            'userid' => $userid,
        ], IGNORE_MISSING);
        return $record ? $this->hydrate_group($record) : null;
    }

    public function save_membership(int $groupid, int $userid, ?int $createdby, int $now): void {
        $existing = $this->db->get_record(
            self::MEMBER_TABLE,
            ['groupid' => $groupid, 'userid' => $userid],
            '*',
            IGNORE_MISSING
        );
        if ($existing) {
            $existing->active = 1;
            $existing->timemodified = $now;
            $this->db->update_record(self::MEMBER_TABLE, $existing);
            return;
        }
        $this->db->insert_record(self::MEMBER_TABLE, (object)[
            'groupid' => $groupid,
            'userid' => $userid,
            'active' => 1,
            'createdby' => $createdby,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function deactivate_membership(int $groupid, int $userid, int $now): void {
        $record = $this->db->get_record(
            self::MEMBER_TABLE,
            ['groupid' => $groupid, 'userid' => $userid],
            '*',
            IGNORE_MISSING
        );
        if (!$record) {
            return;
        }

        $record->active = 0;
        $record->timemodified = $now;
        $this->db->update_record(self::MEMBER_TABLE, $record);
    }

    private function hydrate_group(\stdClass $record): CommercePedagogicalGroup {
        return new CommercePedagogicalGroup(
            (int)$record->id,
            (int)$record->promotionid,
            (int)$record->moodlegroupid,
            (string)$record->displayname,
            (int)$record->position,
            $record->tutorid !== null ? (int)$record->tutorid : null,
            $record->supportlang !== null ? (string)$record->supportlang : null,
            $record->telegramref !== null ? (string)$record->telegramref : null,
            $record->levelupxp !== null ? (int)$record->levelupxp : null,
            (bool)$record->active,
            $record->createdby !== null ? (int)$record->createdby : null,
            $record->modifiedby !== null ? (int)$record->modifiedby : null,
            (int)$record->timecreated,
            (int)$record->timemodified,
            isset($record->tutorname) && $record->tutorname !== null
                ? (string)$record->tutorname
                : null,
            isset($record->productid) && $record->productid !== null
                ? (int)$record->productid
                : null
        );
    }
}
