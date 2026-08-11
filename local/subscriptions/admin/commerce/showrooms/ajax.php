<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewZipImporter;

require_login();
require_sesskey();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());

header('Content-Type: application/json; charset=utf-8');

$action = required_param('action', PARAM_ALPHANUMEXT);
$showroomid = required_param('showroomid', PARAM_INT);
$repository = new CommerceShowroomCmsRepository($DB);
$mediamanager = new CommerceShowroomBlockMediaManager(
    context_system::instance()
);
$exercisepreviewmanager = new CommerceShowroomExercisePreviewMediaManager(
    context_system::instance(),
    $mediamanager
);
$showroom = $repository->get($showroomid);
if ($showroom === null) {
    throw new moodle_exception('invalidrecord');
}

try {
    $result = ['ok' => true, 'schemas' => CommerceShowroomBlockEditorRegistry::schemas()];
    switch ($action) {
        case 'add':
            $blocktype = required_param('blocktype', PARAM_ALPHANUMEXT);
            if (!CommerceShowroomBlockTypeRegistry::exists($blocktype)) {
                throw new invalid_parameter_exception('Unsupported block type.');
            }
            $blockid = $repository->save_block($showroomid, [
                'blocktype' => $blocktype,
                'enabled' => true,
                'configjson' => '{}',
            ], (int)$USER->id);
            $result['block'] = $repository->get_block($blockid);
            break;

        case 'update':
            $blockid = required_param('blockid', PARAM_INT);
            $existing = $repository->get_block($blockid);
            if ($existing === null) {
                throw new invalid_parameter_exception('Unknown block.');
            }
            $rawconfig = optional_param('configjson', '{}', PARAM_RAW);
            $decodedconfig = json_decode(
                $rawconfig,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            if (!is_array($decodedconfig) || array_is_list($decodedconfig)) {
                throw new invalid_parameter_exception(
                    'The Showroom block configuration must be a JSON object.'
                );
            }

            $advancedjson = optional_param('advancedjson', 0, PARAM_BOOL);
            $savedconfig = $advancedjson
                ? $decodedconfig
                : CommerceShowroomBlockEditorRegistry::normalise(
                    (string)$existing->blocktype,
                    $decodedconfig
                );

            $repository->save_block($showroomid, [
                'id' => $blockid,
                'blocktype' => $existing->blocktype,
                'blockkey' => required_param('blockkey', PARAM_ALPHANUMEXT),
                'sortorder' => (int)$existing->sortorder,
                'enabled' => optional_param('enabled', 0, PARAM_BOOL),
                'configjson' => (string)json_encode(
                    $savedconfig,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                ),
            ], (int)$USER->id);
            $result['block'] = $repository->get_block($blockid);
            break;

        case 'reorder':
            $raw = required_param('blockids', PARAM_RAW);
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new invalid_parameter_exception('Invalid block order.');
            }
            $repository->reorder_blocks($showroomid, $decoded, (int)$USER->id);
            break;

        case 'toggle':
            $enabled = required_param('enabled', PARAM_BOOL);

            $repository->set_block_enabled(
                $showroomid,
                required_param('blockid', PARAM_INT),
                (bool)$enabled,
                (int)$USER->id
            );
            break;

        case 'duplicate':
            $sourceblockid = required_param('blockid', PARAM_INT);
            $blockid = $repository->duplicate_block(
                $showroomid,
                $sourceblockid,
                (int)$USER->id
            );
            $mapping = $mediamanager->duplicate_block(
                $sourceblockid,
                $blockid
            );
            if ($mapping !== []) {
                $duplicated = $repository->get_block($blockid);
                $config = json_decode(
                    (string)$duplicated->configjson,
                    true
                );
                $config = is_array($config) ? $config : [];
                $config = $mediamanager->remap_config_urls(
                    $config,
                    $mapping
                );
                $repository->save_block($showroomid, [
                    'id' => $blockid,
                    'blocktype' => (string)$duplicated->blocktype,
                    'blockkey' => (string)$duplicated->blockkey,
                    'sortorder' => (int)$duplicated->sortorder,
                    'enabled' => (int)$duplicated->enabled === 1,
                    'configjson' => json_encode(
                        $config,
                        JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                            | JSON_THROW_ON_ERROR
                    ),
                ], (int)$USER->id);
            }
            $result['block'] = $repository->get_block($blockid);
            break;

        case 'delete':
            $blockid = required_param('blockid', PARAM_INT);
            $repository->delete_block($showroomid, $blockid);
            break;

        case 'uploadmedia':
            $blockid = required_param('blockid', PARAM_INT);
            $field = required_param('field', PARAM_ALPHANUMEXT);
            $block = $repository->get_block($blockid);
            if (
                $block === null
                || (int)$block->showroomid !== $showroomid
                || !CommerceShowroomBlockEditorRegistry::is_media_field(
                    (string)$block->blocktype,
                    $field
                )
            ) {
                throw new invalid_parameter_exception(
                    'Unsupported Showroom media field.'
                );
            }

            $mediamanager->store_uploaded_media(
                $blockid,
                $field,
                'media'
            );
            $url = $mediamanager->get_url($blockid, $field);
            if ($url === null) {
                throw new moodle_exception('error_uploading_file', 'moodle');
            }

            $config = json_decode((string)$block->configjson, true);
            $config = is_array($config) ? $config : [];
            $config[$field] = $url->out(false);
            $repository->save_block($showroomid, [
                'id' => $blockid,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => (int)$block->enabled === 1,
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                ),
            ], (int)$USER->id);

            $result['url'] = $url->out(false);
            $result['filename'] = $mediamanager
                ->get_file($blockid, $field)
                ?->get_filename();
            break;

        case 'deletemedia':
            $blockid = required_param('blockid', PARAM_INT);
            $field = required_param('field', PARAM_ALPHANUMEXT);
            $block = $repository->get_block($blockid);
            if (
                $block === null
                || (int)$block->showroomid !== $showroomid
                || !CommerceShowroomBlockEditorRegistry::is_media_field(
                    (string)$block->blocktype,
                    $field
                )
            ) {
                throw new invalid_parameter_exception(
                    'Unsupported Showroom media field.'
                );
            }

            $mediamanager->delete_field($blockid, $field);
            $config = json_decode((string)$block->configjson, true);
            $config = is_array($config) ? $config : [];
            $config[$field] = '';
            $repository->save_block($showroomid, [
                'id' => $blockid,
                'blocktype' => (string)$block->blocktype,
                'blockkey' => (string)$block->blockkey,
                'sortorder' => (int)$block->sortorder,
                'enabled' => (int)$block->enabled === 1,
                'configjson' => json_encode(
                    $config,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_THROW_ON_ERROR
                ),
            ], (int)$USER->id);
            $result['url'] = '';
            break;

        case 'uploadexercisepreview':
            $blockid = required_param('blockid', PARAM_INT);
            $exercisekey = required_param('exercisekey', PARAM_ALPHANUMEXT);
            $language = required_param('language', PARAM_ALPHANUMEXT);
            $block = $repository->get_block($blockid);
            if (
                $block === null
                || (int)$block->showroomid !== $showroomid
                || (string)$block->blocktype !== 'exercise_explorer'
                || !CommerceShowroomExerciseCatalog::exists($exercisekey)
                || !in_array($language, CommerceShowroomExercisePreviewMediaManager::LANGUAGES, true)
            ) {
                throw new invalid_parameter_exception('Unsupported Exercise Explorer preview slot.');
            }

            $exercisepreviewmanager->store_uploaded_media(
                $blockid,
                $exercisekey,
                $language,
                'media'
            );
            $url = $exercisepreviewmanager->get_url($blockid, $exercisekey, $language);
            if ($url === null) {
                throw new moodle_exception('error_uploading_file', 'moodle');
            }
            $result['url'] = $url->out(false);
            $result['language'] = $language;
            $result['exercisekey'] = $exercisekey;
            break;

        case 'deleteexercisepreview':
            $blockid = required_param('blockid', PARAM_INT);
            $exercisekey = required_param('exercisekey', PARAM_ALPHANUMEXT);
            $language = required_param('language', PARAM_ALPHANUMEXT);
            $block = $repository->get_block($blockid);
            if (
                $block === null
                || (int)$block->showroomid !== $showroomid
                || (string)$block->blocktype !== 'exercise_explorer'
                || !CommerceShowroomExerciseCatalog::exists($exercisekey)
                || !in_array($language, CommerceShowroomExercisePreviewMediaManager::LANGUAGES, true)
            ) {
                throw new invalid_parameter_exception('Unsupported Exercise Explorer preview slot.');
            }

            $exercisepreviewmanager->delete($blockid, $exercisekey, $language);
            $result['url'] = '';
            $result['language'] = $language;
            $result['exercisekey'] = $exercisekey;
            break;

        case 'importexercisezip':
            $blockid = required_param('blockid', PARAM_INT);
            $language = required_param('language', PARAM_ALPHANUMEXT);
            $block = $repository->get_block($blockid);
            if (
                $block === null
                || (int)$block->showroomid !== $showroomid
                || (string)$block->blocktype !== 'exercise_explorer'
                || !in_array($language, CommerceShowroomExercisePreviewMediaManager::LANGUAGES, true)
            ) {
                throw new invalid_parameter_exception('Unsupported Exercise Explorer ZIP target.');
            }
            if (
                !isset($_FILES['archive'])
                || (int)($_FILES['archive']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
            ) {
                throw new invalid_parameter_exception('No Exercise Explorer ZIP was uploaded.');
            }
            $upload = $_FILES['archive'];
            if ((int)($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new moodle_exception('error_uploading_file', 'moodle');
            }
            $tmpname = (string)($upload['tmp_name'] ?? '');
            if ($tmpname === '' || !is_uploaded_file($tmpname)) {
                throw new moodle_exception('error_uploading_file', 'moodle');
            }
            if (strtolower(pathinfo((string)($upload['name'] ?? ''), PATHINFO_EXTENSION)) !== 'zip') {
                throw new invalid_parameter_exception('Exercise Explorer batch must be a ZIP archive.');
            }

            $importer = new CommerceShowroomExercisePreviewZipImporter($exercisepreviewmanager);
            $report = $importer->import($blockid, $tmpname, $language, false);
            $result['report'] = $report;
            $result['media'] = [];
            foreach (CommerceShowroomExerciseCatalog::keys() as $key) {
                $sloturl = $exercisepreviewmanager->get_url($blockid, $key, $language);
                $result['media'][$key] = $sloturl?->out(false) ?? '';
            }
            break;

        case 'applytemplate':
            $repository->apply_template(
                $showroomid,
                required_param('templatekey', PARAM_ALPHANUMEXT),
                (int)$USER->id
            );
            break;

        case 'initialisedefaults':
            $result['updated'] = $repository->initialise_block_defaults(
                $showroomid,
                (int)$USER->id
            );
            break;

        case 'duplicateshowroom':
            $result['showroomid'] = $repository->duplicate_showroom($showroomid, (int)$USER->id);
            break;

        default:
            throw new invalid_parameter_exception('Unsupported action.');
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}
