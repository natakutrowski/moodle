
<?php
// CampusFR CR.7: read-only Moodle CLI tool for identifying test accounts.
// Install this file in local/subscriptions/cli/crm/cr7_find_test_accounts.php.
define('CLI_SCRIPT', true);
require_once(dirname(__DIR__, 4) . '/config.php');

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

// Search only likely test identities; manual validation is still essential.
$pattern = 'test|demo|sandbox|dev|qa';
$sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.auth, u.lastaccess,
               COUNT(DISTINCT CASE
                   WHEN ctx.contextlevel = :coursecontext1 AND r.shortname = :student
                   THEN ctx.instanceid END) AS cours_student,
               COUNT(DISTINCT CASE
                   WHEN ctx.contextlevel = :coursecontext2 AND r.shortname <> :student2
                   THEN CONCAT(ctx.instanceid, ':', r.id) END) AS autres_roles_cours
          FROM {user} u
     LEFT JOIN {role_assignments} ra ON ra.userid = u.id
     LEFT JOIN {context} ctx ON ctx.id = ra.contextid
     LEFT JOIN {role} r ON r.id = ra.roleid
         WHERE u.deleted = 0
           AND u.suspended = 0
           AND u.id > 1
           AND (
               LOWER(u.username) REGEXP :patternuser
               OR LOWER(u.firstname) REGEXP :patternfirst
               OR LOWER(u.lastname) REGEXP :patternlast
           )
           AND NOT EXISTS (
               SELECT 1 FROM {role_assignments} sysra
               JOIN {context} sysctx ON sysctx.id = sysra.contextid
               WHERE sysra.userid = u.id AND sysctx.contextlevel = :systemcontext
           )
      GROUP BY u.id, u.username, u.firstname, u.lastname, u.auth, u.lastaccess
      ORDER BY cours_student DESC, u.lastaccess DESC";

$params = [
    'coursecontext1' => CONTEXT_COURSE,
    'student' => 'student',
    'coursecontext2' => CONTEXT_COURSE,
    'student2' => 'student',
    'patternuser' => $pattern,
    'patternfirst' => $pattern,
    'patternlast' => $pattern,
    'systemcontext' => CONTEXT_SYSTEM,
];

// Fetch extra rows so admin filtering does not truncate the result prematurely.
$users = $DB->get_records_sql($sql, $params, 0, 100);

printf("%-7s %-30s %-13s %-13s %-14s %s\n",
    'ID', 'USERNAME', 'STUDENT_COURS', 'AUTRES_COURS', 'AUTH', 'DERNIER_ACCES');
printf("%s\n", str_repeat('-', 100));
$count = 0;
foreach ($users as $user) {
    if (is_siteadmin((int)$user->id)) {
        continue;
    }
    // Keep printable one-line values for copy/paste.
    $username = preg_replace('/[\r\n\t]+/u', ' ', $user->username);
    $auth = preg_replace('/[\r\n\t]+/u', ' ', $user->auth);
    printf("%-7d %-30s %-13d %-13d %-14s %s\n",
        (int)$user->id,
        $username,
        (int)$user->cours_student,
        (int)$user->autres_roles_cours,
        $auth,
        empty($user->lastaccess) ? 'Jamais' : date('Y-m-d', (int)$user->lastaccess)
    );
    if (++$count >= 30) {
        break;
    }
}
if ($count === 0) {
    echo "Aucun compte candidat. Élargis le motif de recherche dans le script, sans modifier les rôles des utilisateurs.\n";
}
echo "\nLecture seule : aucune modification des comptes ou des rôles.\n";
