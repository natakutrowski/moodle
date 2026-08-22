<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

final class InboxHtmlSanitizer {

    public function sanitize(?string $html, bool $allowremoteimages = false): string {
        if ($html === null || trim($html) === '') {
            return '';
        }

        /*
         * Première protection Moodle :
         * scripts, événements HTML et balises dangereuses
         * sont supprimés.
         */
        $cleanhtml = clean_text(
            $html,
            FORMAT_HTML
        );

        if (!class_exists('\DOMDocument')) {
            return $cleanhtml;
        }

        $document = new \DOMDocument(
            '1.0',
            'UTF-8'
        );

        $previouserrors =
            libxml_use_internal_errors(true);

        try {
            $protectedhtml =
                self::protect_inline_whitespace(
                    $cleanhtml
                );

            $loaded = $document->loadHTML(
                '<meta charset="UTF-8">'
                . '<div id="crm-inbox-render-root">'
                . $protectedhtml
                . '</div>',
                LIBXML_HTML_NOIMPLIED |
                LIBXML_HTML_NODEFDTD
            );
        } finally {
            libxml_clear_errors();

            libxml_use_internal_errors(
                $previouserrors
            );
        }

        if (!$loaded) {
            return $cleanhtml;
        }

        $this->remove_hidden_email_nodes(
            $document
        );

        /*
         * DOMNodeList est dynamique.
         * On copie d’abord les nœuds dans un tableau.
         *
         * @var \DOMElement[] $images
         */
        $images = [];

        foreach (
            $document->getElementsByTagName('img')
            as $node
        ) {
            if ($node instanceof \DOMElement) {
                $images[] = $node;
            }
        }

        $blockedremoteimages = 0;

        foreach ($images as $image) {
            $source = trim(
                $image->getAttribute('src')
            );

            if (
                !preg_match(
                    '#^https?://#i',
                    $source
                )
            ) {
                continue;
            }

            global $CFG;

            $wwwroot = rtrim(
                (string)$CFG->wwwroot,
                '/'
            );

            if (
                $wwwroot !== ''
                && str_starts_with(
                    $source,
                    $wwwroot . '/'
                )
            ) {
                continue;
            }

            if ($allowremoteimages) {
                continue;
            }

            $blockedremoteimages++;

            $parent = $image->parentNode;

            if ($parent instanceof \DOMNode) {
                $parent->removeChild($image);
            }
        }

        if ($blockedremoteimages > 0) {
            $notice = $document->createElement('div');

            if ($notice instanceof \DOMElement) {
                $notice->setAttribute(
                    'class',
                    'crm-inbox-remote-images-notice'
                );
                $notice->setAttribute(
                    'role',
                    'note'
                );

                $copy = $document->createElement('span');
                $copy->appendChild(
                    $document->createTextNode(
                        get_string(
                            'crm_inbox_remote_images_blocked_summary',
                            'local_subscriptions',
                            $blockedremoteimages
                        )
                    )
                );
                $notice->appendChild($copy);

                $button = $document->createElement('button');
                $button->setAttribute('type', 'button');
                $button->setAttribute(
                    'class',
                    'btn btn-sm btn-outline-secondary '
                    . 'crm-inbox-load-images-button'
                );
                $button->setAttribute(
                    'data-inbox-load-images',
                    '1'
                );
                $button->appendChild(
                    $document->createTextNode(
                        get_string(
                            'crm_inbox_load_remote_images',
                            'local_subscriptions'
                        )
                    )
                );
                $notice->appendChild($button);

                $root = $document->getElementById(
                    'crm-inbox-render-root'
                );

                if ($root instanceof \DOMElement) {
                    $firstnode = $root->firstChild;

                    if ($firstnode instanceof \DOMNode) {
                        $root->insertBefore(
                            $notice,
                            $firstnode
                        );
                    } else {
                        $root->appendChild($notice);
                    }
                }
            }
        }


        $root = $document->getElementById(
            'crm-inbox-render-root'
        );

        if (!$root) {
            return $cleanhtml;
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

        return self::restore_inline_whitespace(
            $result
        );
    }

    /**
     * Protect spaces that sit between two inline tags while Moodle/DOM
     * sanitisation runs. Some email HTML is aggressively normalised and can
     * otherwise turn "<strong>A</strong> <em>B</em>" into visually glued
     * words in the CRM preview even though the received email is correct.
     */
    private static function protect_inline_whitespace(
        string $html
    ): string {
        return preg_replace_callback(
            '~(</(?:strong|b|em|i|u|a|span)>)'
            . '([ \t]+)'
            . '(<(?:strong|b|em|i|u|a|span)\b)~i',
            static function (array $match): string {
                return $match[1]
                    . 'CRM_INBOX_INLINE_SPACE'
                    . $match[3];
            },
            $html
        ) ?? $html;
    }

    private static function restore_inline_whitespace(
        string $html
    ): string {
        return str_replace(
            'CRM_INBOX_INLINE_SPACE',
            ' ',
            $html
        );
    }

    /**
     * Removes common hidden/preheader fragments before rendering an e-mail.
     *
     * Many marketing templates rely on CSS hacks such as display:none,
     * max-height:0 or mso-hide:all. Once an e-mail is sanitised those hacks
     * can become visible in a very narrow table cell and produce vertical
     * text down the side of the preview.
     */
    private function remove_hidden_email_nodes(
        \DOMDocument $document
    ): void {
        $elements = [];

        foreach ($document->getElementsByTagName('*') as $node) {
            if ($node instanceof \DOMElement) {
                $elements[] = $node;
            }
        }

        foreach ($elements as $element) {
            $style = strtolower(
                preg_replace(
                    '/\\s+/',
                    '',
                    $element->getAttribute('style')
                ) ?? ''
            );

            $hidden = $element->hasAttribute('hidden')
                || strtolower($element->getAttribute('aria-hidden')) === 'true'
                || str_contains($style, 'display:none')
                || str_contains($style, 'visibility:hidden')
                || str_contains($style, 'mso-hide:all')
                || str_contains($style, 'max-height:0')
                || (
                    str_contains($style, 'font-size:0')
                    && str_contains($style, 'overflow:hidden')
                );

            if (!$hidden) {
                continue;
            }

            $parent = $element->parentNode;
            if ($parent instanceof \DOMNode) {
                $parent->removeChild($element);
            }
        }
    }

}