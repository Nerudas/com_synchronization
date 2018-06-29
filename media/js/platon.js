/*
 * @package    Synchronization Component
 * @version    1.0.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[onclick*="platon.parse"]').attr('id', 'platonParse').removeAttr('onclick');

		$('#platonParse').on('click', function () {
			parseProfilesItems();
		});
	});

	function parseProfilesItems() {
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
			var name = field.name;
			ajaxData[name] = field.value;
		});
		ajaxData['task'] = 'platon.parse';
		ajaxData['first'] = 'true';

		// Prepare
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