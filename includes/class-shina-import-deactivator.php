<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/vovaborisenko
 * @since      1.0.0
 *
 * @package    Shina_Import
 * @subpackage Shina_Import/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Shina_Import
 * @subpackage Shina_Import/includes
 * @author     Uladzimir Barysenka <vovaborisenko@live.com>
 */
class Shina_Import_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        global $wpdb;

        $wpdb -> query("DROP TABLE " . SHINA_IMPORT_TABLE_PROCESSES);

        wp_clear_scheduled_hook( 'shina_import_cron_event' );
	}

}
