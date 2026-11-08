<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePackageRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRenderTemplateRegistry;

/** Public-facing names and safe showroom duplication from the CRM. */
final class commerce_798x223_copy_and_labels_test extends \advanced_testcase {
    public function test_friendly_labels_preserve_internal_identifiers(): void {
        $options = CommerceShowroomRenderTemplateRegistry::options();
        self::assertSame('Showroom — Design Lovable', $options[CommerceShowroomBridgePackageRegistry::TEMPLATE]);
        $designs = CommerceShowroomBridgePackageRegistry::options();
        self::assertArrayHasKey('articles_alf', $designs);
        self::assertSame('Articles / Alf', $designs['articles_alf']);
    }

    public function test_duplicate_showroom_preserves_settings_products_and_stays_draft(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repo = new CommerceShowroomCmsRepository($DB);
        $source = $repo->save([
            'showroomkey' => 'articles-clone-ui',
            'name' => 'Articles original',
            'status' => 'published',
            'slugfr' => 'articles-source-fr',
            'slugen' => 'articles-source-en',
            'slugru' => 'articles-source-ru',
            'template' => CommerceShowroomBridgePackageRegistry::TEMPLATE,
            'settingsjson' => '{"designbridge":{"id":"articles_alf","content":{"fr":{"hero":{"title1":"Test"}}}}}',
            'productsjson' => '{"course":"COURSE_ACCESS.ARTICLES","donation":"DONATION.ALF"}',
        ], 2);
        $clone = $repo->duplicate_showroom($source, 2);
        self::assertNotSame($source, $clone);
        $original = $repo->get($source);
        $copy = $repo->get($clone);
        self::assertSame('published', $original->status);
        self::assertSame('draft', $copy->status);
        self::assertSame('', $copy->slugfr);
        self::assertSame('', $copy->slugen);
        self::assertSame('', $copy->slugru);
        self::assertSame($original->settingsjson, $copy->settingsjson);
        self::assertSame($original->productsjson, $copy->productsjson);
        self::assertSame($original->template, $copy->template);
        self::assertSame('Articles original (copie)', $copy->name);
        self::assertNotSame($original->showroomkey, $copy->showroomkey);
    }

    public function test_crm_duplicate_is_post_only_and_has_csrf_protection(): void {
        global $CFG;
        $file = $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/index.php';
        $html = (string)file_get_contents($file);
        self::assertStringContainsString("(\$_SERVER['REQUEST_METHOD'] ?? '') === 'POST'", $html);
        self::assertStringContainsString('require_sesskey();', $html);
        self::assertStringContainsString("'name' => 'duplicate_showroom'", $html);
        self::assertStringContainsString('duplicate_showroom($duplicateid, (int)$USER->id)', $html);
    }
}
