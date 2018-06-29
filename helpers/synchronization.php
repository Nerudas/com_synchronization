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

use Joomla\CMS\Helper\CMSHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;


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

		JHtmlSidebar::addEntry(JText::_('COM_SYNCHRONIZATION_HOME'),
			'index.php?option=com_synchronization&view=home',
			$vName == 'home');

		JHtmlSidebar::addEntry(JText::_('COM_SYNCHRONIZATION_PLATON'),
			'index.php?option=com_synchronization&view=platon',
			$vName == 'platon');

		JHtmlSidebar::addEntry(JText::_('COM_SYNCHRONIZATION_CONFIG'),
			'index.php?option=com_config&view=component&component=com_synchronization&return=' . $return,
			$vName == 'config');
	}

	/**
	 * Get Remote database
	 *
	 * @return  mixed bool Jdatabase
	 *
	 * @since   1.0.0
	 */
	static function getRemoteDB()
	{
		$config = ComponentHelper::getParams('com_synchronization');
		$object = $config->get('remotedb', array());
		$params = ArrayHelper::fromObject($object, false);

		// Prepare options
		$options = array();
		foreach ($params as $key => $param)
		{
			if (empty($param))
			{
				return false;
			}
			$options[$key] = $param;
		}

		// Get Database
		$db = JDatabaseDriver::getInstance($options);
		try
		{
			$db->getVersion();
		}
		catch (Exception $e)
		{
			return false;
		}

		return $db;
	}

}