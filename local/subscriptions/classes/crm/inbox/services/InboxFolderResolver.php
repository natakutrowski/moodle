<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxFolder;

final class InboxFolderResolver {

    private const TYPES = [
        'inbox',
        'sent',
        'drafts',
        'archive',
        'trash',
    ];

    /**
     * Noms classés par priorité.
     */
    private const ALIASES = [
        'inbox' => [
            'inbox',
            'boîte de réception',
            'boite de reception',
            'входящие',
        ],

        'sent' => [
            'sent',
            'sent items',
            'sent messages',
            'messages envoyés',
            'messages envoyes',
            'envoyés',
            'envoyes',
            'отправленные',
        ],

        'drafts' => [
            'drafts',
            'draft',
            'brouillons',
            'черновики',
        ],

        'archive' => [
            'archive',
            'archives',
            'all mail',
            'tous les messages',
            'архив',
        ],

        'trash' => [
            'trash',
            'deleted',
            'deleted items',
            'corbeille',
            'éléments supprimés',
            'elements supprimes',
            'корзина',
            'удаленные',
            'удалённые',
        ],
    ];

    /**
     * @param InboxFolder[] $folders
     * @return array<string,string>
     */
    public function resolve(
        array $folders,
        array $configured = []
    ): array {
        $resolved = [];

        foreach (self::TYPES as $type) {
            $configuredname = trim(
                (string)($configured[$type] ?? '')
            );

            if (
                $configuredname !== '' &&
                $this->contains_folder(
                    $folders,
                    $configuredname
                )
            ) {
                $resolved[$type] =
                    $this->actual_name(
                        $folders,
                        $configuredname
                    );

                continue;
            }

            $specialuse =
                $this->find_by_special_use(
                    $folders,
                    $type
                );

            if ($specialuse !== null) {
                $resolved[$type] = $specialuse;
                continue;
            }

            $alias = $this->find_by_alias(
                $folders,
                $type
            );

            if ($alias !== null) {
                $resolved[$type] = $alias;
            }
        }

        /*
         * INBOX est un nom réservé par le protocole IMAP.
         */
        $resolved['inbox'] =
            $resolved['inbox'] ?? 'INBOX';

        return $resolved;
    }

    /**
     * @param InboxFolder[] $folders
     */
    public function missing_required(
        array $folders,
        array $configured = []
    ): array {
        $resolved = $this->resolve(
            $folders,
            $configured
        );

        return array_values(
            array_filter(
                ['inbox', 'sent', 'trash'],
                static fn(string $type): bool =>
                    empty($resolved[$type])
            )
        );
    }

    /**
     * @param InboxFolder[] $folders
     */
    private function find_by_special_use(
        array $folders,
        string $type
    ): ?string {
        foreach ($folders as $folder) {
            if ($folder->specialuse === $type) {
                return $folder->name;
            }
        }

        return null;
    }

    /**
     * @param InboxFolder[] $folders
     */
    private function find_by_alias(
        array $folders,
        string $type
    ): ?string {
        $aliases = self::ALIASES[$type] ?? [];

        foreach ($aliases as $alias) {
            foreach ($folders as $folder) {
                if (
                    $this->normalize($folder->name) ===
                    $this->normalize($alias)
                ) {
                    return $folder->name;
                }
            }
        }

        /*
         * Certains serveurs retournent des chemins comme
         * INBOX/Sent ou INBOX.Archives.
         */
        foreach ($aliases as $alias) {
            foreach ($folders as $folder) {
                $basename = $this->basename(
                    $folder->name,
                    $folder->delimiter
                );

                if (
                    $this->normalize($basename) ===
                    $this->normalize($alias)
                ) {
                    return $folder->name;
                }
            }
        }

        return null;
    }

    /**
     * @param InboxFolder[] $folders
     */
    private function contains_folder(
        array $folders,
        string $name
    ): bool {
        return $this->actual_name(
            $folders,
            $name
        ) !== null;
    }

    /**
     * @param InboxFolder[] $folders
     */
    private function actual_name(
        array $folders,
        string $name
    ): ?string {
        $normalized = $this->normalize($name);

        foreach ($folders as $folder) {
            if (
                $this->normalize($folder->name) ===
                $normalized
            ) {
                return $folder->name;
            }
        }

        return null;
    }

    private function basename(
        string $name,
        string $delimiter
    ): string {
        if ($delimiter === '') {
            return $name;
        }

        $parts = explode($delimiter, $name);

        return (string)end($parts);
    }

    private function normalize(string $value): string {
        $value = trim(
            \core_text::strtolower($value)
        );

        if (class_exists('\Transliterator')) {
            $transliterator = \Transliterator::create(
                'NFD; [:Nonspacing Mark:] Remove; NFC'
            );

            if ($transliterator !== null) {
                $value = $transliterator->transliterate(
                    $value
                );
            }
        }

        return trim(
            preg_replace(
                '/[^\p{L}\p{N}]+/u',
                ' ',
                $value
            ) ?? $value
        );
    }
}