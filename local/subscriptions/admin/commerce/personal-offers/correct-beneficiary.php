<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferBeneficiaryCorrectionService;
use local_subscriptions\crm\layout\CrmPageConfigurator;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
$campaignid = required_param('campaignid', PARAM_INT);
$memberid = required_param('memberid', PARAM_INT);
$service = CommercePersonalOfferBeneficiaryCorrectionService::create($DB);
$returnurl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id' => $campaignid]);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/correct-beneficiary.php', ['campaignid' => $campaignid, 'memberid' => $memberid]);
CrmPageConfigurator::configure($PAGE, $context, $url, get_string('commerce_personal_offer_correct_beneficiary', 'local_subscriptions'));

$target = null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $action = required_param('action', PARAM_ALPHA);
        if ($action === 'preview') {
            $email = strtolower(trim(required_param('email', PARAM_EMAIL)));
            $users = array_values($DB->get_records_sql(
                'SELECT * FROM {user} WHERE deleted = 0 AND ' . $DB->sql_equal('email', ':email', false, false),
                ['email' => $email]
            ));
            if (count($users) !== 1) {
                throw new moodle_exception('commerce_personal_offer_correct_beneficiary_user_not_unique', 'local_subscriptions');
            }
            $target = $service->preview($campaignid, $memberid, (int)$users[0]->id);
        } elseif ($action === 'confirm') {
            $targetuserid = required_param('targetuserid', PARAM_INT);
            $preview = $service->preview($campaignid, $memberid, $targetuserid);
            $result = $service->correct($campaignid, $memberid, $targetuserid);
            AdminLog::log(
                AdminEvents::COMMERCE_PERSONAL_OFFER_BENEFICIARY_CORRECTED,
                $targetuserid,
                'commerce_personal_offer',
                (int)$result['offerid'],
                $result
            );
            redirect($returnurl, get_string('commerce_personal_offer_correct_beneficiary_success', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    } catch (Throwable $e) {
        $error = $e instanceof moodle_exception ? $e->getMessage() : $e->getMessage();
    }
}


if (!$service->can_correct($campaignid, $memberid)) {
    redirect($returnurl, get_string('commerce_personal_offer_correct_beneficiary_unavailable', 'local_subscriptions'), null, \core\output\notification::NOTIFY_ERROR);
}
$member = $DB->get_record('local_subs_commerce_offer_campaign_member', ['id' => $memberid, 'campaignid' => $campaignid], '*', MUST_EXIST);
$offer = $DB->get_record('local_subs_commerce_offer', ['id' => (int)$member->offerid], '*', MUST_EXIST);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('commerce_personal_offer_correct_beneficiary', 'local_subscriptions'));
echo html_writer::div(get_string('commerce_personal_offer_correct_beneficiary_help', 'local_subscriptions'), 'alert alert-info');
if ($error !== '') {
    echo html_writer::div(s($error), 'alert alert-danger');
}

echo html_writer::tag('dl',
    html_writer::tag('dt', get_string('commerce_personal_offer_correct_beneficiary_current', 'local_subscriptions')) .
    html_writer::tag('dd', s(trim((string)$member->firstname . ' ' . (string)$member->lastname) . ' <' . (string)$offer->beneficiaryemail . '>')) .
    html_writer::tag('dt', get_string('commerce_personal_offer_id', 'local_subscriptions')) .
    html_writer::tag('dd', '#' . (int)$offer->id),
    ['class' => 'mb-4']
);

if ($target === null) {
    echo html_writer::start_tag('form', ['method' => 'post']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'preview']);
    echo html_writer::tag('label', get_string('commerce_personal_offer_correct_beneficiary_email', 'local_subscriptions'), ['for' => 'id_email', 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['type' => 'email', 'id' => 'id_email', 'name' => 'email', 'required' => 'required', 'class' => 'form-control mb-3', 'autocomplete' => 'off']);
    echo html_writer::tag('button', get_string('commerce_personal_offer_correct_beneficiary_preview', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo ' ' . html_writer::link($returnurl, get_string('cancel'), ['class' => 'btn btn-link']);
    echo html_writer::end_tag('form');
} else {
    $user = $target['user'];
    $old = trim((string)$member->firstname . ' ' . (string)$member->lastname) . ' <' . (string)$offer->beneficiaryemail . '>';
    $new = fullname($user) . ' <' . (string)$user->email . '> (#' . (int)$user->id . ')';
    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_personal_offer_correct_beneficiary_preview_title', 'local_subscriptions')) .
        html_writer::div(s($old) . ' → ' . s($new), 'mt-2'),
        'alert alert-warning'
    );
    echo html_writer::start_tag('form', ['method' => 'post']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'confirm']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'targetuserid', 'value' => (int)$user->id]);
    echo html_writer::tag('button', get_string('commerce_personal_offer_correct_beneficiary_confirm', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-danger']);
    echo ' ' . html_writer::link($url, get_string('back'), ['class' => 'btn btn-link']);
    echo html_writer::end_tag('form');
}
echo $OUTPUT->footer();
