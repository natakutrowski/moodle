<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

class subscription_manager {

	public static function create_subscription($userid, $plan, $provider, $subid, $start, $end, $accessscope = null): void {
		global $DB;
	
		// Chercher une subscription active existante
		$existing = $DB->get_record('user_subscription', [
			'userid' => $userid,
			'status' => 'active'
		]);
	
		if ($existing && $existing->end_date > time()) {
			// Mettre à jour la date de fin + scope + plan + update timestamp
			$existing->plan = $plan;
			$existing->end_date += ($end - $start);
			$existing->last_update = time();
			$existing->access_scope = $accessscope;
			$existing->payment_provider = $provider;
			$existing->subscription_id = $subid;
	
			$DB->update_record('user_subscription', $existing);
		} else {
			// Créer une nouvelle entrée
			$record = new \stdClass();
			$record->userid = $userid;
			$record->plan = $plan;
			$record->payment_provider = $provider;
			$record->subscription_id = $subid;
			$record->start_date = $start;
			$record->end_date = $end;
			$record->status = 'active';
			$record->last_update = $start;
			$record->access_scope = $accessscope;
	
			$DB->insert_record('user_subscription', $record);
		}
		self::enrol_user_to_courses($userid, $accessscope);
	}


    public static function get_active_subscription($userid) {
        global $DB;
        $now = time();

        return $DB->get_record_select('user_subscription',
            'userid = :userid AND status = :status AND end_date > :now',
            ['userid' => $userid, 'status' => 'active', 'now' => $now]);
    }

    public static function expire_subscription_if_needed() {
        global $DB;

        $now = time();
        $sql = "UPDATE {user_subscription}
                SET status = 'expired'
                WHERE status = 'active' AND end_date < :now";

        $DB->execute($sql, ['now' => $now]);
    }
    
	public static function get_end_date_from_plan(string $plan, int $startdate = null): int {
		$start = $startdate ?? time();
	
		switch ($plan) {
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
			default:
				return strtotime('+1 year', $start);
		}
	}
  
	
	public static function enrol_user_to_courses(int $userid, string $accessscope): void {
		global $DB;
	
		$mapping = [
			'full'     => [2, 5],
			'test'     => [3],
			'a0_only'  => [2],
			'a2_only'  => [5],
		];
	
		if (array_key_exists($accessscope, $mapping)) {
			$courseids = $mapping[$accessscope];
		} elseif (preg_match('/^custom_([\d,]+)$/', $accessscope, $matches)) {
			$courseids = array_map('intval', explode(',', $matches[1]));
		} else {
			return; // accès inconnu → rien à faire
		}
	
		if (empty($courseids)) return;
	
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
						$plugin->enrol_user($instance, $userid, 5); // rôle étudiant
					}
				}
			}
		}
	}

	
	public static function unenrol_user_from_all_courses(int $userid): void {
		global $DB;
	
		$enrolled = enrol_get_users_courses($userid, true);
		foreach ($enrolled as $course) {
			$instances = enrol_get_instances($course->id, true);
			foreach ($instances as $instance) {
				if ($instance->enrol === 'manual') {
					$plugin = enrol_get_plugin('manual');
					if ($plugin) {
						// Optionnel : vérifier l’inscription dans l’instance
						$context = context_course::instance($course->id);
						if (is_enrolled($context, $userid)) {
							$plugin->unenrol_user($instance, $userid);
						}
					}
				}
			}
		}
	}

	
	public static function enrol_user_to_test_course(int $userid): void {
		$testcourseid = 3; 
		$instances = enrol_get_instances($testcourseid, true);
	
		foreach ($instances as $instance) {
			if ($instance->enrol === 'manual') {
				$plugin = enrol_get_plugin('manual');
				if ($plugin) {
					$plugin->enrol_user($instance, $userid, 5); // 5 = rôle étudiant
				}
			}
		}
	}
	
	public static function get_all_manual_courses_for_user(int $userid): array {
		global $DB;
	
		$sql = "SELECT c.id, c.fullname
				FROM {user_enrolments} ue
				JOIN {enrol} e ON ue.enrolid = e.id
				JOIN {course} c ON e.courseid = c.id
				WHERE ue.userid = :userid AND e.enrol = 'manual'";
	
		return $DB->get_records_sql($sql, ['userid' => $userid]);
	}
	
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
			case 'unlimited':
				return 100 * 365 * 24 * 60 * 60; // 100 ans = illimité
			default:
				return null;
		}
	}	
	
	public static function extend_subscription(int $userid, string $plan): bool {
		global $DB;
	
		$duration = self::get_duration_from_plan($plan);
		if ($duration === null) {
			return false;
		}
	
		$subscription = $DB->get_record('user_subscription', [
			'userid' => $userid,
			'status' => 'active'
		]);
	
		if (!$subscription) {
			return false;
		}
	
		// Étend la date de fin
		$subscription->end_date += $duration;
		$subscription->last_update = time();
		$subscription->plan = $plan;
	
		return $DB->update_record('user_subscription', $subscription);
	}
	
	public static function create_or_extend_subscription(int $userid, string $plan, string $provider, string $subid, int $startdate, int $enddate, string $accessscope): string {
		global $DB;
	
		$current = $DB->get_record_select('user_subscription', 'userid = :userid AND status = :status AND end_date > :now', [
			'userid' => $userid,
			'status' => 'active',
			'now' => time()
		]);
	
		if ($current) {
			$duration = self::get_duration_from_plan($plan);
			$current->end_date += $duration;
			$current->last_update = time();
			$DB->update_record('user_subscription', $current);
			return 'extended';
		} else {
			$record = new \stdClass();
			$record->userid = $userid;
			$record->plan = $plan;
			$record->payment_provider = $provider;
			$record->subscription_id = $subid;
			$record->start_date = $startdate;
			$record->end_date = $enddate;
			$record->status = 'active';
			$record->last_update = time();
			$record->access_scope = $accessscope;
			$DB->insert_record('user_subscription', $record);
			return 'created';
		}
	}

	
	
    
}
