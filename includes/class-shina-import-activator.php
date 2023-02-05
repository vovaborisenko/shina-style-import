<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/vovaborisenko
 * @since      1.0.0
 *
 * @package    Shina_Import
 * @subpackage Shina_Import/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Shina_Import
 * @subpackage Shina_Import/includes
 * @author     Uladzimir Barysenka <vovaborisenko@live.com>
 */
class Shina_Import_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        global $wpdb;

        $wpdb -> query("DROP TABLE " . SHINA_IMPORT_TABLE_PROCESSES);

        $wpdb -> query(
            "CREATE TABLE `" . SHINA_IMPORT_TABLE_PROCESSES . "` (
                `ID` INT(10) NOT NULL AUTO_INCREMENT,
                `process_name` VARCHAR(25) NOT NULL COLLATE 'utf8_general_ci',
                `status` VARCHAR(25) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
                `file_mod_time` INT(10) NULL DEFAULT NULL,
                `row_processed` INT(10) NULL DEFAULT NULL,
                `row_count` INT(10) NULL DEFAULT NULL,
	            `msg` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci',
                PRIMARY KEY (`ID`) USING BTREE
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
            AUTO_INCREMENT=1
            ;"
        );

        wp_clear_scheduled_hook( 'shina_import_cron_event' );
        wp_schedule_event( time(), 'minutely', 'shina_import_cron_event');
	}

}
