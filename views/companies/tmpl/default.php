<?php
/**
 * @package    Synchronization Component
 * @version    1.0.2
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$doc = Factory::getDocument();

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('stylesheet', 'media/com_synchronization/css/board.min.css', array('version' => 'auto'));
HTMLHelper::_('stylesheet', 'media/com_synchronization/css/default.min.css', array('version' => 'auto'));
HTMLHelper::_('script', 'media/com_synchronization/js/companies.min.js', array('version' => 'auto'));

$doc->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (document.formvalidator.isValid(document.getElementById("item-form")))
		{
			if (task == "users.synchronize") {
				jQuery("#syncloader").addClass("active");
			}
			if (task == "users.synchronizeProfiles") {
				return false;
			}
			Joomla.submitform(task, document.getElementById("item-form"));
		}
	};
');

?>

<form action="<?php echo Route::_('index.php?option=com_synchronization&view=companies'); ?>" method="post"
	  name="adminForm" id="item-form" class="form-validate" enctype="multipart/form-data">
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>


	<div id="j-main-container" class="span10">
		<div id="results">
			<h2><?php echo Text::_('COM_SYNCHRONIZATION_ACTIONS_SYNCHRONIZATION'); ?></h2>
			<div class="error alert alert-error">
			</div>
			<div class="progress progress-success active">
				<div class="text"></div>
				<div class="bar"></div>
			</div>
		</div>
		<h2><?php echo Text::_('COM_SYNCHRONIZATION_PARAMS'); ?></h2>
		<div class="form-horizontal">
			<?php echo $this->form->renderFieldset('params'); ?>
		</div>
	</div>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="return" value="<?php echo $app->input->getCmd('return'); ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<div id="syncloader">
	<div class="inner">
		<div class="loader"></div>
		<div class="text"><?php echo Text::_('COM_SYNCHRONIZATION_ACTIONS_SYNCHRONIZATION') . '...'; ?></div>
	</div>
</div>
