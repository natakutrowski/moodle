<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores an optional clan cover in Moodle's private file storage, under the
 * promotion course context. No schema change or public pluginfile URL.
 * The image is served only after clan membership or CRM permission checks.
 */
final class CommerceClanPhotoService {
    private const AREA = 'clanphoto';
    private const MAX_BYTES = 4 * 1024 * 1024;

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function image(int $groupid, int $courseid): ?\stored_file {
        if ($groupid <= 0 || $courseid <= 0) {
            return null;
        }
        $context = \context_course::instance($courseid);
        $files = get_file_storage()->get_area_files(
            $context->id, 'local_subscriptions', self::AREA, $groupid,
            'id DESC', false
        );
        return $files ? reset($files) : null;
    }

    public function url(int $groupid, int $courseid): string {
        $file = $this->image($groupid, $courseid);
        if (!$file) {
            return '';
        }
        return (new \moodle_url('/local/subscriptions/clan_photo.php', [
            'groupid' => $groupid,
            'v' => $file->get_id(),
        ]))->out(false);
    }

    /**
     * Used only by CRM after MANAGE_PROMOTIONS and sesskey checks.
     * @param array<string,mixed>|null $upload Contents of $_FILES['clanphoto'].
     */
    public function save(int $groupid, int $courseid, ?array $upload, bool $remove, int $actorid): void {
        $group = $this->db->get_record('local_subs_commerce_ped_group', ['id' => $groupid],
            'id,moodlegroupid,promotionid', MUST_EXIST);
        $promotion = $this->db->get_record('local_subs_commerce_ped_promo',
            ['id' => (int)$group->promotionid], 'id,courseid', MUST_EXIST);
        $moodlegroup = $this->db->get_record('groups',
            ['id' => (int)$group->moodlegroupid, 'courseid' => $courseid], 'id', MUST_EXIST);
        if ((int)$promotion->courseid !== $courseid) {
            throw new \coding_exception('Clan photo course/promotion mismatch.');
        }

        $context = \context_course::instance($courseid);
        $fs = get_file_storage();
        if ($remove) {
            $fs->delete_area_files($context->id, 'local_subscriptions', self::AREA, $groupid);
            return;
        }
        if (!$upload || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \moodle_exception('commerce_my_clan_photo_missing', 'local_subscriptions');
        }
        $tmpname = (string)($upload['tmp_name'] ?? '');
        $size = (int)($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES || !is_uploaded_file($tmpname)) {
            throw new \moodle_exception('commerce_my_clan_photo_size', 'local_subscriptions');
        }
        $dimensions = @getimagesize($tmpname);
        $mime = is_array($dimensions) ? (string)($dimensions['mime'] ?? '') : '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($extensions[$mime]) || (int)$dimensions[0] < 1 || (int)$dimensions[1] < 1 ||
                (int)$dimensions[0] > 6000 || (int)$dimensions[1] > 6000) {
            throw new \moodle_exception('commerce_my_clan_photo_invalid', 'local_subscriptions');
        }

        // Store the replacement first: a storage failure must not destroy the old picture.
        $previous = $fs->get_area_files(
            $context->id, 'local_subscriptions', self::AREA, $groupid, 'id DESC', false
        );
        $fs->create_file_from_pathname((object)[
            'contextid' => $context->id,
            'component' => 'local_subscriptions',
            'filearea' => self::AREA,
            'itemid' => $groupid,
            'filepath' => '/',
            'filename' => 'cover-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime],
            'mimetype' => $mime,
            'userid' => $actorid,
            'license' => 'allrightsreserved',
            'source' => 'CRM clan picture',
        ], $tmpname);
        foreach ($previous as $file) {
            $file->delete();
        }
    }

    /** Read permission for clan-photo endpoint (admins or the current clan's active members). */
    public function may_view(int $groupid, int $userid, bool $canviewcrm): bool {
        if ($groupid <= 0 || $userid <= 1) {
            return false;
        }
        if ($canviewcrm) {
            return true;
        }
        return $this->db->record_exists_sql(
            'SELECT g.id
               FROM {local_subs_commerce_ped_group} g
               JOIN {local_subs_commerce_ped_promo} p ON p.id = g.promotionid
               JOIN {local_subs_commerce_ped_gmem} gm ON gm.groupid = g.id
               JOIN {local_subs_commerce_ped_join} j ON j.promotionid = g.promotionid AND j.userid = gm.userid
               JOIN {local_subs_commerce_ped_access} a ON a.promotionid = g.promotionid
                    AND a.userid = gm.userid AND a.courseid = p.courseid
              WHERE g.id = :gid AND g.active = 1 AND gm.userid = :uid
                AND gm.active = 1 AND j.state = :state',
            ['gid' => $groupid, 'uid' => $userid, 'state' => 'active']
        );
    }
}
