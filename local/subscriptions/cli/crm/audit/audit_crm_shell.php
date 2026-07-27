<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\navigation\CrmNavigationKeys;

if (PHP_SAPI !== 'cli') {
    throw new coding_exception(
        'This script can only be executed from CLI.'
    );
}

$options = getopt(
    '',
    [
        'strict',
        'help',
    ]
);

if (isset($options['help'])) {
    echo implode(
        PHP_EOL,
        [
            'CRM Shell audit',
            '',
            'Usage:',
            '  php local/subscriptions/cli/crm/audit/audit_crm_shell.php',
            '  php local/subscriptions/cli/crm/audit/audit_crm_shell.php --strict',
            '',
            '--strict  Return a non-zero status when the audit detects an error.',
            '--help    Display this help.',
            '',
        ]
    );

    exit(0);
}

$strict = isset(
    $options['strict']
);

$pluginroot = dirname(__DIR__);

$workspacerendererpath =
    $pluginroot .
    '/classes/crm/layout/' .
    'CrmWorkspaceRenderer.php';

$pageconfiguratorpath =
    $pluginroot .
    '/classes/crm/layout/' .
    'CrmPageConfigurator.php';

$breadcrumbrendererpath =
    $pluginroot .
    '/classes/crm/navigation/' .
    'CrmBreadcrumbRenderer.php';

/**
 * Reads one source file for architecture checks.
 *
 * @param string $path
 * @return string|null
 */
function read_source_file(
    string $path
): ?string {
    if (!is_file($path)) {
        return null;
    }

    $source = file_get_contents(
        $path
    );

    return $source === false
        ? null
        : $source;
}

/**
 * Detects forbidden primary Moodle headings.
 *
 * Calls without an explicit level use Moodle's default heading level 2.
 * Explicit heading levels 1 and 2 are forbidden inside CRM pages.
 * Heading levels 3 and above are valid section headings.
 *
 * @param string $source
 * @return bool
 */
function has_forbidden_primary_heading(
    string $source
): bool {
    $tokens = token_get_all(
        $source
    );

    $count = count(
        $tokens
    );

    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];

        if (
            !is_array($token) ||
            $token[0] !== T_VARIABLE ||
            $token[1] !== '$OUTPUT'
        ) {
            continue;
        }

        $cursor = $index + 1;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            $tokens[$cursor] !== T_OBJECT_OPERATOR
        ) {
            continue;
        }

        $cursor++;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            !is_array($tokens[$cursor]) ||
            $tokens[$cursor][0] !== T_STRING ||
            strtolower($tokens[$cursor][1]) !==
                'heading'
        ) {
            continue;
        }

        $cursor++;

        while (
            $cursor < $count &&
            is_array($tokens[$cursor]) &&
            $tokens[$cursor][0] === T_WHITESPACE
        ) {
            $cursor++;
        }

        if (
            $cursor >= $count ||
            $tokens[$cursor] !== '('
        ) {
            continue;
        }

        $depth = 1;
        $argumentindex = 0;
        $secondargument = '';

        for (
            $cursor++;
            $cursor < $count && $depth > 0;
            $cursor++
        ) {
            $current = $tokens[$cursor];

            $text = is_array($current)
                ? $current[1]
                : $current;

            if ($text === '(') {
                $depth++;

                if ($argumentindex === 1) {
                    $secondargument .= $text;
                }

                continue;
            }

            if ($text === ')') {
                $depth--;

                if (
                    $depth > 0 &&
                    $argumentindex === 1
                ) {
                    $secondargument .= $text;
                }

                continue;
            }

            if (
                $depth === 1 &&
                $text === ','
            ) {
                $argumentindex++;
                continue;
            }

            if ($argumentindex === 1) {
                $secondargument .= $text;
            }
        }

        /*
         * No explicit level means Moodle's default level 2.
         */
        if ($argumentindex === 0) {
            return true;
        }

        $level = trim(
            $secondargument
        );

        if (
            $level === '1' ||
            $level === '2'
        ) {
            return true;
        }
    }

    return false;
}

$pages = [
    'admin/dashboard.php' =>
        CrmNavigationKeys::DASHBOARD,

    'admin/users/index.php' =>
        CrmNavigationKeys::USERS,

    'admin/users/view.php' =>
        CrmNavigationKeys::USERS,

    'admin/users/email.php' =>
        CrmNavigationKeys::USERS,

    'admin/users/email_preview.php' =>
        CrmNavigationKeys::USERS,

    'admin/users/reset_password.php' =>
        CrmNavigationKeys::USERS,

    'admin/inbox/index.php' =>
        CrmNavigationKeys::INBOX,

    'admin/inbox/thread.php' =>
        CrmNavigationKeys::INBOX,

    'admin/inbox/reply.php' =>
        CrmNavigationKeys::INBOX,

    'admin/inbox/diagnostics.php' =>
        CrmNavigationKeys::INBOX,

    'admin/inbox/ai_diagnostics.php' =>
        CrmNavigationKeys::INBOX,

    'admin/work/index.php' =>
        CrmNavigationKeys::WORK,

    'admin/work/view.php' =>
        CrmNavigationKeys::WORK,

    'admin/work/create.php' =>
        CrmNavigationKeys::WORK,

    'admin/work/teams.php' =>
        CrmNavigationKeys::WORK,

    'admin/assistant/index.php' =>
        CrmNavigationKeys::ASSISTANT,

    'admin/assistant/plan.php' =>
        CrmNavigationKeys::ASSISTANT,

    'admin/assistant/work_item.php' =>
        CrmNavigationKeys::ASSISTANT,

    'admin/commerce/index.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/subscriptions/index.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/subscriptions/view.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/subscriptions/add.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/subscriptions/edit.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/subscriptions/export.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/imports/index.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/imports/process.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/manage.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/plans/prices.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/plans/translations.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/plans/entitlements.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/plans/upgrades.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/scopes/translations.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/digital/products/index.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/digital/products/edit.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/digital/purchases/index.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/digital/purchases/view.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/digital/stats.php' =>
        CrmNavigationKeys::COMMERCE,

    'admin/help/index.php' =>
        CrmNavigationKeys::HELP,

    'admin/help/article.php' =>
        CrmNavigationKeys::HELP,

    'admin/help/guide.php' =>
        CrmNavigationKeys::HELP,

    'admin/help/diagnostics.php' =>
        CrmNavigationKeys::HELP,

    'admin/tools/index.php' =>
        CrmNavigationKeys::TOOLS,

    'admin/tools/history.php' =>
        CrmNavigationKeys::TOOLS,

    'admin/tools/action.php' =>
        CrmNavigationKeys::TOOLS,
];

$alreadyexpected = array_keys(
    $pages
);

$headerexpected = [
    'admin/dashboard.php',

    'admin/users/index.php',
    'admin/users/view.php',
    'admin/users/email.php',
    'admin/users/email_preview.php',
    'admin/users/reset_password.php',

    'admin/inbox/index.php',
    'admin/inbox/thread.php',
    'admin/inbox/reply.php',
    'admin/inbox/diagnostics.php',
    'admin/inbox/ai_diagnostics.php',

    'admin/work/index.php',
    'admin/work/view.php',
    'admin/work/create.php',
    'admin/work/teams.php',

    'admin/assistant/index.php',
    'admin/assistant/plan.php',
    'admin/assistant/work_item.php',

    'admin/subscriptions/index.php',
    'admin/subscriptions/view.php',
    'admin/subscriptions/add.php',
    'admin/subscriptions/edit.php',
    'admin/subscriptions/export.php',
    'admin/imports/index.php',
    'admin/imports/process.php',
    'admin/manage.php',
    'admin/plans/prices.php',
    'admin/plans/translations.php',
    'admin/plans/entitlements.php',
    'admin/plans/upgrades.php',
    'admin/scopes/translations.php',
    'admin/digital/products/index.php',
    'admin/digital/products/edit.php',
    'admin/digital/purchases/index.php',
    'admin/digital/purchases/view.php',
    'admin/digital/stats.php',

    'admin/help/article.php',
    'admin/help/guide.php',
    'admin/help/diagnostics.php',

    'admin/tools/index.php',
    'admin/tools/history.php',
    'admin/tools/action.php',
];

$breadcrumbexpected = [
    'admin/users/view.php',
    'admin/users/email.php',
    'admin/users/email_preview.php',
    'admin/users/reset_password.php',

    'admin/inbox/thread.php',
    'admin/inbox/reply.php',
    'admin/inbox/diagnostics.php',
    'admin/inbox/ai_diagnostics.php',

    'admin/work/view.php',
    'admin/work/create.php',
    'admin/work/teams.php',

    'admin/assistant/plan.php',
    'admin/assistant/work_item.php',

    'admin/subscriptions/index.php',
    'admin/subscriptions/view.php',
    'admin/subscriptions/add.php',
    'admin/subscriptions/edit.php',
    'admin/subscriptions/export.php',
    'admin/imports/index.php',
    'admin/imports/process.php',
    'admin/manage.php',
    'admin/plans/prices.php',
    'admin/plans/translations.php',
    'admin/plans/entitlements.php',
    'admin/plans/upgrades.php',
    'admin/scopes/translations.php',
    'admin/digital/products/index.php',
    'admin/digital/products/edit.php',
    'admin/digital/purchases/index.php',
    'admin/digital/purchases/view.php',
    'admin/digital/stats.php',

    'admin/help/article.php',
    'admin/help/guide.php',
    'admin/help/diagnostics.php',

    'admin/tools/history.php',
    'admin/tools/action.php',
];

$breadcrumbforbidden = [
    'admin/dashboard.php',
    'admin/users/index.php',
    'admin/inbox/index.php',
    'admin/work/index.php',
    'admin/assistant/index.php',
    'admin/commerce/index.php',
    'admin/help/index.php',
    'admin/tools/index.php',
];

$returnurlfiles = [
    'admin/users/email.php',
    'admin/assistant/action.php',
    'admin/help/guide_action.php',
    'admin/help/onboarding_action.php',
];

$backlinkexpected = [
    'admin/subscriptions/view.php',
    'admin/subscriptions/add.php',
    'admin/subscriptions/edit.php',
    'admin/subscriptions/export.php',
    'admin/imports/index.php',
    'admin/imports/process.php',
    'admin/manage.php',
    'admin/plans/prices.php',
    'admin/plans/translations.php',
    'admin/plans/entitlements.php',
    'admin/plans/upgrades.php',
    'admin/scopes/translations.php',
    'admin/digital/products/index.php',
    'admin/digital/products/edit.php',
    'admin/digital/purchases/index.php',
    'admin/digital/purchases/view.php',
    'admin/digital/stats.php',
];

$actionendpoints = [
    'admin/subscriptions/delete.php',

    'admin/digital/products/delete.php',
    'admin/digital/products/duplicate.php',
    'admin/digital/products/toggle.php',

    'admin/digital/purchases/cancel.php',
    'admin/digital/purchases/check_provider.php',
    'admin/digital/purchases/extend_token.php',
    'admin/digital/purchases/regenerate_token.php',
    'admin/digital/purchases/resend_email.php',
];

$contextchecks = [
    'admin/work/index.php' =>
        'HelpContext::WORK_ITEMS',

    'admin/assistant/index.php' =>
        'HelpContext::ASSISTANT',

    'admin/commerce/index.php' =>
        'HelpContext::COMMERCE',

    'admin/subscriptions/index.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/subscriptions/view.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/subscriptions/add.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/subscriptions/edit.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/subscriptions/export.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/imports/index.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/imports/process.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/manage.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/plans/prices.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/plans/translations.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/plans/entitlements.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/plans/upgrades.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/scopes/translations.php' =>
        'HelpContext::SUBSCRIPTIONS',

    'admin/digital/products/index.php' =>
        'HelpContext::DIGITAL_PURCHASES',

    'admin/digital/products/edit.php' =>
        'HelpContext::DIGITAL_PURCHASES',

    'admin/digital/purchases/index.php' =>
        'HelpContext::DIGITAL_PURCHASES',

    'admin/digital/purchases/view.php' =>
        'HelpContext::DIGITAL_PURCHASES',

    'admin/digital/stats.php' =>
        'HelpContext::DIGITAL_PURCHASES',

    'admin/tools/index.php' =>
        'HelpContext::ADMIN_TOOLS',
];

$failures = 0;
$warnings = 0;

$workspacerenderersource =
    read_source_file(
        $workspacerendererpath
    );

$pageconfiguratorsource =
    read_source_file(
        $pageconfiguratorpath
    );

$breadcrumbrenderersource =
    read_source_file(
        $breadcrumbrendererpath
    );

if ($workspacerenderersource === null) {
    echo '[ERROR] Unable to read ' .
        'CrmWorkspaceRenderer.php' .
        PHP_EOL;

    $failures++;
} else if (
    !str_contains(
        $workspacerenderersource,
        'CommandCenterRenderer::render'
    )
) {
    echo '[ERROR] CRM Workspace does not host ' .
        'the global Command Center.' .
        PHP_EOL;

    $failures++;
} else {
    echo '[OK] CRM Workspace hosts the global ' .
        'Command Center.' .
        PHP_EOL;
}

if ($pageconfiguratorsource === null) {
    echo '[ERROR] Unable to read ' .
        'CrmPageConfigurator.php' .
        PHP_EOL;

    $failures++;
} else if (
    !str_contains(
        $pageconfiguratorsource,
        "'local_subscriptions/command_center'"
    )
) {
    echo '[ERROR] CRM page configurator does not ' .
        'load the Command Center AMD module.' .
        PHP_EOL;

    $failures++;
} else {
    echo '[OK] CRM page configurator loads the ' .
        'Command Center AMD module.' .
        PHP_EOL;
}

if ($breadcrumbrenderersource === null) {
    echo '[ERROR] Unable to read ' .
        'CrmBreadcrumbRenderer.php' .
        PHP_EOL;

    $failures++;
} else if (
    !str_contains(
        $breadcrumbrenderersource,
        'aria-current'
    ) ||
    !str_contains(
        $breadcrumbrenderersource,
        "'nav'"
    )
) {
    echo '[ERROR] CRM breadcrumb renderer is missing ' .
        'semantic navigation markers.' .
        PHP_EOL;

    $failures++;
} else {
    echo '[OK] CRM breadcrumb renderer is semantic.' .
        PHP_EOL;
}

foreach ($pages as $relativepath => $navigationkey) {
    $fullpath =
        $pluginroot .
        DIRECTORY_SEPARATOR .
        $relativepath;

    $source =
        read_source_file(
            $fullpath
        );

    if ($source === null) {
        echo '[MISSING] ' .
            $relativepath .
            PHP_EOL;

        $failures++;
        continue;
    }

    $hasconfigurator = str_contains(
        $source,
        'CrmPageConfigurator::configure'
    );

    $hasstart = str_contains(
        $source,
        'CrmWorkspaceRenderer::start'
    );

    $hasend = str_contains(
        $source,
        'CrmWorkspaceRenderer::end'
    );

    $expectedconstant = match (
        $navigationkey
    ) {
        CrmNavigationKeys::DASHBOARD =>
            'DASHBOARD',

        CrmNavigationKeys::USERS =>
            'USERS',

        CrmNavigationKeys::INBOX =>
            'INBOX',

        CrmNavigationKeys::WORK =>
            'WORK',

        CrmNavigationKeys::ASSISTANT =>
            'ASSISTANT',

        CrmNavigationKeys::COMMERCE =>
            'COMMERCE',

        CrmNavigationKeys::HELP =>
            'HELP',

        CrmNavigationKeys::TOOLS =>
            'TOOLS',

        default =>
            '',
    };

    $hasnavigationkey =
        $expectedconstant !== '' &&
        str_contains(
            $source,
            'CrmNavigationKeys::' .
            $expectedconstant
        );

    $directlayout = str_contains(
        $source,
        'set_pagelayout('
    );

    $directstylesheet =
        str_contains(
            $source,
            'plugin_stylesheet_page'
        ) &&
        !$hasconfigurator;

    $localcommandcenterrender =
        str_contains(
            $source,
            'CommandCenterRenderer::render'
        );

    $localcommandcenteramd =
        str_contains(
            $source,
            "'local_subscriptions/command_center'"
        );

    $manualbackarrow =
        str_contains(
            $source,
            "'← '"
        ) ||
        str_contains(
            $source,
            '"← "'
        );

    $haspageheader = str_contains(
        $source,
        'CrmPageHeader::render'
    );

    $expectsheader = in_array(
        $relativepath,
        $headerexpected,
        true
    );

    $hasbreadcrumb = str_contains(
        $source,
        'CrmBreadcrumbRenderer::render'
    );

    $expectsbreadcrumb = in_array(
        $relativepath,
        $breadcrumbexpected,
        true
    );

    $forbidsbreadcrumb = in_array(
        $relativepath,
        $breadcrumbforbidden,
        true
    );

    $hasbacklink = str_contains(
        $source,
        'CrmBackLinkRenderer::render'
    );

    $expectsbacklink = in_array(
        $relativepath,
        $backlinkexpected,
        true
    );

    /*
     * The double-quoted PHP string keeps the regular expression readable.
     * Backslashes used by the regex itself are escaped once for PHP.
     *
     * It detects calls such as:
     *   html_writer::tag('h1', ...)
     *   html_writer::tag("h1", ...)
     */
    $manualmainheading =
        has_forbidden_primary_heading(
            $source
        ) ||
        preg_match(
            '~html_writer::tag\s*'
            . '\(\s*'
            . '[\'"]h1[\'"]'
            . '~',
            $source
        ) === 1;

    $ismigrated =
        $hasconfigurator &&
        $hasstart &&
        $hasend &&
        $hasnavigationkey;

    if (
        $localcommandcenterrender ||
        $localcommandcenteramd
    ) {
        $localparts = [];

        if ($localcommandcenterrender) {
            $localparts[] =
                'local Command Center renderer';
        }

        if ($localcommandcenteramd) {
            $localparts[] =
                'local Command Center AMD load';
        }

        echo '[ERROR] ' .
            $relativepath .
            ': ' .
            implode(
                ', ',
                $localparts
            ) .
            PHP_EOL;

        $failures++;
        continue;
    }

    if ($manualbackarrow) {
        echo '[ERROR] ' .
            $relativepath .
            ': manual CRM back arrow detected.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        $expectsheader &&
        !$haspageheader
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing CRM page header.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if ($manualmainheading) {
        echo '[ERROR] ' .
            $relativepath .
            ': manual primary heading detected.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        $expectsbreadcrumb &&
        !$hasbreadcrumb
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing CRM breadcrumb.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        $forbidsbreadcrumb &&
        $hasbreadcrumb
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': breadcrumb must not be rendered ' .
            'on a CRM root page.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        $expectsbacklink &&
        !$hasbacklink
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing CRM back link.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if ($ismigrated) {
        $messages = [];

        if ($directlayout) {
            $messages[] =
                'direct set_pagelayout()';
        }

        if ($directstylesheet) {
            $messages[] =
                'direct CRM stylesheet';
        }

        if ($messages === []) {
            echo '[OK] ' .
                $relativepath .
                ' [' .
                $navigationkey .
                ']' .
                PHP_EOL;
        } else {
            echo '[WARNING] ' .
                $relativepath .
                ': ' .
                implode(
                    ', ',
                    $messages
                ) .
                PHP_EOL;

            $warnings++;
        }

        continue;
    }

    $details = [];

    if (!$hasconfigurator) {
        $details[] =
            'configurator';
    }

    if (!$hasstart) {
        $details[] =
            'workspace start';
    }

    if (!$hasend) {
        $details[] =
            'workspace end';
    }

    if (!$hasnavigationkey) {
        $details[] =
            'navigation key';
    }

    $expectednow = in_array(
        $relativepath,
        $alreadyexpected,
        true
    );

    if ($expectednow) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing ' .
            implode(
                ', ',
                $details
            ) .
            PHP_EOL;

        $failures++;
    } else {
        echo '[PENDING] ' .
            $relativepath .
            ': missing ' .
            implode(
                ', ',
                $details
            ) .
            PHP_EOL;
    }
}

foreach ($actionendpoints as $relativepath) {
    $source = read_source_file(
        $pluginroot . '/' . $relativepath
    );

    if ($source === null) {
        echo '[ERROR] Unable to read ' .
            $relativepath .
            PHP_EOL;

        $failures++;
        continue;
    }

    $hassecurity =
        str_contains(
            $source,
            'AdminSecurity::require'
        );

    $hassesskey =
        str_contains(
            $source,
            'require_sesskey'
        );

    $hasoutput =
        str_contains(
            $source,
            '$OUTPUT->header'
        ) ||
        str_contains(
            $source,
            'CrmWorkspaceRenderer::start'
        );

    if (!$hassecurity) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing AdminSecurity::require().' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (!$hassesskey) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing require_sesskey().' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if ($hasoutput) {
        echo '[ERROR] ' .
            $relativepath .
            ': action endpoint must not render a page.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        !str_contains(
            $source,
            'subscription_config::'
        )
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': redirect does not use subscription_config.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    echo '[OK] ' .
        $relativepath .
        ' is a secured redirect-only action endpoint.' .
        PHP_EOL;
}

foreach (
    $returnurlfiles as $relativepath
) {
    $path =
        $pluginroot .
        '/' .
        $relativepath;

    $source =
        read_source_file(
            $path
        );

    if ($source === null) {
        echo '[ERROR] Unable to read ' .
            $relativepath .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        str_contains(
            $source,
            'new moodle_url($returnurl)'
        )
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': raw returnurl converted directly ' .
            'to moodle_url.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        !str_contains(
            $source,
            'CrmReturnUrlResolver::resolve'
        )
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': missing CRM return URL resolver.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    echo '[OK] ' .
        $relativepath .
        ' uses the CRM return URL resolver.' .
        PHP_EOL;
}

foreach (
    $contextchecks as
    $relativepath => $expectedcontext
) {
    $source =
        read_source_file(
            $pluginroot .
            '/' .
            $relativepath
        );

    if ($source === null) {
        echo '[ERROR] Unable to read ' .
            $relativepath .
            PHP_EOL;

        $failures++;
        continue;
    }

    if (
        !str_contains(
            $source,
            $expectedcontext
        )
    ) {
        echo '[ERROR] ' .
            $relativepath .
            ': incorrect CRM help context.' .
            PHP_EOL;

        $failures++;
        continue;
    }

    echo '[OK] ' .
        $relativepath .
        ' uses ' .
        $expectedcontext .
        '.' .
        PHP_EOL;
}

echo PHP_EOL;

echo 'Failures: ' .
    $failures .
    PHP_EOL;

echo 'Warnings: ' .
    $warnings .
    PHP_EOL;

if (
    $strict &&
    $failures > 0
) {
    exit(1);
}

exit(0);
