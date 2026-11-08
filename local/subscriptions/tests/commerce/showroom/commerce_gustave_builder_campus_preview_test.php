<?php
// Native CampusFR viewport for generated Lovable drafts: no public release.
declare(strict_types=1);
namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_gustave_builder_campus_preview_test extends \advanced_testcase {
    public function test_native_preview_keeps_existing_cms_id_route(): void {
        global $CFG;
        $page = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/preview.php');
        self::assertStringContainsString("require_login();", $page);
        self::assertStringContainsString("require_capability('local/subscriptions:manage_showrooms'", $page);
        self::assertStringContainsString("optional_param('lovablejob', '', PARAM_ALPHANUM)", $page);
        self::assertStringContainsString('if ($lovablejob !== \'\')', $page);
        self::assertStringContainsString("required_param('id', PARAM_INT)", $page);
        self::assertStringContainsString("require(__DIR__ . '/../../../showroom.php')", $page);
        $branch = strpos($page, 'if ($lovablejob !== \'\')');
        $original = strpos($page, "required_param('id', PARAM_INT)");
        self::assertNotFalse($branch);
        self::assertNotFalse($original);
        self::assertLessThan($original, $branch);
    }

    public function test_campus_canvas_requires_private_session_and_shares_native_layout(): void {
        global $CFG;
        $page = (string)file_get_contents($CFG->dirroot .
            '/local/subscriptions/admin/commerce/showrooms/lovable_campus_preview.php');
        foreach (['Builder::verify_session(', 'Preview::approved_archive(',
            "set_pagelayout('showroom')", 'commerce-showroom-page',
            "requires->css(new moodle_url('/local/subscriptions/styles/showroom.css'))",
            'iframe', "'sandbox' => 'allow-scripts'", "'referrerpolicy' => 'no-referrer'",
            'Aperçu CampusFR · non publié', 'no-store', 'noindex',
            'lovable_campus_preview.css'] as $fragment) {
            self::assertStringContainsString($fragment, $page);
        }
        foreach (['allow-same-origin', 'allow-forms', 'allow-top-navigation', 'send_file(',
            'CommerceShowroomCmsRepository', 'file_put_contents('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $page);
        }
    }


    public function test_full_viewport_css_is_scoped_to_private_canvas(): void {
        global $CFG;
        $css = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/styles/lovable_campus_preview.css');
        self::assertStringContainsString('body.campusfr-lovable-canvas-preview', $css);
        self::assertStringContainsString('position: fixed', $css);
        self::assertStringContainsString('height: 100dvh', $css);
        self::assertStringContainsString('z-index: 8001', $css);
        self::assertStringNotContainsString('https://', $css);
    }

    public function test_campus_preview_link_is_available_without_changing_technical_preview(): void {
        global $CFG;
        $path = $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/';
        $build = (string)file_get_contents($path . 'lovable_build.php');
        $tech = (string)file_get_contents($path . 'lovable_preview.php');
        self::assertStringContainsString("'lovablejob' => " . '$key', $build);
        self::assertStringContainsString('Prévisualiser sur CampusFR', $build);
        self::assertStringContainsString("'lovablejob' => " . '$key', $tech);
        self::assertStringContainsString('Voir sur CampusFR', $tech);
        foreach (["'frame' => 1", 'sandbox allow-scripts', "connect-src 'none'",
            "form-action 'none'", "frame-src 'none'", "worker-src 'none'", 'no-store'] as $fragment) {
            self::assertStringContainsString($fragment, $tech);
        }
    }
}
