<?php

// Set the module directory so we know where JS, etc lives.
NamedLinkFormField::$module_dir = basename(dirname(__FILE__));

// Load required CSS (extra, doesn't seem to load via Ajax)
LeftAndMain::require_css(NamedLinkFormField::$module_dir . '/css/linkfield.css');