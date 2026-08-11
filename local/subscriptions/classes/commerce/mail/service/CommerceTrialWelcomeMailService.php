<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

final class CommerceTrialWelcomeMailService {

    /** @param array<string,mixed> $payload */
    public function queue(array $payload): \stdClass {
        global $DB;

        $email = trim((string)($payload['toemail'] ?? $payload['email'] ?? ''));
        if ($email === '' || !validate_email($email)) {
            throw new \coding_exception('Trial Welcome requires a valid recipient email.');
        }

        $user = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MISSING);
        $firstname = trim((string)($payload['firstname'] ?? ($user->firstname ?? '')));
        $fullname = $user !== false ? fullname($user) : $firstname;
        $userid = $user !== false ? (int)$user->id : null;

        $language = clean_param(
            (string)($payload['lang'] ?? ($user->lang ?? current_language())),
            PARAM_LANG
        ) ?: 'fr';

        $trialurl = trim((string)($payload['mycourses_url'] ?? $payload['login_url'] ?? ''));
        if ($trialurl === '') {
            $trialurl = (new \moodle_url('/mes-cours'))->out(false);
        }

        $reseturl = trim((string)($payload['reset_url'] ?? ''));
        if ($reseturl === '') {
            $reseturl = (new \moodle_url('/login/forgot_password.php'))->out(false);
        }

        $marker = trim((string)(
            $payload['trialid']
            ?? $payload['trial_id']
            ?? $payload['accessscopeid']
            ?? $payload['start_date']
            ?? $payload['started_at']
            ?? ''
        ));
        $identity = strtolower($email) . ':' . ($marker !== '' ? $marker : 'first-trial');

        $request = new CommerceMailRequest(
            CommerceMailType::TRIAL_WELCOME,
            new CommerceMailRecipient($email, $fullname, $userid),
            new CommerceMailContext([
                'customer' => [
                    'firstname' => $firstname,
                    'fullname' => $fullname,
                ],
                'accountemail' => $email,
                'trialurl' => $trialurl,
                'reseturl' => $reseturl,
                'trial' => [
                    'id' => $marker,
                    'source' => trim((string)($payload['source'] ?? 'legacy_trial')),
                    'coursefullname' => trim((string)($payload['course_fullname'] ?? '')),
                ],
                'links' => [
                    'campus' => (new \moodle_url('/mon-campus'))->out(false),
                    'courses' => $trialurl,
                ],
            ]),
            $language,
            CommerceMailIdempotencyKey::normalise(
                'trial-welcome:' . hash('sha256', $identity)
            )
        );

        return CommerceMailRuntime::queue_service()->queue($request);
    }

    /** @param array<string,mixed> $payload */
    public function queue_and_send(array $payload): \stdClass {
        $record = $this->queue($payload);
        CommerceMailRuntime::processor()->process_ids([(int)$record->id]);
        return $record;
    }
}
