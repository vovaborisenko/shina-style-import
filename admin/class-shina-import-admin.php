<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/vovaborisenko
 * @since      1.0.0
 *
 * @package    Shina_Import
 * @subpackage Shina_Import/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shina_Import
 * @subpackage Shina_Import/admin
 * @author     Uladzimir Barysenka <vovaborisenko@live.com>
 */
class Shina_Import_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

    public function add_menu() {
        add_menu_page(
            'ShinaStyle Import',
            'ShS Import',
            'manage_options',
            'shina-style-import',
            array($this, 'render_main_page'),
            'dashicons-forms',
            6
        );
    }

    public function add_wp_cron_schedules( $schedules ) {
        // add a 'minutely' schedule to the existing set
        $schedules['minutely'] = array(
            'interval' => 60,
            'display' => __('Once Minute')
        );

        return $schedules;
    }

    public function heartbeat_send( $response ): array {
        global $wpdb;

        $processes = $wpdb->get_results( 'SELECT * FROM ' . SHINA_IMPORT_TABLE_PROCESSES, ARRAY_A );

        if (!empty($processes)) {
            $process_import = $processes[0];
            $process_import_width = round(( $process_import['row_processed'] / ($process_import['row_count'] ?: 1)) * 100 );
            $process_import_status = $process_import['status'];

            $messages = [
                'new_file' => '<div class="alert alert-success">Файл обновлен, обновление товаров начнется в течение минуты.</div>',
                'started'  => '<div class="alert alert-success">Обновление товаров выполняется.</div>',
                'importing'=> '<div class="alert alert-success">Обновление товаров выполняется.</div>',
                'imported' => '<div class="alert alert-success">Обновление товаров выполнено.</div>',
                'finished' => '<div class="alert alert-success">Обновление товаров успешно выполнено. Если недавно был изменен файл ' . SHINA_IMPORT_FILE_NAME . ', импорт начнется автоматически в течение минуты.</div>',
                'error'    => '<div class="alert alert-warning">' . $process_import['msg'] . '</div>',
            ];

            $progress_style = [
                'new_file'  => 'display: none;',
                'started'   => 'display: block;',
                'importing' => 'display: block;',
                'exporting' => 'display: block;',
                'imported'  => 'display: none;',
                'exported'  => 'display: none;',
                'finished'  => 'display: none;',
                'error'     => 'display: none;',
            ];

            $progress_bar_style = [
                'new_file'  => 'display: none;',
                'started'   => 'width: ' . $process_import_width . '%',
                'importing' => 'width: ' . $process_import_width . '%',
                'imported'  => 'display: none;',
                'exported'  => 'display: none;',
                'finished'  => 'display: none;',
                'error'     => 'display: none;',
            ];

            $response[$this->plugin_name] = [
                'status'                => $process_import_status,
                'message'               => $messages[$process_import_status],
                'progress_style'        => $progress_style[$process_import_status],
                'progress_bar_style'    => $progress_bar_style[$process_import_status],
                'percent'               => $process_import_width . '%',
                'file_mod_time'         => date( 'F d Y H:i:s', $process_import['file_mod_time'] + 3 * 3600 )
            ];
        }

        return $response;
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shina_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shina_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shina-import-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-bootstrap', plugin_dir_url( __FILE__ ) . 'css/bootstrap.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shina_Import_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shina_Import_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shina-import-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.js', array( 'jquery' ), $this->version, false );

	}

    public function render_main_page() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/shina-import-admin-display.php';
    }

}
