<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\builder\CommerceMailBuilder;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

final class commerce_mail_builder_n52_test extends \advanced_testcase {
    public function test_shared_builder_definition_and_cta_renderer_are_stable(): void {
        self::assertSame(2, CommerceMailBuilder::VERSION);
        self::assertSame(2, CommerceMailLibrary::BUILDER_VERSION);
        self::assertContains('gold', CommerceMailBuilder::cta_variants());
        self::assertContains('campus_pink', CommerceMailBuilder::cta_variants());
        self::assertContains('legacy_blue', CommerceMailBuilder::cta_variants());

        $tags = array_column(CommerceMailBuilder::personal_offer_structural_tags(), 'tag');
        self::assertContains('{{offer}}', $tags);
        self::assertContains('{{direct_pay}}', $tags);
        self::assertContains('{{image}}', $tags);

        $renderer = new CommerceMailBuilderCtaRenderer();
        $html = $renderer->render_tags(
            '<p>Hello</p>{{cta|campus_pink}}Acheter{{/cta}}',
            'https://example.test/checkout'
        );
        self::assertStringContainsString('campusfr-campaign-cta-campus_pink', $html);
        self::assertStringContainsString('https://example.test/checkout', $html);
        self::assertStringContainsString('Acheter', $html);
        self::assertStringNotContainsString('{{cta', $html);
    }

    public function test_library_repository_persists_mail_builder_v2_document(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $repository = new CommerceMailLibraryRepository($DB);

        $template = $repository->save([
            'name' => 'N5.2 builder test',
            'category' => CommerceMailLibrary::CATEGORY_MARKETING,
            'status' => CommerceMailLibrary::STATUS_DRAFT,
            'metadata' => ['editor' => 'mail_builder'],
        ], [
            'fr' => [
                'subject' => 'Bonjour {{firstname}}',
                'preheader' => 'Préheader',
                'bodyhtml' => '<p>Bonjour {{firstname}}</p>'
                    . '{{cta|gold}}Découvrir{{/cta}}',
            ],
        ], (int)$user->id);

        self::assertSame(2, (int)$template->builderversion);
        $contents = $repository->contents((int)$template->id);
        $document = json_decode((string)$contents['fr']->contentjson, true);
        self::assertIsArray($document);
        self::assertSame('mail_builder', $document['mode']);
        self::assertSame(2, $document['builderversion']);
        self::assertStringContainsString('{{cta|gold}}', $document['bodyhtml']);
    }

    public function test_n52_library_and_personal_offer_editors_share_same_editor_components(): void {
        $root = dirname(__DIR__, 3);
        $library = file_get_contents(
            $root . '/admin/commerce/mail/templates/library_edit.php'
        );
        $personal = file_get_contents(
            $root . '/admin/commerce/personal-offers/campaign_email.php'
        );
        $editor = file_get_contents(
            $root . '/classes/commerce/mail/builder/CommerceMailBuilderEditorRenderer.php'
        );
        $runtime = file_get_contents(
            $root . '/classes/commerce/personaloffer/mail/CommercePersonalOfferCampaignMailRenderer.php'
        );

        self::assertIsString($library);
        self::assertIsString($personal);
        self::assertIsString($editor);
        self::assertIsString($runtime);

        self::assertStringContainsString(
            'CommerceMailBuilderEditorRenderer::tag_palette',
            $library
        );
        self::assertStringContainsString(
            'CommerceMailBuilderEditorRenderer::rich_editor',
            $library
        );
        self::assertStringContainsString(
            'CommerceMailBuilderEditorRenderer::tag_palette',
            $personal
        );
        self::assertStringContainsString(
            'CommerceMailBuilderEditorRenderer::rich_editor',
            $personal
        );
        self::assertStringContainsString(
            'editors_get_preferred_editor',
            $editor
        );
        self::assertStringContainsString(
            'CommerceMailBuilderCtaRenderer',
            $runtime
        );
        self::assertStringNotContainsString(
            'private function render_cta_tags',
            $runtime
        );
    }

    public function test_n52_language_catalogues_have_common_builder_strings(): void {
        $root = dirname(__DIR__, 3);
        foreach (['en', 'fr', 'ru'] as $language) {
            $catalogue = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertIsString($catalogue);
            foreach ([
                'commerce_mail_library_builder_note',
                'commerce_mail_builder_variables',
                'commerce_mail_builder_blocks',
                'commerce_mail_builder_no_variables',
                'commerce_mail_builder_tag_help',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $catalogue,
                    $language . ' is missing ' . $key
                );
            }
        }
    }
}
