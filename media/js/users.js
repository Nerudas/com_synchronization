/*
 * @package    Synchronization Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2017 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[onclick*="users.synchronizeProfiles"]').attr('id', 'synchronizeProfiles').removeAttr('onclick');

		$('#synchronizeProfiles').on('click', function () {
			synchronizeProfiles();
		});
	});

	function synchronizeProfiles() {
		var results = $('#results'),
			list = results.find('.list'),
			error = results.find('.error'),
			progress = results.find('.progress'),
			progressbar = progress.find('.bar'),
			progresstext = progress.find('.text');

		// Prepare ajax data
		var ajaxData = {},
			form = $('form').serializeArray();
		$(form).each(function (i, field) {
			var name = field.name.replace('jform[params][', '').replace(']', '');
			ajaxData[name] = field.value;
		});
		ajaxData['task'] = 'users.synchronizeProfiles';

		// Preprare
		results.show();
		list.html('');
		error.hide();
		error.html('');
		progressbar.width('0%');
		progresstext.text('0/0');
		progress.show();

		// Start Ajax
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: 'index.php?option=com_synchronization',
			global: false,
			async: false,
			data: ajaxData,
			success: function (response) {
				if (response.success) {
					var data = response.data;
					$(data.html).appendTo(list);

					// Star Profile sync
					progressbar.width('0%');
					progresstext.text('0/' + data.count);

					var items = $(list).find('li'),
						total = items.length;
					$(items).each(function (i, li) {
						var item = $(li),
							current = i + 1,
							loading = current / total * 100;

						// Add Ajax data
						ajaxData['id'] = item.data('id');
						ajaxData['task'] = 'users.synchronizeProfile';

						// Stat ajax
						$.ajax({
							type: 'POST',
							dataType: 'json',
							async: false,
							url: 'index.php?option=com_synchronization',
							data: ajaxData,
							success: function (response) {
								$(item).html(response.data);
							},
							error: function (response) {
								$(item).html(response.responseText.data);
								$(item).html(response.responseText.message);
							}
						});
						progresstext.text(current + '/' + total);
						progressbar.width(loading + '%');
					});
				}
				else {
					error.html(response.message);
					error.show();
				}
			},
			error: function (response) {
				error.html(response.responseText.message);
				error.show();
			}
		});
		progress.hide();
		progressbar.width('0%');
		progresstext.text('0/0');

	}
})(jQuery);
