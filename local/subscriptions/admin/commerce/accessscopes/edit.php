<?php
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_form.php');
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
$context=AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);$id=optional_param('id',0,PARAM_INT);$record=$id?$DB->get_record('subscription_access_scope',['id'=>$id],'*',MUST_EXIST):null;
$pageurl=new moodle_url('/local/subscriptions/admin/commerce/accessscopes/edit.php',$id?['id'=>$id]:[]);$title=$id?get_string('commerce_scope_edit','local_subscriptions'):get_string('commerce_scope_add','local_subscriptions');
CrmPageConfigurator::configure($PAGE,$context,$pageurl,$title,'local-subscriptions-commerce-scope-edit-page');$form=new access_scope_form($pageurl,$record?(array)$record:[]);if($record){$form->set_data(['id'=>$record->id,'name'=>$record->name,'course_ids'=>array_filter(array_map('intval',explode(',',(string)$record->course_ids)))]);}if($form->is_cancelled()){redirect(new moodle_url('/local/subscriptions/admin/commerce/accessscopes/index.php'));}if($data=$form->get_data()){$save=(object)['id'=>(int)($data->id??0),'name'=>trim($data->name),'course_ids'=>implode(',',array_map('intval',(array)$data->course_ids)),'last_update'=>time()];if($save->id){$DB->update_record('subscription_access_scope',$save);}else{$save->creation_date=time();$save->id=$DB->insert_record('subscription_access_scope',$save);}redirect(new moodle_url('/local/subscriptions/admin/commerce/accessscopes/view.php',['id'=>$save->id]),get_string('changessaved'));}
echo $OUTPUT->header();echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE,$context);echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo $OUTPUT->heading($title);$form->display();echo CrmWorkspaceRenderer::end();echo $OUTPUT->footer();
