<?php
class Shina_Import_Cron_Feed {
	private string $file_name;
	private string $dir;
    private array $messages = [
        'new_file' => 'Файл обновлен, обновление feed\'ов начнется в течение минуты.',
        'started'  => 'Обновление feed\'ов выполняется.',
        'importing'=> 'Обновление feed\'ов выполняется.',
        'imported' => 'Обновление feed\'ов выполнено.',
        'finished' => 'Обновление feed\'ов успешно выполнено. Если недавно был изменен файл ' . SHINA_IMPORT_FEED_FILE_NAME . ', процесс начнется автоматически в течение минуты.'
    ];

	private string $table_name = 'sh_feed';
	private string $process_name = 'short_feed';
    private int $ID = 2;

	public function __construct() {
		$dir = wp_upload_dir()['basedir'] . '/' . SHINA_IMPORT_PLUGIN_NAME;

		$this->dir = $dir;
		$this->file_name = $dir . '/' . SHINA_IMPORT_FEED_FILE_NAME;
	}

	public function check_file () {
		global $wpdb;

		$record = $this->get_table_record($this->ID);

		$file_mod_time = $record['file_mod_time'];

		if ( ! file_exists($this->file_name) ) {
			// изменяет инфо о шортfeed
			$wpdb->replace( SHINA_IMPORT_TABLE_PROCESSES,
				[
					'ID' => $this->ID,
					'status' => 'error',
					'process_name' => $this->process_name,
					'msg' => 'Файл ' . SHINA_IMPORT_FEED_FILE_NAME . ' отсутствует в папке ' . $this->dir,
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
					'ID'            => $this->ID,
					'file_mod_time' => $new_file_mod_time,
					'status' 		=> 'new_file',
					'row_processed' => 1,
					'row_count' 	=> $row_count,
					'process_name'  => $this->process_name,
                    'msg'           => $this->messages['new_file']
				]
			);
		}

		return $record;
	}

	public function import() {
		global $wpdb;

        $dependence_record = $this->get_table_record(1);

        if ($dependence_record['status'] !== 'finished') {
            $wpdb->update(
                SHINA_IMPORT_TABLE_PROCESSES,
                [
                    'status'        => 'new_file',
                    'row_processed' => 1,
                    'msg'           => 'Обработка файла ' . SHINA_IMPORT_FEED_FILE_NAME . ' не начнется пока не закончен процесс обновления товаров. После обновления товаров будет выполнено обновление feed\'ов.',
                ],
                ['ID' => $this->ID, 'process_name' => $this->process_name]
            );
            return $dependence_record;
        }

		$record = $this->get_table_record($this->ID);
		$process_status = $record['status'];

		if ($process_status == 'new_file') {
			$wpdb->query("DROP TABLE $this->table_name");

			$wpdb->query(
				"CREATE TABLE $this->table_name (
					`id` INT(11) NOT NULL AUTO_INCREMENT,
					`product_id` BIGINT(20) NOT NULL,
					`product_sku` VARCHAR(50) NOT NULL,
					`product_price` DECIMAL(10,2) NOT NULL,
					`product_link` VARCHAR(150) NOT NULL,
					`product_img` VARCHAR(150) NOT NULL,
					`product_title` VARCHAR(150) NOT NULL,
					`product_category` INT(10) NOT NULL,
					PRIMARY KEY (`id`)
				)
				COLLATE 'utf8_general_ci' ENGINE=MyISAM ROW_FORMAT=Dynamic AUTO_INCREMENT=1;"
			);

			// изменяет статус шортимпорта, создает пути к логам
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'        => 'started',
                    'msg'           => $this->messages['started']
                ],
				['ID' => $this->ID, 'process_name' => $this->process_name]
			);
		}

		if ($process_status == 'started') {
			// изменяет статус шортимпорта
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => 'importing',
                    'msg'       => $this->messages['importing']
                ],
                ['ID' => $this->ID, 'process_name' => $this->process_name]
			);

			$row_start = $record['row_processed'];
			$row_count = $record['row_count'];
			$row_finish = $row_start + 4000 > $row_count ? $row_count : $row_start + 4000;

			$cont = trim(file_get_contents($this->file_name));

			// определим разделитель
			$row_delimiter = !str_contains($cont, "\r\n") ? "\n" : "\r\n";

			$lines = explode($row_delimiter, trim($cont));
			$lines = array_filter($lines);
			$lines = array_map('trim', $lines);

			for ($row_current = $row_start; $row_current <= $row_finish; $row_current++) {
				$linedata = str_getcsv($lines[$row_current], ','); // linedata
				$product_sku = $linedata[4];
				$product_id = $this->get_product_id_by_sku($product_sku);
                $product_link = get_permalink($product_id);
				$product_price = get_post_meta($product_id, '_price', true);
				$product_img = get_the_post_thumbnail_url($product_id, 'full');
				$product_title = get_the_title($product_id);
				$product_category = $linedata[3] === 'Автомобильные шины' ? 1 : 2206;

				$wpdb->insert(
					$this->table_name,
					[
						'product_id' => $product_id,
						'product_sku' => $product_sku,
						'product_price' => $product_price,
						'product_link' => $product_link,
						'product_img' => $product_img,
						'product_title' => $product_title,
                        'product_category' => $product_category
					],
					['%d', '%s', '%f', '%s', '%s', '%s', '%d']
				);

				$wpdb->update(
					SHINA_IMPORT_TABLE_PROCESSES,
					['row_processed' => $row_current],
                    ['ID' => $this->ID, 'process_name' => $this->process_name]
				);
			}

			// изменяет статус шортимпорта
			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => $row_count > $row_current ? 'started' : 'imported',
                    'msg'       =>  $row_count > $row_current ? $this->messages['started'] : $this->messages['imported']
                ],
                ['ID' => $this->ID, 'process_name' => $this->process_name]
			);
		}

		if ($process_status == 'imported') {
			// изменяет статус шортимпорта
            $query = "SELECT *
                FROM $this->table_name
                WHERE product_id != 0";

            $result = $wpdb->get_results( $query, ARRAY_A );
            $result_feeds = false;

            if ( !empty( $result ) ) {
                try {
                    $result_feeds = $this->write_feeds( $result );
                } catch (Exception) {
                    $wpdb->update(
                        SHINA_IMPORT_TABLE_PROCESSES,
                        [
                            'status' => 'error',
                            'msg' => 'Файлы feed не обновлены, ошибка формирования xml данных',
                        ],
                        ['ID' => $this->ID, 'process_name' => $this->process_name]
                    );
                }
            }

			$wpdb->update(
				SHINA_IMPORT_TABLE_PROCESSES,
				[
                    'status'    => $result_feeds ? 'finished' : 'error',
                    'msg'       => $result_feeds ? $this->messages['finished'] : 'Файлы feed не обновлены, ошибка создания xml файлов',
                ],
                ['ID' => $this->ID, 'process_name' => $this->process_name]
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

    private function get_table_record ($ID) {
        global $wpdb;

		return $wpdb->get_row( "SELECT * FROM " . SHINA_IMPORT_TABLE_PROCESSES . " WHERE ID = $ID", ARRAY_A );
    }

    /**
     * @throws Exception
     */
    private function write_feeds(array $items) {
        $date = date('Y-m-d\\TH:i:s', time() + 3 * 3600);
        $shop_categories = [
            1    => 'Автомобильные шины',
            2206 => 'Автомобильные диски'
        ];
        $sxe = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><yml_catalog xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" date="' . $date . '"></yml_catalog>');

        $shop = $sxe->addChild('shop');
        $shop->addChild('name', 'Шина Стайл - Интернет-магазин шин и дисков');
        $shop->addChild('company', 'Шина Стайл');
        $shop->addChild('url', 'https://shina-style.by');
        $shop->addChild('email', 'info@shina-style.by');

        $currencies = $shop->addChild('currencies');
        $currency = $currencies->addChild('currency');
        $currency->addAttribute('id', 'BYN');
        $currency->addAttribute('rate', '1.000000');

        $categories = $shop->addChild('categories');
        foreach ($shop_categories as $id => $shop_category) {
            $category = $categories->addChild('category', $shop_category);
            $category->addAttribute('id', $id);
        }

        $offers = $shop->addChild('offers');
        foreach ($items as $item) {
            $offer = $offers->addChild('offer');
            $offer->addAttribute('id', $item['product_sku']);
            $offer->addAttribute('available', 'true');

            $offer->addChild('name', $item['product_title']);
            $offer->addChild('url', $item['product_link']);
            $offer->addChild('price', $item['product_price']);
            $offer->addChild('currencyId', 'BYN');
            $offer->addChild('categoryId', $item['product_category']);
            $offer->addChild('store', 'false');
            $offer->addChild('pickup', 'true');
            $offer->addChild('delivery', 'false');
            $offer->addChild('min-quantity', 2);
            $offer->addChild('picture', $item['product_img']);
        }

        $dealby_written = $sxe->asXML($this->dir . '/feed-dealby.xml');
        $yandex_written = $sxe->asXML($this->dir . '/feed-yandexm.xml');

        return $dealby_written && $yandex_written;
    }
}
