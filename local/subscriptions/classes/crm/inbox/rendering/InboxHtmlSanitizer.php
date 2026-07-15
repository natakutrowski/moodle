<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

final class InboxHtmlSanitizer {

    public function sanitize(?string $html): string {
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
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8">' .
                $cleanhtml,
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

            $placeholder = $document->createElement(
                'span'
            );

            if (!$placeholder instanceof \DOMElement) {
                continue;
            }

            $placeholder->setAttribute(
                'class',
                'crm-inbox-remote-image-placeholder'
            );

            $placeholder->setAttribute(
                'role',
                'note'
            );

            $placeholder->appendChild(
                $document->createTextNode(
                    get_string(
                        'crm_inbox_remote_image_blocked',
                        'local_subscriptions'
                    )
                )
            );

            $parent = $image->parentNode;

            if ($parent instanceof \DOMNode) {
                $parent->replaceChild(
                    $placeholder,
                    $image
                );
            }
        }

        $result = $document->saveHTML();

        return $result !== false
            ? $result
            : $cleanhtml;
    }
}