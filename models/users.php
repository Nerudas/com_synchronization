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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class SynchronizationModelUsers extends AdminModel
{

	/**
	 * Method to get a single record.
	 *
	 * @param   string $pk The type of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since 1.0.0
	 */
	public function getItem($pk = null)
	{
		$pk    = 'users';
		$table = $this->getTable();

		if (!empty($pk))
		{
			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if ($return === false && $table->getError())
			{
				$this->setError($table->getError());

				return false;
			}
		}

		// Convert to the \JObject before adding other data.
		$properties = $table->getProperties(1);
		$item       = ArrayHelper::toObject($properties, '\JObject');

		if (property_exists($item, 'params'))
		{
			$registry     = new Registry($item->params);
			$item->params = $registry->toArray();
		}

		return $item;
	}

	/**
	 * Abstract method for getting the form from the model.
	 *
	 * @param   array   $data     Data for the form.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_synchronization.users', 'users',
			array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 *
	 * @return mixed The data for the form.
	 *
	 * @since   1.0.0
	 */
	protected function loadFormData()
	{
		$app  = JFactory::getApplication();
		$data = $app->getUserState('com_synchronization.users.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.users', $data);

		return $data;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string $extension The table extension to instantiate
	 * @param   string $prefix    A prefix for the table class name. Optional.
	 * @param   array  $config    Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 * @since   1.0.0
	 */
	public function getTable($extension = 'Synchronization', $prefix = 'SynchronizationTable', $config = array())
	{
		return Table::getInstance($extension, $prefix, $config);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{

		$data['type'] = 'users';

		// Prepare attribs json
		if (isset($data['params']) && is_array($data['params']))
		{
			$registry       = new Registry($data['params']);
			$data['params'] = (string) $registry;
		}

		return parent::save($data);
	}

	/**
	 * Method to synchronize users
	 *
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function synchronize()
	{
		$db       = Factory::getDbo();
		$remoteDB = SynchronizationHelper::getRemoteDB();

		if (!$remoteDB)
		{
			$this->setError(Text::_('COM_SYNCHRONIZATION_ERROR_REMOTEDB'));

			return false;
		}

		$tables = array('users', 'user_usergroup_map', 'usergroups', 'viewlevels', 'slogin_users');

		$remoteData = array();
		// Get Data
		foreach ($tables as $table)
		{
			// Get slogin_users
			$query = $remoteDB->getQuery(true);
			$query->select('*')
				->from('#__' . $table);
			$remoteDB->setQuery($query);
			$data = $remoteDB->loadObjectList();
			if (count($data) == 0)
			{
				$this->setError(Text::sprintf('COM_SYNCHRONIZATION_USERS_ERORR_NODATA', $table));

				return false;
			}
			$remoteData[$table] = $data;
		}

		// Inset Data
		foreach ($tables as $table)
		{
			$name = '#__' . $table;
			$db->truncateTable($name);
			foreach ($remoteData[$table] as $datum)
			{
				$db->insertObject($name, $datum);
			}
		}

		return true;
	}

	/**
	 * Method to synchronize users
	 *
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function synchronizeProfiles()
	{

		$db       = Factory::getDbo();
		$remoteDB = SynchronizationHelper::getRemoteDB();

		if (!$remoteDB)
		{
			$this->setError(Text::_('COM_SYNCHRONIZATION_ERROR_REMOTEDB'));

			return false;
		}

		// Clear extra fields
		$query = $db->getQuery(true)
			->select('field_id')
			->from($db->quoteName('#__fields_values', 'v'))
			->join('LEFT', $db->quoteName('#__fields', 'f') . ' ON f.id = v.field_id')
			->where($db->quoteName('f.context') . ' = ' . $db->quote('com_users.user'));
		$db->setQuery($query);
		$ids   = $db->loadColumn();
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__fields_values'))
			->where($db->quoteName('field_id') . ' IN (' . implode(',', $ids) . ')');
		$db->setQuery($query);
		$result = $db->execute();

		// Get users
		$query = $db->getQuery(true)
			->select(array('id', 'name'))
			->from('#__users');
		$db->setQuery($query);
		$users = $db->loadAssocList('id', 'name');

		// Recreate images folders
		$imagesPath = JPATH_ROOT . '/images/users';
		$folders    = JFolder::folders($imagesPath);
		foreach ($folders as $folder)
		{
			if ($folder !== '0')
			{
				JFolder::delete($imagesPath . '/' . $folder);
			}
		}
		foreach (array_keys($users) as $id)
		{
			JFile::write($imagesPath . '/' . $id . '/index.html', '<!DOCTYPE html><title></title>');
		}

		return $users;
	}


}

