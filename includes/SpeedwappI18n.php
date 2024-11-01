<?php
/*
 * This file is part of the Speedwapp Wordpress plugin.
 *
 * (c) Akambi Fagbohoun <contact@akambi-fagbohoun.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.9.0
 *
 * @author     akambi <akambi@speedwapp.com>
 */
if (!class_exists('Speedwapp_I18n')) {
    class Speedwapp_I18n
    {
        /**
         * Load the plugin text domain for translation.
         *
         * @since    0.9.0
         */
        public function load_plugin_textdomain()
        {
            load_plugin_textdomain(
                'speedwapp',
                false,
                dirname(dirname(plugin_basename(__FILE__))).'/languages/'
            );
        }
    }
}
