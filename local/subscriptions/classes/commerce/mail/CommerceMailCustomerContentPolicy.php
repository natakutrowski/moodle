<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/** Prevents internal Commerce identifiers from leaking into customer-visible mail content. */
final class CommerceMailCustomerContentPolicy {

    private const INTERNAL_REFERENCE_PATTERN = '/\bcmp_[a-z0-9_-]+\b/i';

    public function assert_safe(CommerceMailMessage $message): void {
        $surfaces = [
            'subject' => $message->get_subject(),
            'html' => $this->visible_html($message->get_html()),
            'text' => $this->visible_text($message->get_text()),
        ];

        foreach ($message->get_attachments() as $attachment) {
            $surfaces['attachment filename'] = $attachment->get_filename();
        }

        foreach ($surfaces as $surface => $content) {
            if (preg_match(self::INTERNAL_REFERENCE_PATTERN, $content) === 1) {
                throw new \coding_exception(
                    'A Commerce transactional mail cannot expose an internal purchase reference in its ' . $surface . '.'
                );
            }
        }
    }

    private function visible_html(string $html): string {
        // URLs may legitimately contain an internal routing reference. They are not customer-visible labels.
        $withouturls = preg_replace('/\s(?:href|src)=("[^"]*"|\'[^\']*\')/i', '', $html) ?? $html;
        return html_entity_decode(strip_tags($withouturls), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function visible_text(string $text): string {
        // Ignore raw URLs in the plain-text alternative for the same routing reason.
        return preg_replace('~https?://\S+~i', '', $text) ?? $text;
    }
}
