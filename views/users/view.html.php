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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;


class SynchronizationViewUsers extends HtmlView
{
	/**
	 * The JForm object
	 *
	 * @var  JForm
	 *
	 * @since   1.0.0
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 *
	 * @since   1.0.0
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 *
	 * @since   1.0.0
	 */
	protected $state;

	/**
	 * The sidebar html
	 *
	 * @var  string
	 *
	 * @since   1.0.0
	 */
	protected $sidebar;


	/**
	 * Execute and display a template script.
	 *
	 * @param   string $tpl The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return mixed A string if successful, otherwise an Error object.
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public function display($tpl = null)
	{
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');

		// Title
		JToolBarHelper::title(
			Text::_('COM_SYNCHRONIZATION') . ' / ' . JText::_('COM_SYNCHRONIZATION_USERS'),
			'users'
		);

		JToolbarHelper::custom('users.apply', 'cog', '',
			'COM_SYNCHRONIZATION_ACTIONS_SAVE',false);
		JToolbarHelper::custom('users.synchronize', 'loop', '',
			'COM_SYNCHRONIZATION_USERS_SYNCHRONIZE' ,false);
		JToolbarHelper::custom('users.synchronizeProfiles', 'loop', '',
			'COM_SYNCHRONIZATION_USERS_SYNCHRONIZE_PROFILES',false);

		// Sidebar
		SynchronizationHelper::addSubmenu('users');
		$this->sidebar = JHtmlSidebar::render();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		return parent::display($tpl);
	}

}