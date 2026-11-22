<?php
// CampusFR C2.2b.1 — read-only access/Commerce mapping inspection. GPL-3.0-or-later.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('CLI_SCRIPT', true);
$root = realpath(dirname(__DIR__, 3));
if ($root !== '/var/www/moodle-dev') {
    fwrite(STDERR, "STOP : uniquement Moodle DEV.\n");
    exit(3);
}
require_once($root . '/config.php');

global $DB;
if (count($argv) > 2 || (count($argv) === 2 &&
    !preg_match('/\A--userid=([1-9][0-9]{0,9})\z/D', $argv[1], $match))) {
    fwrite(STDERR, "Usage: php cli/audit_rights.php [--userid=ID_TEST_DEV]\n");
    exit(2);
}
$userid = isset($match[1]) ? (int)$match[1] : null;
$courseid = 19;
$course = $DB->get_record('course', ['id' => $courseid], 'id,fullname,shortname,visible', MUST_EXIST);
if ($course->shortname !== 'AVENT2026-DEV') {
    throw new RuntimeException('Le cours support DEV a changé, audit interrompu.');
}
$collection = $DB->get_record('local_cr_collection', ['courseid' => $courseid, 'slug' => 'avent-2026'], '*', MUST_EXIST);
echo "===== C2.2b.1 — AUDIT DROITS DEV (LECTURE SEULE) =====\n";
echo "COURSE id=19 visible=$course->visible shortname=$course->shortname\n";
echo "COLLECTION id=$collection->id published=$collection->published mode=" . ($collection->presentationmode ?? 'auto') . "\n";
$enrols = $DB->get_records('enrol', ['courseid' => $courseid], 'id ASC', 'id,enrol,status');
foreach ($enrols as $enrol) {
    echo "ENROL instance=$enrol->id method=$enrol->enrol status=$enrol->status\n";
}
$scopeids = [];
if ($DB->get_manager()->table_exists(new xmldb_table('subscription_access_scope'))) {
    $scopes = $DB->get_records('subscription_access_scope');
    foreach ($scopes as $scope) {
        $value = (string)$scope->course_ids;
        $decoded = json_decode($value, true);
        $numbers = is_array($decoded) ? $decoded : preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $ids = array_map('intval', $numbers ?: []);
        if (in_array($courseid, $ids, true)) {
            $scopeids[(int)$scope->id] = true;
            echo 'SCOPE id=' . (int)$scope->id . ' includes_course_19=YES' . "\n";
        }
    }
}
$planids = [];
if ($DB->get_manager()->table_exists(new xmldb_table('subscription_plan'))) {
    foreach ($DB->get_records('subscription_plan') as $plan) {
        if (isset($scopeids[(int)$plan->accessscopeid])) {
            $planids[(int)$plan->id] = true;
            echo 'PLAN id=' . (int)$plan->id . ' scope=' . (int)$plan->accessscopeid .
                ' active=' . (int)$plan->is_active . "\n";
        }
    }
}
$productcount = 0;
if ($DB->get_manager()->table_exists(new xmldb_table('local_subs_commerce_product'))) {
    foreach ($DB->get_records('local_subs_commerce_product', ['type' => 'course_access']) as $product) {
        $meta = json_decode((string)$product->metadatajson, true);
        $access = is_array($meta['access'] ?? null) ? $meta['access'] : [];
        $scopeid = (int)($access['scopeid'] ?? 0);
        $planid = (int)($access['sourceplanid'] ?? 0);
        $persisted = false;
        if ($DB->get_manager()->table_exists(new xmldb_table('local_subs_commerce_prod_ent'))) {
            $ents = $DB->get_records('local_subs_commerce_prod_ent', ['productid' => $product->id]);
            foreach ($ents as $ent) {
                $config = json_decode((string)$ent->configurationjson, true);
                if ($ent->type === 'course_access' &&
                    ((int)($config['courseid'] ?? 0) === $courseid ||
                    str_starts_with((string)$ent->resourcekey, 'course:' . $courseid . ':'))) {
                    $persisted = true;
                }
            }
        }
        if ($persisted || isset($scopeids[$scopeid]) || isset($planids[$planid])) {
            $productcount++;
            echo 'PRODUCT id=' . (int)$product->id . ' status=' . $product->status .
                ' sku=' . $product->sku . ' scope=' . $scopeid . ' sourceplan=' . $planid .
                ' persisted_to_19=' . ($persisted ? 'YES' : 'NO') . "\n";
            if (!$persisted && $DB->record_exists('local_subs_commerce_prod_ent', ['productid' => $product->id])) {
                echo "  ATTENTION : définitions persistées présentes, vérifier leur priorité avant d’utiliser le fallback scope.\n";
            }
        }
    }
}
if (!$scopeids) {
    echo "SCOPES : aucun Access Scope trouvé pour le cours 19.\n";
}
if (!$productcount) {
    echo "PRODUCTS : aucun produit course_access associé au cours 19.\n";
}
if ($userid !== null) {
    $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id,suspended', MUST_EXIST);
    $context = context_course::instance($courseid);
    $active = is_enrolled($context, $userid, '', true);
    $capability = has_capability('moodle/course:view', $context, $userid);
    $eligible = !$user->suspended && $course->visible && $collection->published && $active && $capability;
    echo 'USER id=' . $userid . ' suspended=' . (int)$user->suspended .
        ' enrol_active=' . (int)$active . ' can_course_view=' . (int)$capability .
        ' eligible_collection=' . (int)(bool)$eligible . "\n";
}
echo "OK : aucune écriture ni inscription, produits et contenus inchangés.\n";
