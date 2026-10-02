<?php

namespace Restruct\NlBrowser;

use SilverStripe\Admin\ModelAdmin;

/**
 * BROWSER-TEST FIXTURE ONLY - the CMS screen the specs open: /admin/nl-browser/records
 * (see NlBRecord for why this never loads in a real install).
 */
class NlBAdmin extends ModelAdmin
{
    private static $url_segment = 'nl-browser';

    private static $menu_title = 'NamedLinkField browser test';

    # Keyed managed_models (SS5 and SS6): 'records' becomes the URL segment, so specs need not
    # spell out the sanitised namespaced class name.
    private static $managed_models = [
        'records' => [
            'dataClass' => NlBRecord::class,
            'title' => 'Records',
        ],
    ];
}
