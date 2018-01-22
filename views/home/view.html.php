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

use Joomla\CMS\MVC\View\HtmlView;

class SynchronizationViewHome extends HtmlView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		JToolBarHelper::title(JText::_('COM_SYNCHRONIZATION'), 'loop');
		JToolBarHelper::help($ref = '', $com = false,
			$override = 'https://github.com/Nerudas/com_synchronization/blob/master/README.md');

		return parent::display($tpl);
	}
}