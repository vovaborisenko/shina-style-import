(function( $ ) {
	$( document ).ready(function () {
		wp.heartbeat.interval( 15 );
	})

	$(document).on('heartbeat-tick.shina_import', function (event, data) {
		// Проверяем, пришли ли данные от нашего плагина/темы.
		if (!data['shina-import']) {
			return;
		}

		var shina_import = data['shina-import'];

		$('#import_process_data').text( shina_import.percent);
		$('#import_progress_bar, #import_progress_bar_title').attr('style', shina_import.progress_bar_style);
		$('#import_process').attr('style', shina_import.progress_style);

		$('#import_message').html(shina_import.message);
	});

})( jQuery );
