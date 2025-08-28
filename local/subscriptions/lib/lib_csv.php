<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/csvlib.class.php');

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

/**
 * Lit un fichier CSV et retourne les lignes valides + doublons détectés.
 *
 * @param string $tmpfile Chemin du fichier temporaire
 * @param string $separator Délimiteur CSV (par défaut : ',')
 * @return array [$rows, $validrows, $headers]
 */
function parse_csv_file(string $tmpfile, string $separator = ','): array {
    global $DB;

    $content = file_get_contents($tmpfile);
    $lines = explode(PHP_EOL, $content);

    $rows = [];
    $headers = [];

    $existing_subs = get_all_existing_subscriptions();

    $validrows = [];

    foreach ($lines as $index => $line) {
        if (trim($line) === '') continue;

        $record = str_getcsv($line, $separator, '"', '\\');

        if ($index === 0) {
            $headers = array_map(function ($h) {
                return ltrim(trim($h), "\xEF\xBB\xBF"); // Enlève BOM + espaces
            }, $record);
        } else {
            if (count($record) < 5) continue;

            $row = array_combine($headers, $record);
            $email = strtolower(trim($row['email'] ?? ''));
			$row['email'] = $email;
			$row['start_date'] = trim($row['start_date'] ?? '');
			$row['price'] = trim($row['price'] ?? '');
			$row['currency'] = trim($row['currency'] ?? '');
            
            $planname = $row['plan'];
			$planrecord = $DB->get_record('subscription_plan', ['name' => $planname], 'id');
			if ($planrecord) {
				$planid = $planrecord->id;
			} else {
				// Plan introuvable — à traiter (log ou ignorer la ligne)
				$planid = null;
			}
			$row['plan'] = trim($row['plan'] ?? '');

            $is_duplicate = isset($existing_subs[$email][$planid]);

            $row['_duplicate'] = $is_duplicate;
            $rows[] = $row;

            if (!$is_duplicate) {
                $validrows[] = $row;
            }
        }
    }

    return [$rows, $validrows, $headers];
}

/**
 * Retourne un tableau des emails → plan_ids existants déjà dans la base.
 *
 * @return array [email => [planid => true]]
 */
function get_all_existing_subscriptions(): array {
    global $DB;
    $existing = [];

    $subs = $DB->get_records('user_subscription');
    foreach ($subs as $sub) {
        $user = $DB->get_record('user', ['id' => $sub->userid, 'deleted' => 0], 'email');

        if ($user) {
            $email = strtolower(trim($user->email));
            $existing[$email][$sub->planid] = true;
        }
    }

    return $existing;
}

/**
 * Tente de parser une date de type DD/MM/YYYY en timestamp.
 *
 * @param string $datestring
 * @return int|false
 */
function parse_date(string $datestring) {
    $parts = explode('/', $datestring);
    if (count($parts) === 3) {
        [$day, $month, $year] = $parts;
        return mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);
    }
    return false;
}

/**
 * Retourne l’ID d’un plan à partir de son nom (exact).
 *
 * @param string $planname
 * @return int|null
 */
function get_plan_id_by_name(string $planname): ?int {
    global $DB;
    $record = $DB->get_record('subscription_plan', ['name' => $planname], 'id');
    return $record ? (int) $record->id : null;
}

function process_csv_rows(array $validrows): array {
    global $DB;

    $imported = 0;
    $skipped = [];

    foreach ($validrows as $assoc) {
        $email = trim($assoc['email']);
        $start_date = parse_date($assoc['start_date']);
        $planname = trim($assoc['plan'] ?? '');
        $pricedata = floatval($assoc['price'] ?? 0);
        $currency = trim($assoc['currency'] ?? '');

        if (!$email || !$start_date || !$planname || !$pricedata || !$currency) {
            $skipped[] = ['data' => $assoc, 'reason' => get_string('invalid_or_missing_fields', 'local_subscriptions')];
            continue;
        }

        $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
        if (!$user) {
            $skipped[] = ['data' => $assoc, 'reason' => get_string('user_not_found', 'local_subscriptions')];
            continue;
        }
        $planrecord = $DB->get_record('subscription_plan', ['name' => $planname], 'id, duration_key', IGNORE_MULTIPLE);

        $planid = $planrecord->id ?? null;
        $durationkey = $planrecord->duration_key ?? null;

        $end_date = subscription_manager::get_end_date_from_duration_key($durationkey, $start_date);
        
        subscription_manager::create_or_extend_subscription($user->id, 
            planid: $planid, 
            payment_provider: subscription_config::PAYMENT_PROVIDER_CSV, 
            transactionid: uniqid('csv_'), 
            start_date: $start_date, 
            end_date: $end_date, 
            pricepaid: $pricedata, 
            currency: $currency, 
            creation_date: time()
        );
        
        subscription_manager::enrol_user_to_courses($user->id, $planid, $start_date, $end_date);
        $imported++;
    }

    return [$imported, $skipped];
}
