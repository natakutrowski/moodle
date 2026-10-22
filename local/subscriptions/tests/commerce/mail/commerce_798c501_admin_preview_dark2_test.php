<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\mail;

defined('MOODLE_INTERNAL') || die();

final class commerce_798c501_admin_preview_dark2_test extends \advanced_testcase {
    public function test_dark2_builder_label_is_translated_in_all_supported_languages(): void {
        global $CFG;

        foreach ([
            'fr' => 'Sombre 2',
            'en' => 'Dark 2',
            'ru' => 'Тёмный 2',
        ] as $language => $expected) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertStringContainsString(
                "\$string['commerce_showroom_n95_background_dark2'] = '" . $expected . "';",
                $source
            );
        }
    }

    public function test_transactional_mail_library_exposes_preview_button(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/mail/templates/index.php'
        );

        self::assertStringContainsString(
            '/local/subscriptions/admin/commerce/mail/templates/preview.php',
            $source
        );
        self::assertStringContainsString("'mailtype' => \$mailtype", $source);
        self::assertStringContainsString("'language' => 'fr'", $source);
        self::assertStringContainsString("get_string('commerce_mail_action_preview', 'local_subscriptions')", $source);
        self::assertStringContainsString("'class' => 'fa fa-eye me-1'", $source);
    }
}
