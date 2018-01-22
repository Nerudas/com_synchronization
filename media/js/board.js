/*
 * @package    Synchronization Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[onclick*="board.parse"]').attr('id', 'boardParse').removeAttr('onclick');

		$('#boardParse').on('click', function () {
			parseBoardItems();
		});
	});

	function parseBoardItems() {
		var results = $('#results'),
			error = results.find('.error'),
			progress = results.find('.progress'),
			progressbar = progress.find('.bar'),
			progresstext = progress.find('.text'),
			total = 0,
			form = $('form'),
			step = form.find('input[name*="step"]').val(),
			offset = 0;

		// Prepare ajax data
		var ajaxData = {},
			formData = $(form).serializeArray();
		$(formData).each(function (i, field) {
			var name = field.name; //.replace('jform[params][', '').replace(']', '');
			ajaxData[name] = field.value;
		});
		ajaxData['task'] = 'board.parse';
		ajaxData['first'] = 'true';

		// Preprare
		//$('#boardParse').attr('disabled', 'true');
		results.hide();
		error.hide();
		error.html('');
		progressbar.width('0%');
		progresstext.text('0/0');

		// Start ajax
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'index.php?option=com_synchronization',
			data: ajaxData,
			success: function (response) {
				total = response.data * 1;
				if (total > 0) {
					ajaxData['first'] = 'false';
					ajaxData['limit'] = step;
					ajaxData['offset'] = offset;
					progresstext.text('0/' + total);
					results.show();
					progress.show();
					parse();
				}
			}
		});

		function parse() {
			if (offset !== total) {
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: 'index.php?option=com_synchronization',
					data: ajaxData,
					success: function (response) {
						var count = response.data;
						offset = offset + count;
						ajaxData['first'] = 'false';
						ajaxData['limit'] = step;
						ajaxData['offset'] = offset;
						progresstext.text(offset + '/' + total);
						progressbar.width((offset / total * 100) + '%');
						parse();
					}
				});
			}
		}
	}
})(jQuery);