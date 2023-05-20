<?php
class Shina_Import_Cron {
	private string $file_name;
	private string $dir;
    private array $messages = [
        'new_file' => 'Файл обновлен, обновление товаров начнется в течение минуты.',
        'started'  => 'Обновление товаров выполняется.',
        'importing'=> 'Обновление товаров выполняется.',
        'imported' => 'Обновление товаров выполнено.',
        'finished' => 'Обновление товаров успешно выполнено. Если недавно был изменен файл ' . SHINA_IMPORT_FILE_NAME . ', импорт начнется автоматически в течение минуты.'
    ];

	private string $table_name = 'sh_import';

	public function __construct() {
		$dir = wp_upload_dir()['basedir'] . '/' . SHINA_IMPORT_PLUGIN_NAME;

		$this->dir = $dir;
		$this->file_name = $dir . '/' . SHINA_IMPORT_FILE_NAME;
	}

	public function check_file () {
		global $wpdb;

		$record = $wpdb->get_row( 'SELECT * FROM ' . SHINA_IMPORT_TABLE_PROCESSES, ARRAY_A );
		$dir = wp_upload_dir()['basedir'] . '/' . SHINA_IMPORT_PLUGIN_NAME;
		$file_name = $dir . '/' . SHINA_IMPORT_FILE_NAME;
		$file_mod_time = $record['file_mod_time'];

		if ( ! file_exists($this->file_name) ) {
			// изменяет инфо о шортимпорте
			$wpdb->replace( SHINA_IMPORT_TABLE_PROCESSES,
				[
					'ID' => 1,
					'status' => 'error',
					'process_name' => 'short_import',
					'msg' => 'Файл ' . SHINA_IMPORT_FILE_NAME . ' отсутствует в папке ' . $this->dir,
				]
			);

			return $record;
		}

		$new_file_mod_time = filemtime($this->file_name);

		// если файл изменился
		if ( $file_mod_time != $new_file_mod_time ) {
			// считает кол-во строк в файле
			$handle = fopen( $this->file_name, "r" );

			$row_count = 0;

			while ( !feof($handle) ) {
				$bufer = fread( $handle, 1048576 );
				$row_count += substr_count( $bufer, "\n" );
			}

			fclose($handle);

			// изменяет инфо о шортимпорте
			$wpdb->replace( SHINA_IMPORT_TABLE_PROCESSES,
				[
					'ID' => 1,
					'file_mod_time' => $new_file_mod_time,
					'status' 		=> 'new_file',
					'row_processed' => 1,
					'row_count' 	=> $row_count,
					'process_name'  => 'short_import',
                    'msg'           => $this->messages['new_file']
				]
			);
		}

		return $record;
	}

	public function import() {
		global $wpdb;

        set_time_limit(0);

        $finish = ini_get( 'max_execution_time' ) > 0
            ? microtime(true) + ini_get( 'max_execution_time' ) * 1000
            : 0;

		$record = $wpdb->get_row('SELECT * FROM ' . SHINA_IMPORT_TABLE_PROCESSES, ARRAY_A);
		$process_status = $record['status'];

		if ($process_status == 'new_file') {
			$wpdb->query("DROP TABLE $this->table_name");

			$wpdb->query(
				"CREATE TABLE $this->table_name (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`product_id` BIGINT(20) NOT NULL,
					`product_sku` VARCHAR(50) NOT NULL,
					`product_price` DECIMAL(10,2) NOT NULL,
					`product_nal` VARCHAR(50) NOT NULL,
					PRIMARY KEY (`id`)
				)
				COLLATE 'utf8_general_ci' ENGINE=MyISAM ROW_FORMAT=Dynamic AUTO_INCREMENT=1;"
			);

			// изменяет статус шортимпорта, создает пути к логам
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => 'started',
                    'msg'       => $this->messages['started']
                ],
				['ID' => 1, 'process_name' => 'short_import']
			);
            $process_status = 'started';
		}

		if ($process_status == 'started') {
			// изменяет статус шортимпорта
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => 'importing',
                    'msg'       => $this->messages['importing']
                ],
				['ID' => 1, 'process_name' => 'short_import']
			);

			$row_start = $record['row_processed'];
			$row_count = $record['row_count'];
            $row_current = $row_start;

			$cont = trim(file_get_contents($this->file_name));

			// определим разделитель
			$row_delimiter = !str_contains($cont, "\r\n") ? "\n" : "\r\n";

			$lines = explode($row_delimiter, trim($cont));
			$lines = array_filter($lines);
			$lines = array_map('trim', $lines);

            while (
                $row_current <= $row_count
                && ( !$finish || $finish > microtime(true) + 2000 )
            ) {
				$linedata = str_getcsv($lines[$row_current], ','); // linedata
				$product_sku = $linedata[4];
				$product_id = $this->get_product_id_by_sku($product_sku);
				$product_price = str_replace(',', '.', $linedata[0]);
				$product_store = $linedata[2];

				$wpdb->insert(
					$this->table_name,
					[
						'product_id' => $product_id,
						'product_sku' => $product_sku,
						'product_price' => $product_price,
						'product_nal' => $product_store
					],
					['%d', '%s', '%f', '%s']
				);

				// товара есть на сайте, нет наличия
				if ($product_id && !$product_store) {
					update_post_meta($product_id, '_regular_price', '');
					update_post_meta($product_id, '_price', '');
					update_post_meta($product_id, '_stock', '0');
					update_post_meta($product_id, '_stock_status', 'outofstock');
					update_post_meta($product_id, '_manage_stock', 'yes');
				}

				// товара есть на сайте, есть наличие наличия
				if ($product_id && $product_store) {
					update_post_meta($product_id, '_regular_price', $product_price);
					update_post_meta($product_id, '_price', $product_price);
					update_post_meta($product_id, '_stock', preg_replace('/\D/', '', $product_store));
					update_post_meta($product_id, '_stock_status', 'instock');
					update_post_meta($product_id, '_manage_stock', 'yes');
				}

				$wpdb->update(
					SHINA_IMPORT_TABLE_PROCESSES,
					['row_processed' => $row_current],
					['ID' => 1, 'process_name' => 'short_import']
				);
                $row_current++;
			}

			// изменяет статус шортимпорта
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => $row_count > $row_current ? 'started' : 'imported',
                    'msg'       =>  $row_count > $row_current ? $this->messages['started'] : $this->messages['imported']
                ],
				['ID' => 1, 'process_name' => 'short_import']
			);
            $process_status = $row_count > $row_current ? 'started' : 'imported';
		}

		if ($process_status == 'imported') {
			if ( ! wc_update_product_lookup_tables_is_running() ) {
				wc_update_product_lookup_tables();
			}
			// изменяет статус шортимпорта
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => 'finished',
                    'msg'       => $this->messages['finished']
                ],
				['ID' => 1, 'process_name' => 'short_import']
			);
		}

		return $record;
	}

    private function get_product_id_by_sku( $sku ) {
        global $wpdb;

        $id = $wpdb->get_var( "SELECT post_id
            FROM $wpdb->postmeta AS postmeta
            WHERE  postmeta.meta_key = '_sku'
            AND postmeta.meta_value = '$sku'
            LIMIT 1"
        );

        return (int) $id;
    }
}
