<?php
// X2.3A: safe CRM launch/status for isolated Lovable preview compilation.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
require_once(__DIR__ . '/../../../classes/task/lovable_build_task.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCompileService as Builder;

final class commerce_798x23_lovable_compile_test extends \advanced_testcase {
    public function test_identifiers_reject_path_traversal_and_nonrandom_keys(): void {
        self::assertTrue(Builder::valid_key(str_repeat('a', 32)));
        self::assertFalse(Builder::valid_key('../bad'));
        self::assertFalse(Builder::valid_key(str_repeat('X',32)));
        self::assertTrue(Builder::valid_id('articles_alf_reimport'));
        self::assertFalse(Builder::valid_id('../articles'));
        self::assertFalse(Builder::valid_id('ARTICLES_ALF'));
    }

    public function test_job_folder_never_accepts_untrusted_path(): void {
        $this->expectException(\invalid_parameter_exception::class);
        Builder::job_dir(5, '../../evil');
    }

    public function test_verify_session_rejects_foreign_user(): void {
        $key = str_repeat('b',32);
        $this->expectException(\invalid_parameter_exception::class);
        Builder::verify_session(['key'=>$key,'userid'=>44,'id'=>'test_design','created'=>time()], 45, $key);
    }

    public function test_verify_session_rejects_expired_job(): void {
        $key = str_repeat('c',32);
        $this->expectException(\invalid_parameter_exception::class);
        Builder::verify_session(['key'=>$key,'userid'=>44,'id'=>'test_design',
            'created'=>time() - Builder::MAX_JOB_AGE - 5], 44, $key);
    }

    public function test_status_roundtrip_and_atomic_file(): void {
        $dir = make_temp_directory('local_subscriptions/x23_test_' . bin2hex(random_bytes(6)));
        Builder::write_status($dir, ['state'=>'queued','userid'=>24,'designId'=>'test_design','started'=>time()]);
        $record = Builder::get_status($dir);
        self::assertSame('queued', $record['state']);
        self::assertSame(24, $record['userid']);
        Builder::write_status($dir, ['state'=>'ready','userid'=>24,'designId'=>'test_design']);
        self::assertSame('ready', Builder::get_status($dir)['state']);
        remove_dir($dir);
    }

    public function test_queue_refuses_unapproved_path_before_any_task(): void {
        $this->expectException(\invalid_parameter_exception::class);
        Builder::queue(24, str_repeat('a', 32), 'test_design', '/tmp/arbitrary');
    }

    public function test_task_is_adhoc_and_uses_fixed_service(): void {
        self::assertTrue(is_subclass_of(\local_subscriptions\task\lovable_build_task::class,
            \core\task\adhoc_task::class));
        global $CFG;
        $page = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/admin/commerce/showrooms/lovable_build.php');
        self::assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $page);
        self::assertStringContainsString('require_sesskey();', $page);
        self::assertStringContainsString("Builder::queue((int)\$USER->id", $page);
        self::assertStringNotContainsString('npm install', $page);
    }

    public function test_compiler_only_returns_draft_archive_without_deploy(): void {
        global $CFG;
        $script = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/cli/lovable_builder/compile_preview.py');
        self::assertStringContainsString("'--network=none'", $script);
        self::assertStringContainsString("'--pull=never'", $script);
        self::assertStringContainsString("'--read-only'", $script);
        self::assertStringContainsString('NON_PUBLIABLE.txt', $script);
        self::assertStringNotContainsString('designs/articles_alf', $script);
    }
    public function test_copy_log_button_and_larger_read_limit(): void {
        global $CFG;
        $page = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/admin/commerce/showrooms/lovable_build.php');
        self::assertStringContainsString('campusfr-copy-build-log', $page);
        self::assertStringContainsString('navigator.clipboard.writeText(text)', $page);
        self::assertStringContainsString('campusfr-build-log', $page);
        self::assertStringContainsString("'type' => 'button'", $page);
        $service = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
        self::assertStringContainsString('MAX_LOG_BYTES = 180000', $service);
    }

    public function test_worker_relay_never_runs_podman_as_www_data(): void {
        global $CFG;
        $service = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/classes/commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');
        self::assertStringContainsString("'/usr/bin/sudo', '-n', '-u',", $service);
        self::assertStringContainsString("'campusfr-builder', '--', '/usr/local/libexec/campusfr-lovable-build'", $service);
        self::assertStringContainsString("'/var/spool/campusfr-lovable/inbox/'", $service);
        self::assertStringContainsString("'/var/spool/campusfr-lovable/outbox/'", $service);
        self::assertStringNotContainsString("'/usr/sbin/runuser', '-u', 'www-data'", $service);
        self::assertStringNotContainsString("'/usr/bin/podman', 'run'", $service);
    }

    public function test_worker_relay_is_not_directly_available_to_web_account(): void {
        self::assertTrue(is_executable('/usr/local/libexec/campusfr-lovable-build'));
        self::assertTrue(is_readable('/var/spool/campusfr-lovable/outbox'));
        self::assertTrue(is_dir('/var/spool/campusfr-lovable/inbox'));
        $worker = (string)file_get_contents('/usr/local/libexec/campusfr-lovable-build');
        self::assertStringContainsString('if len(sys.argv) != 1:', $worker);
        self::assertStringContainsString("if os.geteuid() != identity.pw_uid", $worker);
        self::assertStringContainsString("subprocess.Popen(command", $worker);
    }

}
