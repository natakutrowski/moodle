<?php
namespace local_subscriptions\task;

use local_subscriptions\constants\Status;
use local_subscriptions\mailer;

defined('MOODLE_INTERNAL') || die();

class followup_task extends \core\task\scheduled_task {
    public function get_name(): string { return get_string('task_followup', 'local_subscriptions'); }

    public function execute() {
        global $DB;

        $now = time();

        // Réglages.
        $expireMin = (int)(get_config('local_subscriptions','expire_pending_after_minutes') ?: 60);
        $r1Min     = (int)(get_config('local_subscriptions','reminder1_after_minutes') ?: 1440);
        $r2Min     = (int)(get_config('local_subscriptions','reminder2_after_minutes') ?: 4320);

        $ageToExpire = $expireMin * 60;
        $ageToR1     = $r1Min * 60;
        $ageToR2     = $r2Min * 60;

        // Délai minimal entre R1 et R2 : diff des seuils, avec plancher 5 min.
        $gapR1R2 = max(($r2Min - $r1Min) * 60, 5 * 60);

        // A) Expirer les pending trop anciens (aucun email ici).
        $prsA = $DB->get_records_select('subscription_payment_request',
            "status='".Status::PENDING."' AND :now - creation_date >= :age",
            ['now' => $now, 'age' => $ageToExpire]
        );
        foreach ($prsA as $pr) {
            $pr->status = Status::EXPIRED;
            $pr->last_attempt = $now;
            $DB->update_record('subscription_payment_request', $pr);
            // Pas d’email ici → R1 s’en chargera selon l’âge.
        }

        // B) Rappels selon âge + étape.
        // On cible tous les statuts à relancer.
        $prs = $DB->get_records_select('subscription_payment_request',
            "status IN ('".Status::PENDING."','".Status::FAILED."','".Status::EXPIRED."')", []);

        foreach ($prs as $pr) {
            $age = $now - (int)$pr->creation_date;

            // Déjà R2 envoyée -> plus rien à faire.
            if ((int)$pr->reminder_stage >= 2) {
                continue;
            }

            // Cas: aucune relance encore (stage 0).
            if ((int)$pr->reminder_stage === 0) {
                if ($age >= $ageToR2) {
                    // Envoie R2 uniquement (pas de double envoi avec R1).
                    mailer::dispatch(
                        mailer::T_REMINDER_SECOND,[
                            'pr' => $pr
                        ]
                    );

                    $pr->reminder_stage = 2;
                    $pr->reminder2_at   = $now;
                    // Optionnel: si encore pending à ce stade, force expired
                    if ($pr->status === Status::PENDING) {
                        $pr->status = Status::EXPIRED;
                    }
                    $DB->update_record('subscription_payment_request', $pr);
                    continue;
                }
                if ($age >= $ageToR1) {
                    mailer::dispatch(
                        mailer::T_REMINDER_FIRST,[
                            'pr' => $pr
                        ]
                    );

                    $pr->reminder_stage = 1;
                    $pr->reminder1_at   = $now;
                    $DB->update_record('subscription_payment_request', $pr);
                    continue;
                }
                continue;
            }

            // Cas: R1 déjà envoyée (stage 1) -> attendre seuil R2 ET un gap depuis R1.
            if ((int)$pr->reminder_stage === 1) {
                $sinceR1 = $pr->reminder1_at ? ($now - (int)$pr->reminder1_at) : PHP_INT_MAX;

                if ($age >= $ageToR2 && $sinceR1 >= $gapR1R2) {
                    \local_subscriptions\mailer::send_reminder_second($pr); // R2
                    $pr->reminder_stage = 2;
                    $pr->reminder2_at   = $now;
                    if ($pr->status === Status::PENDING) {
                        $pr->status = Status::EXPIRED;
                    }
                    $DB->update_record('subscription_payment_request', $pr);
                }
            }
        }
    }


}
