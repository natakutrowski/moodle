<?php
namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;

class subscription_rollover_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_subscription_rollover', 'local_subscriptions');
    }

    public function execute() {
        global $DB;

        $now = time();

        // Récupère par paquets pour éviter de tout verrouiller
        $batchsize = 500;

        // Sélection des QUEUED à démarrer (on joint les plans pour avoir le scope)
        $sql = "SELECT s.id, s.userid, s.planid, s.start_date, s.end_date, p.accessscopeid
                  FROM {user_subscription} s
                  JOIN {subscription_plan} p ON p.id = s.planid
                 WHERE s.status = '".Status::QUEUED."'
                   AND s.start_date <= :now
              ORDER BY s.start_date ASC, s.id ASC";
        $params = ['now' => $now];

        $rs = $DB->get_recordset_sql($sql, $params, 0, $batchsize);
        foreach ($rs as $row) {
            $this->promote_one((int)$row->id, (int)$row->userid, (int)$row->planid, (int)$row->accessscopeid, $now);
        }
        $rs->close();

        // (Option) Expire passivement les actives déjà terminées, même s'il n'y a pas de queued
        $this->passive_expire($now);
    }

    /**
     * Promeut une subscription QUEUED en ACTIVE si les conditions sont réunies,
     * et expire l'ancienne ACTIVE du même scope (si end_date < now).
     */
    private function promote_one(int $subid, int $userid, int $planid, int $scopeid, int $now): void {
        global $DB;

        $tx = $DB->start_delegated_transaction();

        // Relit l'item pour idempotence et verrou "pauvre" (WHERE status='queued')
        $s = $DB->get_record('user_subscription', ['id' => $subid, 'status' => Status::QUEUED], '*', IGNORE_MISSING);
        if (!$s) { $tx->allow_commit(); return; }

        // Vérifie qu'il n'existe pas une ACTIVE encore en cours sur le scope (lifetime ou chevauchement)
        $sqlactive = "SELECT s2.id, s2.end_date
                        FROM {user_subscription} s2
                        JOIN {subscription_plan} p2 ON p2.id = s2.planid
                       WHERE s2.userid = :uid
                         AND s2.status = '".Status::ACTIVE."'
                         AND p2.accessscopeid = :scope
                       ORDER BY s2.end_date DESC";
        $a = $DB->get_record_sql($sqlactive, ['uid' => $userid, 'scope' => $scopeid], IGNORE_MISSING);

        if ($a) {
            // Si l'active actuelle n'est pas finie, on NE promeut pas
            $isfinished = ((int)$a->end_date > 0 && (int)$a->end_date < $now);
            if (!$isfinished) {
                // Rien à faire ; on laissera la queued démarrer au prochain passage si jamais.
                $tx->allow_commit();
                return;
            }

            // Elle est finie -> passe EXPired
            $DB->update_record('user_subscription', (object)[
                'id' => $a->id,
                'status' => Status::EXPIRED,
                'last_update' => $now,
            ]);
        }

        // Promeut la QUEUED en ACTIVE (double check de la fenêtre de temps)
        $DB->update_record('user_subscription', (object)[
            'id'          => $subid,
            'status'      => Status::ACTIVE,
            'last_update' => $now,
        ]);

        $tx->allow_commit();
    }

    /**
     * Met en 'expired' les subscriptions actives dont end_date < now (sécurité).
     * Idempotent. Limité en lot pour ne pas charger le serveur.
     */
    private function passive_expire(int $now): void {
        global $DB;

        $sql = "SELECT s.id
                  FROM {user_subscription} s
                 WHERE s.status = '".Status::ACTIVE."'
                   AND s.end_date > 0
                   AND s.end_date < :now
              ORDER BY s.id ASC";
        $rs = $DB->get_recordset_sql($sql, ['now' => $now], 0, 500);
        foreach ($rs as $row) {
            $DB->update_record('user_subscription', (object)[
                'id'          => $row->id,
                'status'      => Status::EXPIRED,
                'last_update' => $now,
            ]);
        }
        $rs->close();
    }
}
