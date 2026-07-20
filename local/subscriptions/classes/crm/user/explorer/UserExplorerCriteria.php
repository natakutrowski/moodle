<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\user\UserExplorerFilter;

final class UserExplorerCriteria {

    public const ACCOUNT_ALL = '';
    public const ACCOUNT_ACTIVE = 'active';
    public const ACCOUNT_SUSPENDED = 'suspended';

    public const PRESENCE_ALL = '';
    public const PRESENCE_YES = 'yes';
    public const PRESENCE_NO = 'no';

    public const ACTIVITY_ALL = '';
    public const ACTIVITY_7_DAYS = '7days';
    public const ACTIVITY_30_DAYS = '30days';
    public const ACTIVITY_90_DAYS = '90days';
    public const ACTIVITY_NEVER = 'never';

    public const FUNNEL_NONE = '';
    public const FUNNEL_NEW_USERS = 'new_users';
    public const FUNNEL_TRIAL_USERS = 'trial_users';
    public const FUNNEL_NEW_CUSTOMERS = 'new_customers';
    public const FUNNEL_DIGITAL_BUYERS = 'digital_buyers';
    public const FUNNEL_CONVERTED_TRIALS = 'converted_trials';    

    public function __construct(
        public readonly string $query = '',
        public readonly string $intelligence = '',
        public readonly string $country = '',
        public readonly string $tag = '',
        public readonly string $accountstatus = '',
        public readonly string $sort = UserExplorerSort::NAME_ASC,
        public readonly int $page = 0,
        public readonly int $perpage = 25,
        public readonly ?int $scoremin = null,
        public readonly ?int $scoremax = null,
        public readonly ?int $riskmin = null,
        public readonly ?int $riskmax = null,
        public readonly string $hassubscription = '',
        public readonly string $haspurchase = '',
        public readonly string $activity = '',
        public readonly string $hasinbox = '',
        public readonly string $hasinboxunread = '',
        public readonly string $hascustomer_success_plan = '',
        public readonly string $customer_success_plan_blocked = '',
        public readonly string $customer_success_plan_status = '',
        public readonly string $funnelstage = '',
        public readonly int $funnelstart = 0,
        public readonly int $funnelend = 0,
        public readonly int $funnelwindow = 30,
        public readonly UserExplorerTrendFilter $trendfilter =
            new UserExplorerTrendFilter(
                '',
                0,
                0,
                5
            )
    ) {
    }

    public static function from_request(): self {
        return new self(
            trim(optional_param(
                'q',
                '',
                PARAM_RAW_TRIMMED
            )),
            UserExplorerFilter::normalize(
                optional_param(
                    'intelligence',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            clean_param(
                optional_param(
                    'country',
                    '',
                    PARAM_ALPHANUMEXT
                ),
                PARAM_ALPHANUMEXT
            ),
            clean_param(
                optional_param(
                    'tag',
                    '',
                    PARAM_ALPHANUMEXT
                ),
                PARAM_ALPHANUMEXT
            ),
            self::normalize_account_status(
                optional_param(
                    'accountstatus',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            UserExplorerSort::normalize(
                optional_param(
                    'sort',
                    UserExplorerSort::NAME_ASC,
                    PARAM_ALPHANUMEXT
                )
            ),
            max(0, optional_param('page', 0, PARAM_INT)),
            self::normalize_perpage(
                optional_param('perpage', 25, PARAM_INT)
            ),
            self::normalize_score(
                optional_param('scoremin', -1, PARAM_INT)
            ),
            self::normalize_score(
                optional_param('scoremax', -1, PARAM_INT)
            ),
            self::normalize_score(
                optional_param('riskmin', -1, PARAM_INT)
            ),
            self::normalize_score(
                optional_param('riskmax', -1, PARAM_INT)
            ),
            self::normalize_presence(
                optional_param(
                    'hassubscription',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_presence(
                optional_param(
                    'haspurchase',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_activity(
                optional_param(
                    'activity',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_presence(
                optional_param(
                    'hasinbox',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_presence(
                optional_param(
                    'hasinboxunread',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_presence(
                optional_param(
                    'hascustomer_success_plan',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_presence(
                optional_param(
                    'customer_success_plan_blocked',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_plan_status(
                optional_param(
                    'customer_success_plan_status',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            self::normalize_funnel_stage(
                optional_param(
                    'funnelstage',
                    '',
                    PARAM_ALPHANUMEXT
                )
            ),
            max(
                0,
                optional_param(
                    'funnelstart',
                    0,
                    PARAM_INT
                )
            ),
            max(
                0,
                optional_param(
                    'funnelend',
                    0,
                    PARAM_INT
                )
            ),
            self::normalize_funnel_window(
                optional_param(
                    'funnelwindow',
                    30,
                    PARAM_INT
                )
            ),
            trendfilter:
                self::trend_filter_from_request()
        );
    }

    public static function from_saved_params(
        array $params
    ): self {
        return new self(
            (string)($params['q'] ?? ''),
            UserExplorerFilter::normalize(
                (string)($params['intelligence'] ?? '')
            ),
            (string)($params['country'] ?? ''),
            (string)($params['tag'] ?? ''),
            self::normalize_account_status(
                (string)($params['accountstatus'] ?? '')
            ),
            UserExplorerSort::normalize(
                (string)($params['sort'] ?? '')
            ),
            0,
            self::normalize_perpage(
                (int)($params['perpage'] ?? 25)
            ),
            self::normalize_nullable_score(
                $params['scoremin'] ?? null
            ),
            self::normalize_nullable_score(
                $params['scoremax'] ?? null
            ),
            self::normalize_nullable_score(
                $params['riskmin'] ?? null
            ),
            self::normalize_nullable_score(
                $params['riskmax'] ?? null
            ),
            self::normalize_presence(
                (string)($params['hassubscription'] ?? '')
            ),
            self::normalize_presence(
                (string)($params['haspurchase'] ?? '')
            ),
            self::normalize_activity(
                (string)($params['activity'] ?? '')
            ),
            self::normalize_presence(
                (string)($params['hasinbox'] ?? '')
            ),
            self::normalize_presence(
                (string)($params['hasinboxunread'] ?? '')
            ),
            self::normalize_presence(
                (string)(
                    $params['hascustomer_success_plan']
                    ?? ''
                )
            ),
            self::normalize_presence(
                (string)(
                    $params['customer_success_plan_blocked']
                    ?? ''
                )
            ),
            self::normalize_plan_status(
                (string)(
                    $params['customer_success_plan_status']
                    ?? ''
                )
            ),
            self::normalize_funnel_stage(
                (string)($params['funnelstage'] ?? '')
            ),
            max(
                0,
                (int)($params['funnelstart'] ?? 0)
            ),
            max(
                0,
                (int)($params['funnelend'] ?? 0)
            ),
            self::normalize_funnel_window(
                (int)($params['funnelwindow'] ?? 30)
            ),
            trendfilter:
                UserExplorerTrendFilter::create(
                    (string)($params['trend'] ?? ''),
                    (int)($params['trendstart'] ?? 0),
                    (int)($params['trendend'] ?? 0),
                    (int)($params['trenddelta'] ?? 5)
                )
        );
    }

    public static function account_statuses(): array {
        return [
            self::ACCOUNT_ALL,
            self::ACCOUNT_ACTIVE,
            self::ACCOUNT_SUSPENDED,
        ];
    }

    public static function presence_options(): array {
        return [
            self::PRESENCE_ALL,
            self::PRESENCE_YES,
            self::PRESENCE_NO,
        ];
    }

    public static function activity_options(): array {
        return [
            self::ACTIVITY_ALL,
            self::ACTIVITY_7_DAYS,
            self::ACTIVITY_30_DAYS,
            self::ACTIVITY_90_DAYS,
            self::ACTIVITY_NEVER,
        ];
    }

    public static function account_status_label(
        string $status
    ): string {
        $status = self::normalize_account_status($status);

        return get_string(
            $status === ''
                ? 'crm_user_account_status_all'
                : 'crm_user_account_status_' . $status,
            'local_subscriptions'
        );
    }

    public static function presence_label(
        string $presence
    ): string {
        $presence = self::normalize_presence($presence);

        return get_string(
            $presence === ''
                ? 'crm_user_presence_all'
                : 'crm_user_presence_' . $presence,
            'local_subscriptions'
        );
    }

    public static function activity_label(
        string $activity
    ): string {
        $activity = self::normalize_activity($activity);

        return get_string(
            $activity === ''
                ? 'crm_user_activity_all'
                : 'crm_user_activity_' . $activity,
            'local_subscriptions'
        );
    }

    public function offset(): int {
        return $this->page * $this->perpage;
    }

    public function without_inbox(): self {
        if (
            $this->hasinbox === ''
            && $this->hasinboxunread === ''
        ) {
            return $this;
        }

        return new self(
            $this->query,
            $this->intelligence,
            $this->country,
            $this->tag,
            $this->accountstatus,
            $this->sort,
            $this->page,
            $this->perpage,
            $this->scoremin,
            $this->scoremax,
            $this->riskmin,
            $this->riskmax,
            $this->hassubscription,
            $this->haspurchase,
            $this->activity,
            self::PRESENCE_ALL,
            self::PRESENCE_ALL,
            $this->hascustomer_success_plan,
            $this->customer_success_plan_blocked,
            $this->customer_success_plan_status,
            $this->funnelstage,
            $this->funnelstart,
            $this->funnelend,
            $this->funnelwindow,
            $this->trendfilter
        );
    }

    public function with_page(int $page): self {
        return new self(
            $this->query,
            $this->intelligence,
            $this->country,
            $this->tag,
            $this->accountstatus,
            $this->sort,
            max(0, $page),
            $this->perpage,
            $this->scoremin,
            $this->scoremax,
            $this->riskmin,
            $this->riskmax,
            $this->hassubscription,
            $this->haspurchase,
            $this->activity,
            $this->hasinbox,
            $this->hasinboxunread,
            $this->hascustomer_success_plan,
            $this->customer_success_plan_blocked,
            $this->customer_success_plan_status,
            $this->funnelstage,
            $this->funnelstart,
            $this->funnelend,
            $this->funnelwindow,
            $this->trendfilter
        );
    }
    
    public function url_params(
        bool $includepage = true
    ): array {
        $params = $this->saved_params();

        if ($includepage && $this->page > 0) {
            $params['page'] = $this->page;
        }

        return $params;
    }

    public function saved_params(): array {
        $params = [
            'sort' => $this->sort,
            'perpage' => $this->perpage,
        ];

        foreach ([
            'q' => $this->query,
            'intelligence' => $this->intelligence,
            'country' => $this->country,
            'tag' => $this->tag,
            'accountstatus' => $this->accountstatus,
            'hassubscription' => $this->hassubscription,
            'haspurchase' => $this->haspurchase,
            'activity' => $this->activity,
            'hasinbox' => $this->hasinbox,
            'hasinboxunread' => $this->hasinboxunread,
            'hascustomer_success_plan' =>
                $this->hascustomer_success_plan,

            'customer_success_plan_blocked' =>
                $this->customer_success_plan_blocked,

            'customer_success_plan_status' =>
                $this->customer_success_plan_status,
            'funnelstage' => $this->funnelstage,
        ] as $key => $value) {
            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        if (
            $this->funnelstage !== ''
            && $this->funnelstart > 0
            && $this->funnelend > $this->funnelstart
        ) {
            $params['funnelstart'] =
                $this->funnelstart;

            $params['funnelend'] =
                $this->funnelend;

            $params['funnelwindow'] =
                $this->funnelwindow;
        }

        foreach ([
            'scoremin' => $this->scoremin,
            'scoremax' => $this->scoremax,
            'riskmin' => $this->riskmin,
            'riskmax' => $this->riskmax,
        ] as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            }
        }

        $params = array_merge(
            $params,
            $this->trendfilter->params()
        );

        return $params;
    }

    public function active_filter_count(): int {
        $values = [
            $this->query,
            $this->intelligence,
            $this->country,
            $this->tag,
            $this->accountstatus,
            $this->hassubscription,
            $this->haspurchase,
            $this->activity,
            $this->hasinbox,
            $this->hasinboxunread,
            $this->hascustomer_success_plan,
            $this->customer_success_plan_blocked,
            $this->customer_success_plan_status,
            $this->scoremin,
            $this->scoremax,
            $this->riskmin,
            $this->riskmax,
            $this->funnelstage,
        ];

        $count = count(
            array_filter(
                $values,
                static fn(mixed $value): bool =>
                    $value !== ''
                    && $value !== null
            )
        );

        if ($this->trendfilter->is_active()) {
            $count++;
        }

        return $count;
    }

    private static function normalize_account_status(
        string $status
    ): string {
        return in_array(
            $status,
            self::account_statuses(),
            true
        ) ? $status : '';
    }

    private static function normalize_presence(
        string $presence
    ): string {
        return in_array(
            $presence,
            self::presence_options(),
            true
        ) ? $presence : '';
    }

    private static function normalize_activity(
        string $activity
    ): string {
        return in_array(
            $activity,
            self::activity_options(),
            true
        ) ? $activity : '';
    }

    private static function normalize_plan_status(
        string $status
    ): string {
        return in_array(
            $status,
            [
                '',
                'draft',
                'active',
                'paused',
                'completed',
                'cancelled',
            ],
            true
        )
            ? $status
            : '';
    }

    private static function normalize_score(
        int $score
    ): ?int {
        return $score >= 0 && $score <= 100
            ? $score
            : null;
    }

    private static function normalize_nullable_score(
        mixed $score
    ): ?int {
        return is_numeric($score)
            ? self::normalize_score((int)$score)
            : null;
    }

    private static function normalize_perpage(
        int $perpage
    ): int {
        return in_array(
            $perpage,
            [25, 50, 100],
            true
        ) ? $perpage : 25;
    }

    /**
     * Normalize one Dashboard Funnel drill-down stage.
     *
     * @param string $stage
     * @return string
     */
    private static function normalize_funnel_stage(
        string $stage
    ): string {
        return in_array(
            $stage,
            [
                self::FUNNEL_NONE,
                self::FUNNEL_NEW_USERS,
                self::FUNNEL_TRIAL_USERS,
                self::FUNNEL_NEW_CUSTOMERS,
                self::FUNNEL_DIGITAL_BUYERS,
                self::FUNNEL_CONVERTED_TRIALS,
            ],
            true
        ) ? $stage : self::FUNNEL_NONE;
    }

    /**
     * Normalize the cohort conversion observation window.
     *
     * @param int $days
     * @return int
     */
    private static function normalize_funnel_window(
        int $days
    ): int {
        return max(
            1,
            min(365, $days)
        );
    }

    /**
     * Whether a valid Funnel drill-down is active.
     *
     * @return bool
     */
    public function has_funnel_filter(): bool {
        return
            $this->funnelstage !== self::FUNNEL_NONE
            && $this->funnelstart > 0
            && $this->funnelend > $this->funnelstart;
    }    

    /**
     * Read and normalize the Dashboard trend drill-down.
     */
    private static function trend_filter_from_request():
            UserExplorerTrendFilter {
        return UserExplorerTrendFilter::create(
            optional_param(
                'trend',
                '',
                PARAM_ALPHANUMEXT
            ),
            optional_param(
                'trendstart',
                0,
                PARAM_INT
            ),
            optional_param(
                'trendend',
                0,
                PARAM_INT
            ),
            optional_param(
                'trenddelta',
                5,
                PARAM_INT
            )
        );
    }

}