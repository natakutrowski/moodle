<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerEmailChangeService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);
$userid = required_param('id', PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$url = new moodle_url(subscription_config::admin_user_change_email_page(), ['id' => $userid]);
$returnurl = new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]);
$title = get_string('commerce_customer_email_change_title', 'local_subscriptions');

CrmPageConfigurator::configure($PAGE, $context, $url, $title, [
    'local-subscriptions-user-profile-page',
    'local-subscriptions-user-change-email-page',
]);

class local_subscriptions_change_email_form extends moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('text', 'newemail', get_string('commerce_customer_email_change_newemail', 'local_subscriptions'));
        $mform->setType('newemail', PARAM_EMAIL);
        $mform->addRule('newemail', null, 'required', null, 'client');
        $mform->addElement(
            'select',
            'newlang',
            get_string('commerce_customer_email_change_newlang', 'local_subscriptions'),
            get_string_manager()->get_list_of_translations()
        );
        $mform->addElement('advcheckbox', 'confirmchange', get_string('commerce_customer_email_change_confirm', 'local_subscriptions'));
        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'previewbutton', get_string('commerce_customer_email_change_preview', 'local_subscriptions'));
        $buttons[] = $mform->createElement('submit', 'executebutton', get_string('commerce_customer_email_change_execute', 'local_subscriptions'), ['class' => 'btn btn-danger']);
        $buttons[] = $mform->createElement('cancel');
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
    }
}

$service = new CommerceCustomerEmailChangeService($DB);
$form = new local_subscriptions_change_email_form($url);
$form->set_data(['id' => $userid, 'newemail' => (string)$user->email, 'newlang' => (string)$user->lang]);
$error = null;
$preview = null;

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    require_sesskey();
    try {
        $preview = $service->preview($userid, (string)$data->newemail, (string)$data->newlang);
        if (!empty($data->executebutton)) {
            if (empty($data->confirmchange)) {
                throw new moodle_exception('commerce_customer_email_change_confirmation_required', 'local_subscriptions');
            }
            $service->change($userid, (string)$data->newemail, (int)$USER->id, (string)$data->newlang);
            redirect($returnurl, get_string('commerce_customer_email_change_success', 'local_subscriptions'));
        }
        $form->set_data($data);
    } catch (\Throwable $exception) {
        $error = $exception->getMessage();
        $form->set_data($data);
    }
} else {
    try {
        $preview = $service->preview($userid, (string)$user->email, (string)$user->lang);
    } catch (\Throwable $ignored) {
        $preview = null;
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::USERS, $context);
echo CrmBackLinkRenderer::render($returnurl, get_string('back'));
echo CrmPageHeader::render($title, get_string('commerce_customer_email_change_description', 'local_subscriptions'), HelpContext::USER_PROFILE);

echo html_writer::div(
    get_string('commerce_customer_email_change_current', 'local_subscriptions', (object)['email' => (string)$user->email]),
    'alert alert-light border'
);
if ($error !== null) {
    echo html_writer::div(s($error), 'alert alert-danger');
}

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_customer_email_change_scope_title', 'local_subscriptions'), ['class' => 'h6']);
echo html_writer::tag('ul',
    html_writer::tag('li', get_string('commerce_customer_email_change_scope_current', 'local_subscriptions')) .
    html_writer::tag('li', get_string('commerce_customer_email_change_scope_history', 'local_subscriptions')),
    ['class' => 'mb-0']
);
echo html_writer::end_div();

$form->display();
if ($preview !== null && (!empty($preview['emailchanged']) || !empty($preview['langchanged']))) {
    echo html_writer::start_div('card card-body mt-4 border-primary');
    echo html_writer::tag('h3', get_string('commerce_customer_email_change_preview_title', 'local_subscriptions'), ['class' => 'h6 mb-3']);

    $changes = [];
    if (!empty($preview['emailchanged'])) {
        $changes[] = html_writer::tag('li',
            html_writer::tag('strong', get_string('commerce_customer_email_change_preview_email', 'local_subscriptions'))
            . ': ' . s($preview['oldemail']) . ' → ' . s($preview['newemail'])
        );
    }
    if (!empty($preview['langchanged'])) {
        $translations = get_string_manager()->get_list_of_translations();
        $oldlanglabel = $translations[$preview['oldlang']] ?? $preview['oldlang'];
        $newlanglabel = $translations[$preview['newlang']] ?? $preview['newlang'];
        $changes[] = html_writer::tag('li',
            html_writer::tag('strong', get_string('commerce_customer_email_change_preview_language', 'local_subscriptions'))
            . ': ' . s($oldlanglabel) . ' → ' . s($newlanglabel)
        );
    }
    echo html_writer::tag('ul', implode('', $changes), ['class' => 'mb-3']);

    echo html_writer::tag('h4', get_string('commerce_customer_email_change_preview_impacted_title', 'local_subscriptions'), ['class' => 'h6 mb-2']);
    $impactlabels = [
        'local_subs_commerce_grant' => 'commerce_customer_email_change_impact_grants',
        'local_subs_commerce_dig_access' => 'commerce_customer_email_change_impact_digital',
        'local_subs_commerce_offer' => 'commerce_customer_email_change_impact_offers',
        'local_subs_commerce_offer_campaign_member' => 'commerce_customer_email_change_impact_offer_campaigns',
        'local_subs_commerce_grant_campaign_member' => 'commerce_customer_email_change_impact_grant_campaigns',
        'local_subs_commerce_guest' => 'commerce_customer_email_change_impact_guests',
    ];
    $impactrows = [];
    if (!empty($preview['emailchanged'])) {
        $impactrows[] = html_writer::tag('li', get_string('commerce_customer_email_change_impact_moodle_email', 'local_subscriptions'));
    }
    if (!empty($preview['langchanged'])) {
        $impactrows[] = html_writer::tag('li', get_string('commerce_customer_email_change_impact_moodle_language', 'local_subscriptions'));
    }
    if (!empty($preview['emailchanged'])) {
        foreach ($preview['current'] as $table => $count) {
            if ((int)$count <= 0 || !isset($impactlabels[$table])) {
                continue;
            }
            $impactrows[] = html_writer::tag('li', get_string($impactlabels[$table], 'local_subscriptions', (int)$count));
        }
    }
    echo html_writer::tag('ul', implode('', $impactrows), ['class' => 'mb-3']);

    echo html_writer::tag('h4', get_string('commerce_customer_email_change_preview_preserved_title', 'local_subscriptions'), ['class' => 'h6 mb-2']);
    $historylabels = [
        'local_subscriptions_commerce_purchase' => 'commerce_customer_email_change_history_purchases',
        'subscription_payment_request' => 'commerce_customer_email_change_history_payments',
        'subscription_digital_payment_request' => 'commerce_customer_email_change_history_digital_payments',
    ];
    $historyrows = [];
    foreach ($preview['historical'] as $table => $count) {
        if ((int)$count <= 0 || !isset($historylabels[$table])) {
            continue;
        }
        $historyrows[] = html_writer::tag('li', get_string($historylabels[$table], 'local_subscriptions', (int)$count));
    }
    if ($historyrows === []) {
        $historyrows[] = html_writer::tag('li', get_string('commerce_customer_email_change_history_none', 'local_subscriptions'));
    }
    echo html_writer::tag('ul', implode('', $historyrows), ['class' => 'mb-0 text-muted']);
    echo html_writer::end_div();
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
