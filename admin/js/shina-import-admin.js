(function( $ ) {
	$( document ).ready(function () {
		wp.heartbeat.interval( 15 );
	})

	$(document).on('heartbeat-tick.shina_import', function (event, data) {
		// Проверяем, пришли ли данные от нашего плагина/темы.
		if (!data['shina-import']) {
			return;
		}

		Object.entries(data['shina-import']).forEach(([id, value]) => {
			$(`#import_process_data_${id}`).text( value.percent);
			$(`#import_progress_bar_${id}, #import_progress_bar_title_${id}`).attr('style', value.progress_bar_style);
			$(`#import_process_${id}`).attr('style', value.progress_style);

			$(`#import_message_${id}`).html(value.message);
		})
	});

})( jQuery );
