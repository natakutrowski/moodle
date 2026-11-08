<?php
// CampusFR 7.98X.2.2.1 - Lovable CRM upload/preparation tests.

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeLovableUiService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeLovableUiService as LovableUi;

final class commerce_798x221_lovable_ui_test extends \advanced_testcase {
    public function test_identifiers_and_labels_are_validated(): void {
        $ok = LovableUi::validate_identity('articles_a2', 'Cours Articles A2');
        $this->assertSame('articles_a2', $ok['id']);
        $this->assertSame('Cours Articles A2', $ok['label']);
        foreach (['x', '../unsafe', 'Bad_Id', '-bad', '123456', 'bonjour/toto'] as $invalid) {
            try {
                LovableUi::validate_identity($invalid, 'Correct');
                $this->fail('Invalid id accepted: ' . $invalid);
            } catch (\invalid_parameter_exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $this->expectException(\invalid_parameter_exception::class);
        LovableUi::validate_identity('ok_design', '<script>alert(1)</script>');
    }

    public function test_upload_constraints_and_job_keys(): void {
        LovableUi::validate_upload_details('lovable.ZIP', 2048, UPLOAD_ERR_OK);
        $this->assertTrue(LovableUi::valid_job_key(str_repeat('a', 32)));
        $this->assertFalse(LovableUi::valid_job_key('../bad'));
        $this->assertFalse(LovableUi::valid_job_key(str_repeat('A', 32)));
        foreach ([['bad.json', 10, UPLOAD_ERR_OK], ['bad.zip', 0, UPLOAD_ERR_OK],
                ['bad.zip', LovableUi::MAX_UPLOAD_BYTES + 1, UPLOAD_ERR_OK],
                ['bad.zip', 10, UPLOAD_ERR_PARTIAL]] as $test) {
            try {
                LovableUi::validate_upload_details(...$test);
                $this->fail('Invalid upload accepted');
            } catch (\invalid_parameter_exception $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function test_command_never_interpolates_shell_arguments(): void {
        $command = LovableUi::command('/trusted/bridge_lovable.py', '/private/with spaces/lovable.zip',
            'test_design', 'Nom avec un espace et des guillemets "', '/private/output');
        $this->assertContains('/usr/bin/python3', $command);
        $this->assertContains('/private/with spaces/lovable.zip', $command);
        $this->assertContains('Nom avec un espace et des guillemets "', $command);
        $this->assertSame('prepare', $command[6]);
        $this->assertNotContains('sh', $command);
    }

    public function test_valid_fixture_prepares_and_download_bundle_is_source_only(): void {
        global $CFG;
        $folder = make_temp_directory('local_subscriptions/x221_test_' . bin2hex(random_bytes(6)));
        $zipfile = $folder . '/input.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipfile, \ZipArchive::CREATE));
        $zip->addFromString('package.json', json_encode([
            'name' => 'demo', 'version' => '0.1.0', 'dependencies' => ['react' => '^19.0.0', 'react-dom' => '^19.0.0'],
        ]));
        $zip->addFromString('src/App.tsx', 'export default function App() { return <main>Bienvenue sur CampusFR</main>; }');
        $zip->addFromString('src/styles.css', 'main { padding: 10px; }');
        $this->assertTrue($zip->close());
        $this->assertFileExists($zipfile);
        LovableUi::assert_zip_file($zipfile);
        $report = LovableUi::prepare($zipfile, 'demo_lyon', 'Démo Lyon', $folder,
            $CFG->dirroot . '/local/subscriptions/cli/bridge_lovable.py');
        $this->assertSame('PREPARED_SOURCE_ONLY', $report['status']);
        $this->assertFalse($report['approved']);
        $this->assertSame('demo_lyon', $report['designId']);
        $bundle = $folder . '/campusfr-lovable-demo_lyon-a-revoir.zip';
        $this->assertFileExists($bundle);
        // Moodle temp can be below /var/www; source preparation must never be stored there.
        $this->assertDirectoryDoesNotExist($folder . '/workspace');
        $readzip = new \ZipArchive();
        $this->assertTrue($readzip->open($bundle));
        $this->assertNotFalse($readzip->locateName('source/src/bridge/campusfr-entry.tsx'));
        $this->assertNotFalse($readzip->locateName('README_CAMPUSFR_X22.md'));
        $this->assertNotFalse($readzip->locateName('campusfr-inspection.json'));
        $this->assertFalse($readzip->locateName('dist/main.js'));
        $readzip->close();
        remove_dir($folder);
    }

    public function test_bad_zip_is_refused_without_executing_any_source(): void {
        global $CFG;
        $folder = make_temp_directory('local_subscriptions/x221_bad_' . bin2hex(random_bytes(6)));
        $zipfile = $folder . '/bad.zip';
        $zip = new \ZipArchive();
        $zip->open($zipfile, \ZipArchive::CREATE);
        $zip->addFromString('../escape.txt', 'Not allowed');
        $zip->addFromString('package.json', '{"dependencies":{"react":"19"}}');
        $zip->addFromString('src/App.tsx', 'export default () => <h1>Bad</h1>;');
        $zip->addFromString('src/styles.css', 'body {}');
        $zip->close();
        try {
            LovableUi::prepare($zipfile, 'bad_demo', 'Bad demo', $folder,
                $CFG->dirroot . '/local/subscriptions/cli/bridge_lovable.py');
            $this->fail('Unsafe ZIP was accepted');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Préparation refusée', $e->getMessage());
            $this->assertDirectoryDoesNotExist($folder . '/workspace');
        } finally {
            remove_dir($folder);
        }
    }

    public function test_web_download_loads_moodle_filelib_and_forces_zip_download(): void {
        global $CFG;
        // The Moodle bootstrap alone may not load send_file().
        require_once($CFG->libdir . '/filelib.php');
        $this->assertTrue(function_exists('send_file'));
        $wizard = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/lovable.php');
        $this->assertStringContainsString("require_once(\$CFG->libdir . '/filelib.php');", $wizard);
        $this->assertStringContainsString("send_file(\$file, 'campusfr-lovable-' . \$id . '-a-revoir.zip', 0, 0, false, true, 'application/zip');", $wizard);
        $this->assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $wizard);
        $this->assertStringContainsString('require_sesskey();', $wizard);
        $this->assertStringNotContainsString('Bridge X2.2', $wizard);
        $this->assertStringNotContainsString('Articles / Alf', $wizard);
        $this->assertStringContainsString("html_writer::start_tag('ol'", $wizard);
    }

    public function test_listing_button_exists_and_is_not_a_payment_action(): void {
        global $CFG;
        $index = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/index.php');
        $this->assertStringContainsString("'/local/subscriptions/admin/commerce/showrooms/lovable.php'", $index);
        $wizard = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/lovable.php');
        $this->assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $wizard);
        $this->assertStringContainsString('require_sesskey();', $wizard);
        $this->assertStringNotContainsString('install-dev', $wizard);
    }
}
