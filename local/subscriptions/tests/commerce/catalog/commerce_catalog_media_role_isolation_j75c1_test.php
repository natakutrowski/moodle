<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_catalog_media_role_isolation_j75c1_test
        extends \advanced_testcase {

    public function test_media_manager_never_deletes_the_whole_product_area(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/catalog/assets/'
            . 'CommerceCatalogMediaManager.php'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/(?<![A-Za-z0-9_:])delete_area_files\\s*\\(/',
            $this->without_php_comments($source)
        );
        $this->assertStringContainsString(
            'delete_role_files',
            $source
        );
        $this->assertStringContainsString(
            '$file->get_filepath() !== $rolepath',
            $source
        );
        $this->assertStringContainsString(
            '$file->delete()',
            $source
        );
    }


    private function without_php_comments(string $source): string {
        $tokens = token_get_all($source);
        $result = '';

        foreach ($tokens as $token) {
            if (
                is_array($token)
                && in_array(
                    $token[0],
                    [T_COMMENT, T_DOC_COMMENT],
                    true
                )
            ) {
                continue;
            }

            $result .= is_array($token) ? $token[1] : $token;
        }

        return $result;
    }

    public function test_store_and_delete_both_use_role_scoped_cleanup(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/catalog/assets/'
            . 'CommerceCatalogMediaManager.php'
        );

        $this->assertGreaterThanOrEqual(
            2,
            substr_count(
                $source,
                '$this->delete_role_files($productid, $role);'
            )
        );
    }
}
