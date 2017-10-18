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
			var results = $('#results'),
				list = results.find('.list'),
				error = results.find('.error'),
				progress = results.find('.progress'),
				progressbar = progress.find('.bar'),
				progresstext = progress.find('.text');


			// Preprare
			results.show();
			list.html('');
			error.hide();
			error.html('');
			progress.show();
			progressbar.width('0%');
			progresstext.text('0/0');

			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'index.php?option=com_synchronization&task=users.synchronizeProfiles',
				global: false,
				async: false,
				data: {},
				success: function (response) {
					if (response.success) {
						var data = response.data;
						$(data.html).appendTo(list);
						progressbar.width('0%');
						progresstext.text('0/' + data.count);
					}
					else {
						error.html(response.message);
						error.show();
					}

				}
			});
		});

	});
})(jQuery);
