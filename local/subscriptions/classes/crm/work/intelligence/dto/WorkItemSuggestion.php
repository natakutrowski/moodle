<?php

namespace local_subscriptions\crm\work\intelligence\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemType;

/**
 * Complete human-reviewable Work Item suggestion.
 *
 * This DTO never creates a Work Item.
 */
final class WorkItemSuggestion {

    /**
     * @param string[] $reasons
     * @param WorkTeamSuggestion[] $teams
     * @param WorkItemDuplicateCandidate[] $duplicates
     */
    public function __construct(
        public readonly int $recommendationid,
        public readonly string $title,
        public readonly string $description,
        public readonly string $type,
        public readonly string $priority,
        public readonly ?int $targetuserid,
        public readonly ?int $suggestedteamid,
        public readonly ?int $dueat,
        public readonly int $confidencescore,
        public readonly array $reasons = [],
        public readonly array $teams = [],
        public readonly array $duplicates = []
    ) {
        if ($this->recommendationid <= 0) {
            throw new \InvalidArgumentException(
                'Work Item suggestion recommendation ID must be greater than zero.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Work Item suggestion title cannot be empty.'
            );
        }

        if (!WorkItemType::is_valid($this->type)) {
            throw new \InvalidArgumentException(
                'Invalid suggested Work Item type.'
            );
        }

        if (!WorkItemPriority::is_valid($this->priority)) {
            throw new \InvalidArgumentException(
                'Invalid suggested Work Item priority.'
            );
        }

        if (
            $this->targetuserid !== null &&
            $this->targetuserid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Suggested target user ID must be greater than zero.'
            );
        }

        if (
            $this->suggestedteamid !== null &&
            $this->suggestedteamid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Suggested team ID must be greater than zero.'
            );
        }

        if (
            $this->confidencescore < 0 ||
            $this->confidencescore > 100
        ) {
            throw new \InvalidArgumentException(
                'Work Item suggestion confidence must be between 0 and 100.'
            );
        }

        foreach ($this->teams as $team) {
            if (!$team instanceof WorkTeamSuggestion) {
                throw new \InvalidArgumentException(
                    'Work Item suggestion teams must contain WorkTeamSuggestion objects.'
                );
            }
        }

        foreach ($this->duplicates as $duplicate) {
            if (
                !$duplicate instanceof
                WorkItemDuplicateCandidate
            ) {
                throw new \InvalidArgumentException(
                    'Work Item suggestion duplicates must contain WorkItemDuplicateCandidate objects.'
                );
            }
        }
    }

    public function has_probable_duplicate(): bool {
        foreach ($this->duplicates as $duplicate) {
            if ($duplicate->is_probable_duplicate()) {
                return true;
            }
        }

        return false;
    }

    public function strongest_duplicate():
        ?WorkItemDuplicateCandidate {
        return $this->duplicates[0] ?? null;
    }

    public function to_object(): \stdClass {
        return (object)[
            'recommendationid' =>
                $this->recommendationid,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'targetuserid' => $this->targetuserid,
            'suggestedteamid' =>
                $this->suggestedteamid,
            'dueat' => $this->dueat,
            'confidencescore' =>
                $this->confidencescore,
            'reasons' => $this->reasons,
            'teams' => array_map(
                static fn(
                    WorkTeamSuggestion $team
                ): \stdClass => $team->to_object(),
                $this->teams
            ),
            'duplicates' => array_map(
                static fn(
                    WorkItemDuplicateCandidate $duplicate
                ): \stdClass => $duplicate->to_object(),
                $this->duplicates
            ),
            'hasprobableduplicate' =>
                $this->has_probable_duplicate(),
        ];
    }
}