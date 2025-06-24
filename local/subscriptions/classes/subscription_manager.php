<?php
namespace local_subscriptions;
use local_subscriptions\subscription_config;

defined('MOODLE_INTERNAL') || die();


class subscription_manager {

    public static function expire_subscription_if_needed() {
        global $DB;

        $now = time();
        $sql = "UPDATE {user_subscription}
                SET status = 'expired'
                WHERE status = 'active' AND end_date < :now";

        $DB->execute($sql, ['now' => $now]);
    }
    
	public static function get_end_date_from_duration_key(string $duration_key, int $startdate = null): int {
		$start = $startdate ?? time();
	
		switch ($duration_key) {
			case '1month':
				return strtotime('+1 month', $start);
			case '3months':
				return strtotime('+3 months', $start);
			case '6months':
				return strtotime('+6 months', $start);
			case '1year':
				return strtotime('+1 year', $start);
			case '3years':
				return strtotime('+3 years', $start);
			case 'lifetime':
				return strtotime('+100 years', $start);
			default:
				return $start;
		}
	}

	public static function calculate_end_date(int $duration, int $startdate = null): int {
		$start = $startdate ?? time();
		$duration_days = '+ '.($duration ?? 0).' days';
	
		return strtotime($duration_days, $start);
	}	

	public static function get_access_scope_from_planid(int $planid){
		global $DB;

		$plan = $DB->get_record('subscription_plan', ['id' => $planid], 'access_scope_id', IGNORE_MISSING);
		if (!$plan) {
			return null;
		}

		$scope = $DB->get_record('subscription_access_scope', ['id' => $plan->access_scope_id], '*', IGNORE_MISSING);
		return $scope ?: null;
	}


	public static function enrol_user_to_courses(int $userid, int $planid, int $startdate, int $enddate): void {
		$scope = self::get_access_scope_from_planid($planid);
		if (!$scope || empty($scope->course_ids)) {
			return;
		}

		$courseids = array_map('intval', explode(',', $scope->course_ids));
		
		foreach ($courseids as $courseid) {
			$context = \context_course::instance($courseid);
			if (is_enrolled($context, $userid)) {
				continue;
			}
	
			$instances = enrol_get_instances($courseid, true);
			foreach ($instances as $instance) {
				if ($instance->enrol === 'manual') {
					$plugin = enrol_get_plugin('manual');
					if ($plugin) {
						if ($enddate > strtotime('2100-01-01')){
							$enddate = 0; // means enrolment forever
						}

						$plugin->enrol_user($instance, $userid, 5, $startdate, $enddate); // rôle étudiant
					}
				}
			}
		}
	}
	
	public static function create_or_extend_subscription(
		int $userid,
		int $planid,
		string $payment_provider,
		string $transaction_id,
		int $startdate,
		int $enddate,
		float $pricepaid,
		string $currency,
		int $creationdate,
		bool $allowupdate = false
	): string {
		global $DB;

		$existing = $DB->get_record('user_subscription', [
			'userid' => $userid,
			'planid' => $planid,
			'status' => 'active'
		]);

		if ($existing) {
			if ($allowupdate) {
				$existing->payment_provider = $payment_provider;
				$existing->transaction_id = $transaction_id;
				$existing->start_date = $startdate;
				$existing->end_date = $enddate;
				$existing->pricepaid = $pricepaid;
				$existing->currency = $currency;
				$existing->last_update = time();

				$DB->update_record('user_subscription', $existing);
				return 'updated';
			} else {
				return 'exists';
			}
		}

		$record = (object)[
			'userid' => $userid,
			'planid' => $planid,
			'payment_provider' => $payment_provider,
			'transaction_id' => $transaction_id,
			'start_date' => $startdate,
			'end_date' => $enddate,
			'pricepaid' => $pricepaid,
			'currency' => $currency,
			'status' => 'active',
			'creation_date' => $creationdate,
			'last_update' => time()
		];

		$DB->insert_record('user_subscription', $record);
		return 'created';
	}

	public static function unenrol_user_from_plan(int $userid, int $planid): void {
		$scope = self::get_access_scope_from_planid($planid);
		if (!$scope || empty($scope->course_ids)) {
			return;
		}

		$courseids = array_map('intval', explode(',', $scope->course_ids));

		foreach ($courseids as $courseid) {
			$instances = enrol_get_instances($courseid, true);
			foreach ($instances as $instance) {
				if ($instance->enrol === 'manual') {
					$plugin = enrol_get_plugin('manual');
					if ($plugin) {
						$plugin->unenrol_user($instance, $userid);
					}
				}
			}
		}
	}

	public static function save_user_subscription(\stdClass $data): int {
		global $DB;

		$now = time();

		if (empty($data->id)) {
			$data->creation_date = $data->creation_date ?? $now;
			$data->status = $data->status ?? 'active';
			return $DB->insert_record('user_subscription', $data);
		} else {
			$data->last_update = $now;
			$DB->update_record('user_subscription', $data);
			return $data->id;
		}
	}

	public static function get_plan_id_by_name(string $name): ?int {
		global $DB;
		$record = $DB->get_record('subscription_plan', ['name' => $name], 'id', IGNORE_MISSING);
		return $record ? (int)$record->id : null;
	}

	public static function get_translated_plan_name(int $planid, string $lang): ?string {
		global $DB;

		$translation = $DB->get_record('subscription_plan_translation', [
			'plan_id' => $planid,
			'lang' => $lang
		], 'name', IGNORE_MULTIPLE); // Juste au cas où, même si UNIQUE(plan_id, lang)

		return $translation ? format_string($translation->name) : null;
	}



/* LEGACY : might be used later	
	public static function get_duration_from_plan(string $plan): ?int {
		switch ($plan) {
			case '1month':
				return 30 * 24 * 60 * 60;
			case '3months':
				return 90 * 24 * 60 * 60;
			case '6months':
				return 180 * 24 * 60 * 60;
			case '1year':
				return 365 * 24 * 60 * 60;
			case '3years':
				return 3 * 365 * 24 * 60 * 60;
			case 'lifetime':
				return 100 * 365 * 24 * 60 * 60; // 100 ans = illimité
			default:
				return null;
		}
	}	
 */
}