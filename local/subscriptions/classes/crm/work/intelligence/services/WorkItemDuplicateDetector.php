<?php

namespace local_subscriptions\crm\work\intelligence\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\intelligence\dto\WorkItemDuplicateCandidate;
use local_subscriptions\crm\work\intelligence\repositories\WorkItemDuplicateRepository;

/**
 * Detects duplicate Work Items deterministically.
 */
final class WorkItemDuplicateDetector {

    public function __construct(
        private readonly WorkItemDuplicateRepository $repository =
            new WorkItemDuplicateRepository()
    ) {
    }

    /**
     * @return WorkItemDuplicateCandidate[]
     */
    public function detect(
        int $recommendationid,
        ?int $targetuserid,
        string $type,
        string $title,
        string $description,
        int $limit = 10
    ): array {
        $duplicates = [];

        $linked =
            $this->repository
                ->find_linked_recommendation_item(
                    $recommendationid
                );

        if ($linked !== null) {
            $duplicates[] =
                new WorkItemDuplicateCandidate(
                    workitemid: (int)$linked->id,
                    reference:
                        (string)$linked->reference,
                    title:
                        (string)$linked->title,
                    type:
                        (string)$linked->type,
                    priority:
                        (string)$linked->priority,
                    status:
                        (string)$linked->status,
                    similarityscore: 100,
                    reasons: [
                        'same_recommendation_link',
                    ]
                );
        }

        $candidates =
            $this->repository->find_candidates(
                $targetuserid,
                $type,
                50
            );

        foreach ($candidates as $candidate) {
            if (
                $linked !== null &&
                (int)$candidate->id ===
                    (int)$linked->id
            ) {
                continue;
            }

            [$score, $reasons] =
                $this->calculate_similarity(
                    $title,
                    $description,
                    (string)$candidate->title,
                    (string)($candidate->description ?? ''),
                    $targetuserid,
                    $candidate->targetuserid !== null
                        ? (int)$candidate->targetuserid
                        : null
                );

            if ($score < 35) {
                continue;
            }

            $duplicates[] =
                new WorkItemDuplicateCandidate(
                    workitemid:
                        (int)$candidate->id,
                    reference:
                        (string)$candidate->reference,
                    title:
                        (string)$candidate->title,
                    type:
                        (string)$candidate->type,
                    priority:
                        (string)$candidate->priority,
                    status:
                        (string)$candidate->status,
                    similarityscore: $score,
                    reasons: $reasons
                );
        }

        usort(
            $duplicates,
            static fn(
                WorkItemDuplicateCandidate $left,
                WorkItemDuplicateCandidate $right
            ): int =>
                $right->similarityscore <=>
                $left->similarityscore
        );

        return array_slice(
            $duplicates,
            0,
            max(1, $limit)
        );
    }

    /**
     * @return array{0:int,1:string[]}
     */
    private function calculate_similarity(
        string $proposedtitle,
        string $proposeddescription,
        string $existingtitle,
        string $existingdescription,
        ?int $proposeduserid,
        ?int $existinguserid
    ): array {
        $score = 0;
        $reasons = [];

        $normalizedproposedtitle =
            $this->normalize($proposedtitle);

        $normalizedexistingtitle =
            $this->normalize($existingtitle);

        if (
            $normalizedproposedtitle !== '' &&
            $normalizedproposedtitle ===
                $normalizedexistingtitle
        ) {
            $score += 70;
            $reasons[] = 'same_normalized_title';
        } else {
            $titlesimilarity =
                $this->token_similarity(
                    $normalizedproposedtitle,
                    $normalizedexistingtitle
                );

            if ($titlesimilarity >= 80) {
                $score += 55;
                $reasons[] =
                    'very_similar_title';
            } else if ($titlesimilarity >= 60) {
                $score += 40;
                $reasons[] =
                    'similar_title';
            } else if ($titlesimilarity >= 40) {
                $score += 20;
                $reasons[] =
                    'partially_similar_title';
            }
        }

        $descriptionsimilarity =
            $this->token_similarity(
                $this->normalize(
                    $proposeddescription
                ),
                $this->normalize(
                    $existingdescription
                )
            );

        if ($descriptionsimilarity >= 70) {
            $score += 20;
            $reasons[] =
                'similar_description';
        } else if ($descriptionsimilarity >= 45) {
            $score += 10;
            $reasons[] =
                'partially_similar_description';
        }

        if (
            $proposeduserid !== null &&
            $existinguserid !== null &&
            $proposeduserid === $existinguserid
        ) {
            $score += 10;
            $reasons[] = 'same_target_user';
        }

        return [
            min(100, $score),
            $reasons,
        ];
    }

    private function normalize(
        string $value
    ): string {
        $value = \core_text::strtolower(
            trim($value)
        );

        $value = preg_replace(
            '/[^\pL\pN]+/u',
            ' ',
            $value
        ) ?? '';

        $tokens = array_filter(
            preg_split(
                '/\s+/u',
                $value
            ) ?: [],
            static fn(string $token): bool =>
                \core_text::strlen($token) >= 3
        );

        $tokens = array_values(
            array_unique($tokens)
        );

        sort($tokens);

        return implode(' ', $tokens);
    }

    private function token_similarity(
        string $left,
        string $right
    ): int {
        if ($left === '' || $right === '') {
            return 0;
        }

        if ($left === $right) {
            return 100;
        }

        $lefttokens =
            array_values(array_filter(
                explode(' ', $left)
            ));

        $righttokens =
            array_values(array_filter(
                explode(' ', $right)
            ));

        if (
            $lefttokens === [] ||
            $righttokens === []
        ) {
            return 0;
        }

        $intersection = array_intersect(
            $lefttokens,
            $righttokens
        );

        $union = array_unique(array_merge(
            $lefttokens,
            $righttokens
        ));

        return (int)round(
            count($intersection) /
            max(1, count($union)) *
            100
        );
    }
}