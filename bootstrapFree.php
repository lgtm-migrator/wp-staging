<?php

namespace WPStaging\Bootstrap\V1;

require_once __DIR__ . '/Bootstrap/V1/Requirements/WpstgFreeRequirements.php';
require_once __DIR__ . '/Bootstrap/V1/WpstgBootstrap.php';

if (!class_exists(WpstgFreeBootstrap::class)) {
    class WpstgFreeBootstrap extends WpstgBootstrap
    {
        protected function afterBootstrap()
        {
            // WP STAGING version number
            if (!defined('WPSTG_VERSION')) {
                define('WPSTG_VERSION', '1.2.3');
            }

            // Compatible up to WordPress Version
            if (!defined('WPSTG_COMPATIBLE')) {
                define('WPSTG_COMPATIBLE', '5.6');
            }
        }
    }
}

$bootstrap = new WpstgFreeBootstrap(WPSTG_FREE_ENTRYPOINT, new WpstgFreeRequirements(WPSTG_FREE_ENTRYPOINT));

add_action('plugins_loaded', [$bootstrap, 'checkRequirements'], 5);
add_action('plugins_loaded', [$bootstrap, 'bootstrap'], 10);

/** Installation Hooks */
if (!class_exists('WPStaging\Install')) {
    require_once __DIR__ . "/install.php";

    $install = new \WPStaging\Install($bootstrap);
    register_activation_hook(WPSTG_FREE_ENTRYPOINT, [$install, 'activation']);
}
