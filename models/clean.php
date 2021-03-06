<?php
/**
 * @package    Synchronization Component
 * @version    1.0.5
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class SynchronizationModelClean extends AdminModel
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
		$pk    = 'clean';
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
	 * @since   1.0.1
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_synchronization.clean', 'clean',
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
	 * @since   1.0.1
	 */
	protected function loadFormData()
	{
		$app  = JFactory::getApplication();
		$data = $app->getUserState('com_synchronization.clean.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.clean', $data);

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
	 * @since   1.0.1
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
	 * @since   1.0.1
	 */
	public function save($data)
	{
		$data['type']     = 'clean';
		$data['last_run'] = Factory::getDate()->toSql();

		// Prepare attribs json
		if (isset($data['params']) && is_array($data['params']))
		{
			$registry       = new Registry($data['params']);
			$data['params'] = (string) $registry;
		}

		return parent::save($data);
	}


	/**
	 * Method to delete extension
	 *
	 * @param array $pks
	 *
	 * @return bool;
	 */
	public function deleteExtensions($pks = array())
	{
		$error = false;

		BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_installer/models');
		$model = BaseDatabaseModel::getInstance('Manage', 'InstallerModel', array('ignore_request' => true));
		$model->remove($pks);

		if (!empty($model->getErrors()))
		{
			$error = true;
			foreach ($model->getErrors() as $error)
			{
				$this->setError($error);
			}
		}

		return !$error;
	}

	/**
	 * Method to delete extension
	 *
	 * @return bool;
	 */
	public function deleteDB()
	{
		$error = false;

		$db = Factory::getDbo();

		foreach (array('ipgeobase', '#__geolocations', '#__regions') as $table)
		{
			$db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($table));
			try
			{
				$db->execute();
			}
			catch (JDataBaseExceptionExecuting $e)
			{
				$error = true;
				$this->setError($e->getMessage());
			}
		}

		return !$error;
	}

}