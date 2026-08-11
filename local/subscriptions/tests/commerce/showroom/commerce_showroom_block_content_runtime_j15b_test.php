<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockDefaultsCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_block_content_runtime_j15b_test extends \advanced_testcase {
    public function test_published_config_overrides_public_content(): void {
        global $DB;
        $this->resetAfterTest(true);
        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs', 'status' => 'published',
            'name' => 'Verbes', 'template' => 'local_subscriptions/showroom/third_group_verbs',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'hero', 'blockkey' => 'hero', 'sortorder' => 10, 'enabled' => true,
            'configjson' => json_encode([
                'eyebrow' => 'Nouveau', 'title' => 'Titre CMS', 'text' => 'Texte CMS',
                'primarylabel' => 'Voir les offres', 'primarytarget' => '#showroom-offers',
                'showgustave' => false,
            ], JSON_THROW_ON_ERROR),
        ], 2);
        $blocks = CommerceShowroomRuntimeBlockSet::load($DB, 'third-group-verbs');
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([
            'eyebrow' => 'Ancien', 'title' => 'Ancien titre', 'description' => 'Ancien texte',
            'herocta' => 'Ancien CTA', 'heroactionurl' => '#old',
        ], $blocks);
        $this->assertSame('Titre CMS', $data['title']);
        $this->assertSame('Texte CMS', $data['description']);
        $this->assertSame('Voir les offres', $data['herocta']);
        $this->assertFalse($data['showherogustave']);
    }

    public function test_defaults_catalog_does_not_require_optional_missing_keys(): void {
        $defaults = CommerceShowroomBlockDefaultsCatalog::for_showroom('third-group-verbs');
        $this->assertArrayHasKey('stats', $defaults);
        $this->assertIsArray($defaults['stats']);
        $this->assertArrayHasKey('title', $defaults['stats']);
        $this->assertArrayHasKey('items', $defaults['stats']);
    }

    public function test_editor_has_back_to_index_action(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/showrooms/edit.php');
        $this->assertIsString($source);
        $this->assertStringContainsString('/admin/commerce/showrooms/index.php', $source);
        $this->assertStringContainsString('commerce_showroom_back_to_list', $source);
    }
}
