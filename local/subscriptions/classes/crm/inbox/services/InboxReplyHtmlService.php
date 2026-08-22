<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

/**
 * Sanitises HTML produced by the lightweight Inbox composer.
 */
final class InboxReplyHtmlService {

    public function sanitize(string $html): string {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        /*
         * Moodle's HTML cleaner does not preserve cid: image URLs reliably.
         * Protect them temporarily behind an HTTPS-shaped placeholder, run
         * the normal Moodle sanitiser, then restore only those exact CIDs.
         */
        $protected = preg_replace_callback(
            '~(<img\b[^>]*\bsrc\s*=\s*)(["\'])cid:([A-Za-z0-9._@+\-]+)\2~i',
            static function (array $match): string {
                $token = rawurlencode(
                    $match[3]
                );

                return $match[1]
                    . $match[2]
                    . 'https://crm-inline.invalid/'
                    . $token
                    . $match[2];
            },
            $html
        );

        if (!is_string($protected)) {
            $protected = $html;
        }

        $clean = clean_text(
            $protected,
            FORMAT_HTML
        );

        if (!class_exists('\DOMDocument')) {
            return preg_replace_callback(
                '~https://crm-inline\.invalid/([A-Za-z0-9%._@+\-]+)~i',
                static fn(array $match): string =>
                    'cid:' . rawurldecode(
                        $match[1]
                    ),
                $clean
            ) ?? $clean;
        }

        $document = new \DOMDocument(
            '1.0',
            'UTF-8'
        );

        $previous = libxml_use_internal_errors(
            true
        );

        try {
            /*
             * Use a real HTML charset declaration + wrapper instead of an XML
             * processing instruction. The previous '<?xml encoding="UTF-8">'
             * trick was serialized into the outgoing message body.
             */
            $loaded = $document->loadHTML(
                '<meta charset="UTF-8">'
                . '<div id="crm-inbox-reply-root">'
                . $clean
                . '</div>',
                LIBXML_HTML_NOIMPLIED |
                LIBXML_HTML_NODEFDTD
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(
                $previous
            );
        }

        if (!$loaded) {
            return $clean;
        }

        $images = [];

        foreach (
            $document->getElementsByTagName('img')
            as $image
        ) {
            if ($image instanceof \DOMElement) {
                $images[] = $image;
            }
        }

        foreach ($images as $image) {
            $source = trim(
                $image->getAttribute('src')
            );

            if (
                preg_match(
                    '~^https://crm-inline\.invalid/([A-Za-z0-9%._@+\-]+)$~i',
                    $source,
                    $matches
                )
            ) {
                $cid = rawurldecode(
                    $matches[1]
                );

                if (
                    !preg_match(
                        '~^[A-Za-z0-9._@+\-]+$~',
                        $cid
                    )
                ) {
                    $parent = $image->parentNode;

                    if ($parent instanceof \DOMNode) {
                        $parent->removeChild(
                            $image
                        );
                    }

                    continue;
                }

                $image->setAttribute(
                    'src',
                    'cid:' . $cid
                );
                $image->removeAttribute(
                    'onerror'
                );
                $image->removeAttribute(
                    'onload'
                );
                $image->setAttribute(
                    'style',
                    'max-width:100%;height:auto;'
                );

                continue;
            }

            $parent = $image->parentNode;

            if ($parent instanceof \DOMNode) {
                $parent->removeChild(
                    $image
                );
            }
        }

        $root = $document->getElementById(
            'crm-inbox-reply-root'
        );

        if (!$root) {
            return $clean;
        }

        $result = '';

        foreach ($root->childNodes as $child) {
            $fragment = $document->saveHTML(
                $child
            );

            if ($fragment !== false) {
                $result .= $fragment;
            }
        }

        return trim($result);
    }

    /**
     * @return string[]
     */
    public function referenced_cids(
        string $html
    ): array {
        preg_match_all(
            '~cid:([A-Za-z0-9._@+\-]+)~i',
            $html,
            $matches
        );

        return array_values(
            array_unique(
                array_map(
                    static fn(string $cid): string =>
                        trim($cid, "<> \t\n\r\0\x0B"),
                    $matches[1] ?? []
                )
            )
        );
    }

    public function text_version(
        string $html
    ): string {
        if (trim($html) === '') {
            return '';
        }

        return trim(
            html_to_text(
                $html,
                0,
                false
            )
        );
    }
}
