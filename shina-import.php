<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/vovaborisenko
 * @since             1.1.1
 * @package           Shina_Import
 *
 * @wordpress-plugin
 * Plugin Name:       Shina Style Import
 * Plugin URI:        https://github.com/vovaborisenko/shina-style-import
 * Description:       Обновление цен и наличия на сайте путем записи нужных значений прямо в базу WP
 * Version:           1.1.1
 * Author:            Uladzimir Barysenka
 * Author URI:        https://github.com/vovaborisenko
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shina-import
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SHINA_IMPORT_PLUGIN_NAME', 'shina-import' );
define( 'SHINA_IMPORT_VERSION', '1.1.1' );
define( 'SHINA_IMPORT_FILE_NAME', 'sh-st_next.csv' );
define( 'SHINA_IMPORT_FEED_FILE_NAME', 'sh-st_next-yml-0.csv' );
define( 'SHINA_IMPORT_TABLE_PROCESSES', 'sh_processes' );
define( 'SHINA_IMPORT_TABLE_IMPORT', 'sh_import' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-shina-import-activator.php
 */
function activate_shina_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shina-import-activator.php';
	Shina_Import_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-shina-import-deactivator.php
 */
function deactivate_shina_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shina-import-deactivator.php';
	Shina_Import_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_shina_import' );
register_deactivation_hook( __FILE__, 'deactivate_shina_import' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-shina-import.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_shina_import() {

	$plugin = new Shina_Import();
	$plugin->run();

}
run_shina_import();
