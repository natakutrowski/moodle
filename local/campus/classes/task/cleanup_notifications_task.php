<?php
// local/campus/classes/task/cleanup_notifications_task.php

namespace local_campus\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Tâche planifiée pour nettoyer certaines notifications système.
 *
 * Elle marque comme lues (timeread) les notifications "availableupdate" et "newlogin"
 * du composant "moodle", afin d'éviter des badges de notifications bloqués
 * par des messages peu utiles dans la cloche.
 */
class cleanup_notifications_task extends \core\task\scheduled_task {

    /**
     * Nom lisible de la tâche (affiché dans l'administration).
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_notifications', 'local_campus');
    }

    /**
     * Exécution de la tâche.
     */
    public function execute(): void {
        global $DB;

        $now = time();

        // Par prudence, on ne nettoie que les notifs de plus de 7 jours.
        $threshold = $now - 7 * DAYSECS;

        $params = [
            'now'       => $now,
            'component' => 'moodle',
            'threshold' => $threshold,
        ];

        // Pour éviter les "IN" dynamiques compliqués, on écrit les eventtypes en dur.
        $select = "(timeread = 0 OR timeread IS NULL)
                   AND component = :component
                   AND eventtype IN ('availableupdate', 'newlogin')
                   AND timecreated < :threshold";

        // Juste pour logguer le nombre avant l'update (facultatif).
        $count = $DB->count_records_select('notifications', $select, $params);

        if ($count > 0) {
            mtrace("[local_campus] cleanup_notifications_task: $count notification(s) à nettoyer.");

            $DB->execute("UPDATE {notifications}
                             SET timeread = :now
                           WHERE $select", $params);

            mtrace("[local_campus] cleanup_notifications_task: nettoyage terminé.");
        } else {
            mtrace("[local_campus] cleanup_notifications_task: rien à nettoyer.");
        }
    }
}
