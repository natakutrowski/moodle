<?php

namespace local_subscriptions\crm\inbox\connectors\imap;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxAttachmentData;

final class ImapMimeParser {

    /**
     * @return array{
     *     bodytext:?string,
     *     bodyhtml:?string,
     *     attachments:InboxAttachmentData[]
     * }
     */
    public function parse(
        mixed $stream,
        int $uid,
        object $structure
    ): array {
        $result = [
            'bodytext' => null,
            'bodyhtml' => null,
            'attachments' => [],
        ];

        if (
            empty($structure->parts) ||
            !is_array($structure->parts)
        ) {
            $content = imap_body(
                $stream,
                $uid,
                FT_UID | FT_PEEK
            );

            $content = $this->decode(
                (string)$content,
                (int)($structure->encoding ?? 0)
            );

            $subtype = strtoupper(
                (string)($structure->subtype ?? 'PLAIN')
            );

            if ($subtype === 'HTML') {
                $result['bodyhtml'] = $content;
            } else {
                $result['bodytext'] = $content;
            }

            return $result;
        }

        $this->walk_parts(
            $stream,
            $uid,
            $structure->parts,
            '',
            $result
        );

        return $result;
    }

    public function fetch_part_content(
        mixed $stream,
        int $uid,
        string $partnumber,
        object $part
    ): string {
        $content = imap_fetchbody(
            $stream,
            $uid,
            $partnumber,
            FT_UID | FT_PEEK
        );

        return $this->decode(
            (string)$content,
            (int)($part->encoding ?? 0)
        );
    }

    private function walk_parts(
        mixed $stream,
        int $uid,
        array $parts,
        string $prefix,
        array &$result
    ): void {
        foreach ($parts as $index => $part) {
            $partnumber = $prefix === ''
                ? (string)($index + 1)
                : $prefix . '.' . ($index + 1);

            if (
                !empty($part->parts) &&
                is_array($part->parts)
            ) {
                $this->walk_parts(
                    $stream,
                    $uid,
                    $part->parts,
                    $partnumber,
                    $result
                );

                continue;
            }

            $filename = $this->filename($part);
            $contentid = $this->content_id($part);
            $disposition = strtoupper(
                (string)($part->disposition ?? '')
            );

            $isattachment =
                $filename !== null ||
                in_array(
                    $disposition,
                    ['ATTACHMENT', 'INLINE'],
                    true
                );

            if ($isattachment) {
                $result['attachments'][] =
                    new InboxAttachmentData(
                        $partnumber,
                        $filename ??
                            ('attachment-' . $partnumber),
                        $this->mimetype($part),
                        (int)($part->bytes ?? 0),
                        $contentid,
                        $disposition === 'INLINE'
                    );

                continue;
            }

            if ((int)($part->type ?? -1) !== 0) {
                continue;
            }

            $subtype = strtoupper(
                (string)($part->subtype ?? 'PLAIN')
            );

            if (
                $subtype !== 'PLAIN' &&
                $subtype !== 'HTML'
            ) {
                continue;
            }

            $content = $this->fetch_part_content(
                $stream,
                $uid,
                $partnumber,
                $part
            );

            $content = $this->convert_charset(
                $content,
                $this->parameter(
                    $part,
                    'charset'
                )
            );

            if ($subtype === 'HTML') {
                if ($result['bodyhtml'] === null) {
                    $result['bodyhtml'] = $content;
                }
            } else if ($result['bodytext'] === null) {
                $result['bodytext'] = $content;
            }
        }
    }

    private function filename(object $part): ?string {
        $filename =
            $this->parameter($part, 'filename')
            ?? $this->parameter($part, 'name');

        if ($filename === null) {
            return null;
        }

        return $this->decode_header($filename);
    }

    private function content_id(object $part): ?string {
        $contentid = trim(
            (string)($part->id ?? ''),
            " \t\n\r\0\x0B<>"
        );

        return $contentid !== ''
            ? $contentid
            : null;
    }

    private function parameter(
        object $part,
        string $name
    ): ?string {
        $collections = [
            $part->parameters ?? [],
            $part->dparameters ?? [],
        ];

        foreach ($collections as $parameters) {
            if (!is_array($parameters)) {
                continue;
            }

            foreach ($parameters as $parameter) {
                if (
                    strcasecmp(
                        (string)($parameter->attribute ?? ''),
                        $name
                    ) === 0
                ) {
                    $value = trim(
                        (string)($parameter->value ?? '')
                    );

                    return $value !== ''
                        ? $value
                        : null;
                }
            }
        }

        return null;
    }

    private function decode(
        string $content,
        int $encoding
    ): string {
        return match ($encoding) {
            3 => base64_decode(
                $content,
                true
            ) ?: '',
            4 => quoted_printable_decode($content),
            default => $content,
        };
    }

    private function convert_charset(
        string $content,
        ?string $charset
    ): string {
        if (
            $charset === null ||
            strcasecmp($charset, 'UTF-8') === 0 ||
            strcasecmp($charset, 'US-ASCII') === 0
        ) {
            return $content;
        }

        if (function_exists('mb_convert_encoding')) {
            try {
                return mb_convert_encoding(
                    $content,
                    'UTF-8',
                    $charset
                );
            } catch (\Throwable $exception) {
                return $content;
            }
        }

        $converted = @iconv(
            $charset,
            'UTF-8//IGNORE',
            $content
        );

        return $converted !== false
            ? $converted
            : $content;
    }

    private function decode_header(
        string $value
    ): string {
        $parts = imap_mime_header_decode($value);
        $decoded = '';

        foreach ($parts as $part) {
            $text = (string)($part->text ?? '');
            $charset = (string)($part->charset ?? 'default');

            if (
                $charset !== 'default' &&
                strcasecmp($charset, 'UTF-8') !== 0
            ) {
                $text = $this->convert_charset(
                    $text,
                    $charset
                );
            }

            $decoded .= $text;
        }

        return trim($decoded);
    }

    private function mimetype(object $part): string {
        $types = [
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            7 => 'other',
        ];

        $type = $types[
            (int)($part->type ?? 7)
        ] ?? 'application';

        $subtype = \core_text::strtolower(
            (string)($part->subtype ?? 'octet-stream')
        );

        return $type . '/' . $subtype;
    }
}