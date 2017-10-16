<?php
/**
 * @package    Synchronization Component
 * @version    1.0.0
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2017 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Helper\CMSHelper;

class SynchronizationHelper extends CMSHelper
{
	public static $extension = 'com_synchronization';


	/**
	 * Configure the Linkbar.
	 *
	 * @param   string $vName The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	static function addSubmenu($vName)
	{
		$uri    = (string) JUri::getInstance();
		$return = urlencode(base64_encode($uri));

		JHtmlSidebar::addEntry(JText::_('COM_SYNCHRONIZATION_USERS'),
			'index.php?option=com_synchronization&view=users',
			$vName == 'users');

		JHtmlSidebar::addEntry(JText::_('COM_SYNCHRONIZATION_CONFIG'),
			'index.php?option=com_config&view=component&component=com_synchronization&return=' . $return,
			$vName == 'config');
	}
}