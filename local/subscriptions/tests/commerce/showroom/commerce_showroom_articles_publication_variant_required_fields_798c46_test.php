<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationIntegrityValidator;

final class commerce_showroom_articles_publication_variant_required_fields_798c46_test extends \advanced_testcase {
    private function validator_and_method(): array {
        $reflection = new \ReflectionClass(CommerceShowroomPublicationIntegrityValidator::class);
        $validator = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('validate_required_fields');
        $method->setAccessible(true);
        return [$validator, $method];
    }

    public function test_four_columns_ppt_does_not_require_legacy_interactive_fields(): void {
        [$validator, $method] = $this->validator_and_method();

        $method->invoke($validator, 'problem_interactive', 'problem_interactive', [
            'presentation' => 'four_columns_ppt',
            'title' => 'Vous connaissez les articles.',
            'choices' => '',
            'correctanswer' => '',
            'consequences' => '',
            'col1items' => "le / la / les\nun / une / des",
            'col3items' => "J’adore ___ chocolat.\nIl joue ___ piano.",
            'col4items' => "Le sens change|un ami ≠ l’ami|fa-regular fa-comment-dots",
        ]);

        self::assertTrue(true);
    }

    public function test_four_columns_ppt_requires_its_actual_content_columns(): void {
        [$validator, $method] = $this->validator_and_method();

        try {
            $method->invoke($validator, 'problem_interactive', 'problem_interactive', [
                'presentation' => 'four_columns_ppt',
                'title' => 'Vous connaissez les articles.',
                'col1items' => "le / la / les\nun / une / des",
                'col3items' => '',
                'col4items' => "Le sens change|un ami ≠ l’ami|fa-regular fa-comment-dots",
            ]);
            self::fail('Expected publication integrity failure.');
        } catch (\moodle_exception $exception) {
            self::assertSame('commerce_showroom_publish_integrity_failed', $exception->errorcode);
            self::assertStringContainsString('col3items', $exception->a);
        }
    }

    public function test_interactive_presentation_still_requires_choices(): void {
        [$validator, $method] = $this->validator_and_method();

        try {
            $method->invoke($validator, 'problem_interactive', 'problem_interactive', [
                'presentation' => 'interactive',
                'title' => 'Test',
                'choices' => '',
                'correctanswer' => 'Je prends',
                'consequences' => 'Conséquence',
            ]);
            self::fail('Expected publication integrity failure.');
        } catch (\moodle_exception $exception) {
            self::assertSame('commerce_showroom_publish_integrity_failed', $exception->errorcode);
            self::assertStringContainsString('choices', $exception->a);
        }
    }
}
