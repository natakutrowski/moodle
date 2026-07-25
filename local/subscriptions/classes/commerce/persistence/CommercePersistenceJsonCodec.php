<?php

namespace local_subscriptions\commerce\persistence;

defined('MOODLE_INTERNAL') || die();

/** Deterministic JSON codec used at the Commerce persistence boundary. */
final class CommercePersistenceJsonCodec {

    public function encode(array $value): string {
        try {
            return json_encode(
                $this->canonicalise($value),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (\JsonException $exception) {
            throw new \coding_exception(
                'Unable to encode Commerce persistence JSON: ' . $exception->getMessage()
            );
        }
    }

    /** @return array<mixed> */
    public function decode(string $json): array {
        if (trim($json) === '') {
            throw new \coding_exception('Commerce persistence JSON cannot be empty.');
        }

        try {
            $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \coding_exception(
                'Unable to decode Commerce persistence JSON: ' . $exception->getMessage()
            );
        }

        if (!is_array($value)) {
            throw new \coding_exception('Commerce persistence JSON must contain an array or object.');
        }

        return $value;
    }

    private function canonicalise(mixed $value): mixed {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalise($item);
        }

        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }
}
