<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o8_1_phpmailer_sent_mime_api_test
    extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3)
            . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_smtp_connector_uses_phpmailer_sent_mime_message_api(): void {
        $smtp = $this->file(
            'classes/crm/inbox/connectors/smtp/OvhSmtpConnector.php'
        );

        self::assertStringContainsString(
            'getSentMIMEMessage()',
            $smtp
        );

        self::assertStringNotContainsString(
            'getSentMIME()',
            $smtp
        );

        self::assertStringContainsString(
            '$mailer->preSend()',
            $smtp
        );

        self::assertStringContainsString(
            '$mailer->postSend()',
            $smtp
        );
    }
}
