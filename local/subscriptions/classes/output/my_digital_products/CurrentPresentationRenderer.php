<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_digital_products;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\digital\library\CommerceDigitalLibrary;
use local_subscriptions\commerce\digital\library\CommerceDigitalResourcePresentation;
use local_subscriptions\url\UrlFactory;
use renderer_base;

/**
 * Presentation adapter for the customer digital product library.
 */
final class CurrentPresentationRenderer {
    public function __construct(
        private readonly CommerceDigitalLibrary $library,
        private readonly bool $isadminview
    ) {
    }

    public function export(renderer_base $output): array {
        $resources = array_map(
            function(CommerceDigitalResourcePresentation $resource): array {
                $data = $resource->export();
                $data['productlabel'] = get_string('digital_library_view_product', 'local_subscriptions');
                $data['fileslabel'] = get_string(
                    $data['hasmultiplefiles'] ? 'digital_library_files' : 'digital_library_file',
                    'local_subscriptions'
                );

                foreach ($data['downloads'] as &$download) {
                    $download['typelabel'] = get_string('digital_library_file_type', 'local_subscriptions');
                    $download['sizelabel'] = get_string('digital_library_file_size', 'local_subscriptions');
                    $download['downloadedlabel'] = $download['hasbeendownloaded']
                        ? get_string('digital_library_already_downloaded', 'local_subscriptions')
                        : get_string('digital_library_not_downloaded_yet', 'local_subscriptions');
                    $download['downloadcountlabel'] = '';
                    if ($download['hasbeendownloaded']) {
                        $download['downloadcountlabel'] = get_string(
                            $download['downloadcount'] === 1
                                ? 'digital_library_download_count_one'
                                : 'digital_library_download_count_many',
                            'local_subscriptions',
                            $download['downloadcount']
                        );
                    }
                    $download['lastdownloadlabel'] = get_string('digital_library_last_download', 'local_subscriptions');
                    $download['historyunavailablelabel'] = get_string(
                        'digital_library_history_unavailable',
                        'local_subscriptions'
                    );
                    $download['downloadlabel'] = get_string('digital_library_download_file', 'local_subscriptions');
                    $download['downloadarialabel'] = get_string(
                        'digital_library_download_aria',
                        'local_subscriptions',
                        (object)[
                            'file' => $download['label'],
                            'product' => $data['title'],
                        ]
                    );
                }
                unset($download);

                return $data;
            },
            $this->library->get_resources()
        );

        return [
            'resources' => $resources,
            'hasresources' => $resources !== [],
            'resourcecount' => count($resources),
            'downloadableresourcecount' => $this->library->count_downloadable_resources(),
            'isadminview' => $this->isadminview,
            'emptytitle' => get_string('digital_library_empty_title', 'local_subscriptions'),
            'emptydescription' => get_string('digital_library_empty_description', 'local_subscriptions'),
            'catalogurl' => UrlFactory::digital_catalog()->out(false),
            'cataloglabel' => get_string('digital_library_open_catalog', 'local_subscriptions'),
        ];
    }
}
