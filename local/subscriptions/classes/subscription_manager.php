<?php
namespace local_subscriptions;
use local_subscriptions\subscription_config;

defined('MOODLE_INTERNAL') || die();


class subscription_manager {


/* 
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
 */

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
			case 'lifetime':
				return strtotime('+100 years', $start);
			default:
				return strtotime('+1 year', $start);
		}
	}
	
	public static function enrol_user_to_courses(int $userid, string $accessscope): void {
		global $DB;
		
		if (array_key_exists($accessscope, subscription_config::SUBSCRIPTION_MAPPING)) {
			$courseids = subscription_config::SUBSCRIPTION_MAPPING[$accessscope];
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
	
	public static function create_or_extend_subscription(
		int $userid,
		string $plan,
		string $provider,
		string $subid,
		int $startdate,
		int $enddate,
		string $accessscope,
		int $creationdate,
		bool $allowupdate = false
	): string {
		global $DB;
	
		$existing = $DB->get_record('user_subscription', [
			'userid' => $userid,
			'access_scope' => $accessscope,
			'status' => 'active'
		]);
	
		if ($existing) {
			if ($allowupdate) {
				$existing->plan = $plan;
				$existing->payment_provider = $provider;
				$existing->subscription_id = $subid;
				$existing->start_date = $startdate;
				$existing->end_date = $enddate;
				$existing->last_update = time();
	
				$DB->update_record('user_subscription', $existing);
				return 'updated';
			} else {
				return 'exists';
			}
		}
	
		$record = (object)[
			'userid' => $userid,
			'plan' => $plan,
			'payment_provider' => $provider,
			'subscription_id' => $subid,
			'start_date' => $startdate,
			'end_date' => $enddate,
			'status' => 'active',
			'last_update' => time(),
			'access_scope' => $accessscope, 
			'creation_date' => $creationdate
		];
	
		$DB->insert_record('user_subscription', $record);
		return 'created';
	}

	public static function unenrol_user_from_scope(int $userid, string $accessscope): void {

		if (!array_key_exists($accessscope, subscription_config::SUBSCRIPTION_MAPPING)) {
			return;
		}
	
		$courseids = subscription_config::SUBSCRIPTION_MAPPING[$accessscope];
	
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
 */
}