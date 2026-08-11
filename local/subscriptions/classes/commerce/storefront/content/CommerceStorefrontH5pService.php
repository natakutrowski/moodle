<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\content;

defined('MOODLE_INTERNAL') || die();

/** Safe projection of Moodle Content Bank H5P packages. */
final class CommerceStorefrontH5pService {
    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /** @return array<int,string> */
    public function options(): array {
        $records = $this->db->get_records(
            'contentbank_content',
            ['contenttype' => 'contenttype_h5p'],
            'name ASC',
            'id,name,contextid,visibility'
        );
        $options = [0 => get_string(
            'commerce_storefront_h5p_none',
            'local_subscriptions'
        )];
        foreach ($records as $record) {
            $options[(int)$record->id] = format_string(
                (string)$record->name
            );
        }
        return $options;
    }

    public function has_options(): bool {
        return $this->db->record_exists(
            'contentbank_content',
            ['contenttype' => 'contenttype_h5p']
        );
    }

    public function embed_url(int $contentid): ?string {
        if ($contentid <= 0) {
            return null;
        }
        $content = $this->db->get_record(
            'contentbank_content',
            ['id' => $contentid],
            'id,contextid,contenttype',
            IGNORE_MISSING
        );
        if (
            !$content
            || (string)$content->contenttype
                !== 'contenttype_h5p'
        ) {
            return null;
        }

        $files = get_file_storage()->get_area_files(
            (int)$content->contextid,
            'contentbank',
            'public',
            (int)$content->id,
            'id DESC',
            false
        );
        foreach ($files as $file) {
            if (
                strtolower(pathinfo(
                    $file->get_filename(),
                    PATHINFO_EXTENSION
                )) !== 'h5p'
            ) {
                continue;
            }
            $packageurl = \moodle_url::make_pluginfile_url(
                (int)$content->contextid,
                'contentbank',
                'public',
                (int)$content->id,
                $file->get_filepath(),
                $file->get_filename(),
                false
            );
            return (new \moodle_url('/h5p/embed.php', [
                'url' => $packageurl->out(false),
            ]))->out(false);
        }
        return null;
    }
}
