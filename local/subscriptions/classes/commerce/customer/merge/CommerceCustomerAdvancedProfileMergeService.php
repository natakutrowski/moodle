<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/** Applies administrator-selected profile fields to the retained Moodle account. */
final class CommerceCustomerAdvancedProfileMergeService {
    public const FIELDS = [
        'firstname', 'lastname', 'middlename', 'alternatename', 'firstnamephonetic', 'lastnamephonetic',
        'idnumber', 'institution', 'department', 'phone1', 'phone2', 'address', 'city', 'country',
        'lang', 'timezone', 'description',
    ];

    public function __construct(private readonly moodle_database $db) {}

    /** @param array<string,int|string> $choices @param int[] $alloweduserids @return array<string,int> */
    public function apply(int $targetuserid, array $choices, array $alloweduserids): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $alloweduserids = array_map('intval', $alloweduserids);
        $target = $this->db->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);
        $changed = [];
        foreach (self::FIELDS as $field) {
            $sourceuserid = (int)($choices[$field] ?? $targetuserid);
            if ($sourceuserid === $targetuserid || !in_array($sourceuserid, $alloweduserids, true)) {
                continue;
            }
            $source = $this->db->get_record('user', ['id' => $sourceuserid, 'deleted' => 0], '*', MUST_EXIST);
            if (!property_exists($source, $field)) {
                continue;
            }
            $target->{$field} = $source->{$field};
            $changed[$field] = $sourceuserid;
        }
        if ($changed !== []) {
            user_update_user($target, false, false);
        }

        foreach ($choices as $key => $rawsourceuserid) {
            if (!preg_match('/^custom_(\d+)$/', (string)$key, $matches)) {
                continue;
            }
            $fieldid = (int)$matches[1];
            $sourceuserid = (int)$rawsourceuserid;
            if ($sourceuserid === $targetuserid || !in_array($sourceuserid, $alloweduserids, true)) {
                continue;
            }
            if (!$this->db->record_exists('user_info_field', ['id' => $fieldid])) {
                continue;
            }
            $sourcedata = $this->db->get_record('user_info_data', ['userid' => $sourceuserid, 'fieldid' => $fieldid]);
            if (!$sourcedata) {
                continue;
            }
            $targetdata = $this->db->get_record('user_info_data', ['userid' => $targetuserid, 'fieldid' => $fieldid]);
            if ($targetdata) {
                $targetdata->data = $sourcedata->data;
                $targetdata->dataformat = $sourcedata->dataformat;
                $this->db->update_record('user_info_data', $targetdata);
            } else {
                $this->db->insert_record('user_info_data', (object)[
                    'userid' => $targetuserid,
                    'fieldid' => $fieldid,
                    'data' => $sourcedata->data,
                    'dataformat' => $sourcedata->dataformat,
                ]);
            }
            $changed['custom_' . $fieldid] = $sourceuserid;
        }

        return $changed;
    }
}
