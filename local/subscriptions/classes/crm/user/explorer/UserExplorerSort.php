<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerSort {

    public const NAME_ASC = 'name_asc';
    public const NAME_DESC = 'name_desc';
    public const SCORE_DESC = 'score_desc';
    public const RISK_DESC = 'risk_desc';
    public const LAST_ACCESS_DESC = 'last_access_desc';
    public const CREATED_DESC = 'created_desc';

    public static function allowed(): array {
        return [
            self::NAME_ASC,
            self::NAME_DESC,
            self::SCORE_DESC,
            self::RISK_DESC,
            self::LAST_ACCESS_DESC,
            self::CREATED_DESC,
        ];
    }

    public static function normalize(string $sort): string {
        return in_array($sort, self::allowed(), true)
            ? $sort
            : self::NAME_ASC;
    }

    public static function sql(string $sort): string {
        return match (self::normalize($sort)) {
            self::NAME_DESC =>
                'u.lastname DESC, u.firstname DESC, u.id DESC',

            self::SCORE_DESC =>
                'COALESCE(score.globalscore, 0) DESC, ' .
                'u.lastname ASC, u.firstname ASC, u.id DESC',

            self::RISK_DESC =>
                'COALESCE(score.riskscore, 0) DESC, ' .
                'u.lastname ASC, u.firstname ASC, u.id DESC',

            self::LAST_ACCESS_DESC =>
                'u.lastaccess DESC, u.id DESC',

            self::CREATED_DESC =>
                'u.timecreated DESC, u.id DESC',

            default =>
                'u.lastname ASC, u.firstname ASC, u.id DESC',
        };
    }

    public static function label(string $sort): string {
        return get_string(
            'crm_user_sort_' . self::normalize($sort),
            'local_subscriptions'
        );
    }
}