<?php
/**
 * @package    Synchronization Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$uri    = (string) JUri::getInstance();
$return = urlencode(base64_encode($uri));

HTMLHelper::_('stylesheet', 'media/com_synchronization/css/home.min.css', array('version' => 'auto'));
?>


<div class="row-fluid">
	<div class="span9">
		<div class="row-fluid icons-block">
			<div class="span2">
				<a href="/administrator/index.php?option=com_synchronization&view=board">
					<div class="img">
						<span class="icon-archive large-icon"></span>
					</div>
					<div class="title">
						<?php echo Text::_('COM_SYNCHRONIZATION_BOARD'); ?>
					</div>
				</a>
			</div>
			<div class="span2">
				<a href="/administrator/index.php?option=com_config&view=component&component=com_synchronization&return=<?php echo $return; ?>">
					<div class="img">
						<span class="icon-options large-icon"></span>
					</div>
					<div class="title">
						<?php echo Text::_('COM_SYNCHRONIZATION_CONFIG'); ?>
					</div>
				</a>
			</div>
		</div>
		<div class="row-fluid icons-block">

		</div>
	</div>
	<div class="span3">
		<div class="well description-block clearfix">
			<?php echo HTMLHelper::_('image', 'com_synchronization/logo.png',
				Text::_('COM_SYNCHRONIZATION'), array('title' => Text::_('COM_SYNCHRONIZATION')), true); ?>

			<div class="text-center lead">
				<?php echo Text::_('COM_SYNCHRONIZATION_DESCRIPTION'); ?>
			</div>
		</div>
	</div>
</div>