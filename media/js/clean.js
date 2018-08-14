/*
 * @package    Synchronization Component
 * @version    1.0.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

(function ($) {
	$(document).ready(function () {
		$('[onclick*="clean.clean"]').attr('id', 'cleanRun').removeAttr('onclick');

		var items = $('[data-clean-tasks]').find('[data-task]'),
			tasks = [],
			key = 0;

		$(items).each(function () {
			tasks.push($(this).data('task'));
		});

		var count = tasks.length;

		function runTask() {
			if (count > 0 && key < count) {
				var task = tasks[key],
					elem = $('[data-task="' + task + '"]'),
					loading = $(elem).find('.loading'),
					error = $(elem).find('.error'),
					success = $(elem).find('.success');

				// Run ajax
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: 'index.php?option=com_synchronization&task=clean.' + task,
					cache: false,
					data: {},
					beforeSend: function () {
						error.hide();
						success.hide();
						loading.show();
					},
					complete: function () {
						loading.hide();
						// Recursive
						key++;
						runTask();
					},
					success: function (response) {
						if (response.success) {
							success.show();
						}
						else {
							error.show();
							console.error(task + '\n' + response.message);
						}
					},
					error: function (response) {
						error.show();
						console.error(task + '\n' + response.status + ' ' + response.statusText);
					}
				});

			}
		}

		$('#cleanRun').on('click', function () {
			key = 0;
			runTask();
		});
	});
})(jQuery);