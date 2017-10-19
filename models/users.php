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
			$query = $remoteDB->getQuery(true)
				->select('*')
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
	 * Method to synchronize profiles
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

		$ids = $db->loadColumn();
		if (count($ids) > 0)
		{
			$query = $db->getQuery(true)
				->delete($db->quoteName('#__fields_values'))
				->where($db->quoteName('field_id') . ' IN (' . implode(',', $ids) . ')');
			$db->setQuery($query);
			$result = $db->execute();
		}

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

	/**
	 * Method to synchronize profile
	 *
	 *
	 * @param $pk User Id;
	 *
	 * @return bool True on success.
	 *
	 * @since   1.0.0
	 */
	public function synchronizeProfile($pk = null)
	{

		$app  = Factory::getApplication();
		$pk   = (empty($pk)) ? $app->input->get('id', 0, 'int') : $pk;
		$user = Factory::getUser($pk);
		$name = $user->name;
		if (empty($pk))
		{
			$this->setError(Text::_('COM_SYNCHRONIZATION_USERS_SYNCHRONIZE_PROFILE_ERROR_NOUSER'));

			return false;
		}

		// Get DBs
		$db       = Factory::getDbo();
		$remoteDB = SynchronizationHelper::getRemoteDB();
		if (!$remoteDB)
		{
			$this->setError(Text::_('COM_SYNCHRONIZATION_ERROR_REMOTEDB'));

			return false;
		}

		// Get k2 profile
		$query = $remoteDB->getQuery(true)
			->select(array('k.*', 's.text as status'))
			->from($db->quoteName('#__k2_items', 'k'))
			->where($db->quoteName('catid') . ' = ' . 10)
			->where($db->quoteName('created_by') . ' = ' . (int) $pk)
			->join('LEFT', $db->quoteName('#__profiles_status', 's') . ' ON k.id = s.id');
		$remoteDB->setQuery($query);
		$k2Profile = $remoteDB->loadObject();
		if (empty($k2Profile))
		{
			$this->setError(Text::_('COM_SYNCHRONIZATION_USERS_SYNCHRONIZE_PROFILE_ERROR_NOK2PROFILE'));

			return false;
		}
		$k2Profile->extra = array();
		if (!empty($k2Profile->extra_fields))
		{
			foreach (json_decode($k2Profile->extra_fields) as $field)
			{
				if (!empty($field->value))
				{
					$k2Profile->extra[$field->id] = $field->value;
				}
			}
		}


		// Fields Model
		JLoader::import('joomla.application.component.model');
		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/models', 'FieldsModel');
		$fieldsModel = JModelLegacy::getInstance('Field', 'FieldsModel', array('ignore_request' => true));

		// Check name
		if ($name !== $k2Profile->title)
		{
			$newName       = new stdClass();
			$newName->id   = $pk;
			$newName->name = $k2Profile->title;
			$db->updateObject('#__users', $newName, 'id');
			$name = $k2Profile->title;
		}

		// Phones (5, 7, 9, 11, 13)
		if ($app->input->get('phones', 0, 'int'))
		{
			$phones     = array();
			$k2Phones   = array(5, 7, 9, 11, 13);
			$k2Contacts = array(6, 8, 10, 12, 14);
			$i          = 1;
			foreach ($k2Phones as $key => $field)
			{
				if (!empty($k2Profile->extra[$field]))
				{
					$phone         = new stdClass();
					$phone->code   = '+7';
					$phone->number = $k2Profile->extra[$field];
					if (!empty($k2Profile->extra[$k2Contacts[$key]]))
					{
						$phone->text = $k2Profile->extra[$k2Contacts[$key]];
					}
					$phones['phone_' . $i] = $phone;
					$i++;
				}
			}
			if (count($phones))
			{
				$phones = new Registry($phones);
				$fieldsModel->setFieldValue($app->input->get('phones'), $pk, (string) $phones);
			}
		}

		// VK (40)
		if ($app->input->get('vk', 0, 'int') && !empty($k2Profile->extra[40])
			&& !empty($k2Profile->extra[40][1]))
		{
			$vk = str_replace('http://', '', $k2Profile->extra[40][1]);
			$vk = str_replace('https://', '', $vk);
			$vk = str_replace('www.', '', $vk);
			$vk = str_replace('vk.com', '', $vk);
			$vk = str_replace('/', '', $vk);
			$fieldsModel->setFieldValue($app->input->get('vk'), $pk, (string) $vk);
		}

		// Facebook (41)
		if ($app->input->get('facebook', 0, 'int') && !empty($k2Profile->extra[41])
			&& !empty($k2Profile->extra[41][1]))
		{
			$facebook = str_replace('http://', '', $k2Profile->extra[41][1]);
			$facebook = str_replace('https://', '', $facebook);
			$facebook = str_replace('www.', '', $facebook);
			$facebook = str_replace('facebook.com', '', $facebook);
			$facebook = str_replace('/', '', $facebook);
			$fieldsModel->setFieldValue($app->input->get('facebook'), $pk, (string) $facebook);
		}

		// Status
		if ($app->input->get('status', 0, 'int') && !empty($k2Profile->status))
		{
			$fieldsModel->setFieldValue($app->input->get('status'), $pk, (string) $k2Profile->status);
		}

		// About
		if ($app->input->get('about', 0, 'int') && !empty($k2Profile->introtext))
		{
			$fieldsModel->setFieldValue($app->input->get('about'), $pk, (string) $k2Profile->introtext);
		}

		// Avatar
		if ($app->input->get('avatar', 0, 'int'))
		{
			$avatar = 'https://nerudas.ru/media/k2/items/src/' . md5('Image' . $k2Profile->id) . '.jpg';
			if (!preg_match('/<html/', JFile::read($avatar)))
			{
				$newAvatar = 'images/users/' . $pk . '/' . 'avatar.' . JFile::getExt($avatar);
				if (Factory::getStream()->copy($avatar, JPATH_ROOT . '/' . $newAvatar, null, false))
				{
					$fieldsModel->setFieldValue($app->input->get('avatar'), $pk, $newAvatar);
				}
			}
		}

		return $name;
	}
}

