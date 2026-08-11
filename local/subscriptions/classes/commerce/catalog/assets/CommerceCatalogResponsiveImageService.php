<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\assets;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides responsive derivatives for the compact product artwork used across Commerce.
 *
 * The original catalogue media remain the source of truth. Derivatives are generated lazily,
 * stored in the same Moodle file area and invalidated by the source content hash.
 */
final class CommerceCatalogResponsiveImageService {
    /** @var array<string,array{src:string,srcset:string,width:int,height:int,role:string}|null> */
    private static array $requestcache = [];

    /** @var array<string,array{widths:int[],ratio:array{int,int}}> */
    private const PRESETS = [
        CommerceCatalogMediaManager::ROLE_STOREFRONT => [
            'widths' => [480, 800],
            'ratio' => [4, 3],
        ],
        CommerceCatalogMediaManager::ROLE_RECOMMENDATION => [
            'widths' => [360, 720],
            'ratio' => [4, 3],
        ],
        CommerceCatalogMediaManager::ROLE_CHECKOUT => [
            'widths' => [160, 320],
            'ratio' => [1, 1],
        ],
        CommerceCatalogMediaManager::ROLE_RESOURCES => [
            'widths' => [240, 480],
            'ratio' => [4, 5],
        ],
    ];

    public function __construct(private readonly CommerceCatalogMediaManager $media) {
    }

    public static function create(): self {
        return new self(new CommerceCatalogMediaManager(\context_system::instance()));
    }

    /**
     * @return array{src:string,srcset:string,width:int,height:int,role:string}|null
     */
    public function resolve(int $productid, string $role): ?array {
        $role = trim(strtolower($role), '/');
        if (!isset(self::PRESETS[$role])) {
            throw new \coding_exception('Unsupported responsive catalogue media role: ' . $role);
        }
        $cachekey = $productid . ':' . $role;
        if (array_key_exists($cachekey, self::$requestcache)) {
            return self::$requestcache[$cachekey];
        }

        $preset = self::PRESETS[$role];
        self::$requestcache[$cachekey] = $this->media->get_responsive_urls(
            $productid,
            $role,
            $preset['widths'],
            $preset['ratio'][0],
            $preset['ratio'][1]
        );
        return self::$requestcache[$cachekey];
    }
}
