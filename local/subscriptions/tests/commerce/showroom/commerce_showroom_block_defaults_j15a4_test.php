<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockDefaultsCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

final class commerce_showroom_block_defaults_j15a4_test extends \advanced_testcase {
    public function test_current_showroom_defaults_are_available(): void {
        $defaults = CommerceShowroomBlockDefaultsCatalog::for_showroom(
            'third-group-verbs'
        );

        $this->assertArrayHasKey('hero', $defaults);
        $this->assertArrayHasKey('offers', $defaults);
        $this->assertSame('pdf,bundle,course', $defaults['offers']['order']);
        $this->assertSame('#showroom-offers', $defaults['hero']['primarytarget']);
    }

    public function test_initialisation_only_fills_empty_blocks(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbes du 3e groupe',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $emptyid = $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => '{}',
        ], 2);
        $customid = $repository->save_block($showroomid, [
            'blocktype' => 'cta',
            'blockkey' => 'cta',
            'sortorder' => 20,
            'enabled' => true,
            'configjson' => json_encode(['title' => 'Personnalisé']),
        ], 2);

        $this->assertSame(1, $repository->initialise_block_defaults($showroomid, 2));

        $empty = json_decode($repository->get_block($emptyid)->configjson, true);
        $custom = json_decode($repository->get_block($customid)->configjson, true);

        $this->assertNotEmpty($empty);
        $this->assertSame('Personnalisé', $custom['title']);
    }

    public function test_offer_css_enforces_pdf_bundle_course_order(): void {
        $css = file_get_contents(__DIR__ . '/../../../styles/showroom.css');

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '.commerce-showroom-offer--pdf',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-showroom-offer--bundle',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-showroom-offer--course',
            $css
        );
    }
}
