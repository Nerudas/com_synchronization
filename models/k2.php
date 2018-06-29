<?php
/**
 * @package    Synchronization Component
 * @version    1.0.4
 * @author     Nerudas  - nerudas.ru
 * @copyright  Copyright (c) 2013 - 2018 Nerudas. All rights reserved.
 * @license    GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link       https://nerudas.ru
 */

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class SynchronizationModelK2 extends AdminModel
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
		$pk    = 'k2';
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
		$form = $this->loadForm('com_synchronization.k2', 'k2',
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
		$data = $app->getUserState('com_synchronization.k2.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.k2', $data);

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
		$data['type']     = 'companies';
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
	 * Method to create redirects
	 *
	 * @return bool;
	 */
	public function createRedirects()
	{

		$error = false;

		$site       = JApplication::getInstance('site');
		$siteRouter = $site->getRouter();

		JLoader::register('PrototypeHelperRoute', JPATH_SITE . '/components/com_prototype/helpers/route.php');

		$redirects = array();


		// vesi
		$redirects[$siteRouter->build('index.php?Itemid=' . 2045)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(8))->toString();

		$redirects[$siteRouter->build('index.php?Itemid=' . 2044)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(8))->toString();

		// Platon
		$redirects[$siteRouter->build('index.php?Itemid=' . 2048)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(9))->toString();

		$redirects[$siteRouter->build('index.php?Itemid=' . 2047)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(9))->toString();

		// Doska
		$redirects['/map/doska.html'] = '/karta/doska.html';

		// Map
		$redirects[$siteRouter->build('index.php?Itemid=' . 637)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(2))->toString();

		$redirects[$siteRouter->build('index.php?Itemid=' . 637)->toString() . '/*'] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(2))->toString();

		// Remzona
		$redirects[$siteRouter->build('index.php?Itemid=' . 1628)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(2))->toString();


		// Pesok
		$redirects[$siteRouter->build('index.php?Itemid=' . 1075)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(6))->toString();

		$redirects[$siteRouter->build('index.php?Itemid=' . 1075)->toString() . '/*'] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(6))->toString();

		// Sheven
		$redirects[$siteRouter->build('index.php?Itemid=' . 1073)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(7))->toString();

		$redirects[$siteRouter->build('index.php?Itemid=' . 1073)->toString() . '/*'] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(7))->toString();

		// Nerudka
		$redirects[$siteRouter->build('index.php?Itemid=' . 1622)->toString()] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(3))->toString();

		$redirects[$siteRouter->build('index.php?Itemid=' . 1622)->toString() . '/*'] =
			$siteRouter->build(PrototypeHelperRoute::getMapRoute(3))->toString();

		foreach ($redirects as $old => $new)
		{
			$old    = str_replace('/administrator', '', $old);
			$new    = str_replace('/administrator', '', $new);
			$regexp = (preg_match('/\*/', $old)) ? 1 : 0;

			if ($regexp)
			{
				$old = str_replace('.html', '', $old);
			}

			if (!$this->addRedirect($old, $new, $regexp))
			{
				$error = true;
				$this->setError('Redirect false: ' . $old);
			}
		}

		return !$error;
	}

	/**
	 * Method to create redirect
	 *
	 * @param string $old   old url
	 * @param string $new   new url
	 *
	 * @param int    $regex regexp;
	 *
	 * @return  bool
	 *
	 * @since 1.0.1
	 */
	protected function addRedirect($old, $new, $regex = 0)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__sefwizard_redirects')
			->where($db->quoteName('source') . ' = ' . $db->quote($old));
		$db->setQuery($query);
		$id = $db->loadResult();

		$redirect = new stdClass();
		if (!empty($id))
		{
			$redirect->id = $id;
		}
		$redirect->source      = $old;
		$redirect->destination = $new;
		$redirect->code        = 301;
		$redirect->regex       = $regex;

		return (!empty($id)) ? $db->updateObject('#__sefwizard_redirects', $redirect, 'id') :
			$db->insertObject('#__sefwizard_redirects', $redirect);
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

		foreach (array('#__k2_log', '#__k2_related_items', '#__maps') as $table)
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

	/**
	 * Method to delete modules
	 *
	 * @param array $pks
	 *
	 * @return bool;
	 */
	public function deleteModules($pks = array())
	{
		$error = false;

		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('id')
			->from('#__modules')
			->where('id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		$pks = $db->loadColumn();
		if (!empty($pks))
		{
			$query = $db->getQuery(true)
				->update($db->quoteName('#__modules'))
				->set($db->quoteName('published') . ' = -2')
				->where('id IN (' . implode(',', $pks) . ')');
			$db->setQuery($query)
				->execute();

			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_modules/models');
			$model = BaseDatabaseModel::getInstance('Module', 'ModulesModel', array('ignore_request' => true));

			$model->delete($pks);

			if (!empty($model->getErrors()))
			{
				$error = true;
				foreach ($model->getErrors() as $error)
				{
					$this->setError($error);
				}
			}
		}

		return !$error;
	}

	/**
	 * Method to delete modules
	 *
	 * @param array $pks
	 *
	 * @return bool;
	 */
	public function deleteMenus($pks = array())
	{
		$error = false;

		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('id')
			->from('#__menu_types')
			->where('id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		$pks = $db->loadColumn();
		if (!empty($pks))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_menus/models');
			$model = BaseDatabaseModel::getInstance('Menu', 'MenusModel', array('ignore_request' => true));

			$model->delete($pks);
			if (!empty($model->getErrors()))
			{
				$error = true;
				foreach ($model->getErrors() as $error)
				{
					$this->setError($error);
				}
			}
		}

		return !$error;
	}

	/**
	 * Method to delete modules
	 *
	 * @param array $pks
	 *
	 * @return bool;
	 */
	public function deleteMenuItems($pks = array())
	{
		$error = false;

		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select('id')
			->from('#__menu')
			->where('id IN (' . implode(',', $pks) . ')');
		$db->setQuery($query);

		$pks = $db->loadColumn();
		if (!empty($pks))
		{

			$query = $db->getQuery(true)
				->update($db->quoteName('#__menu'))
				->set($db->quoteName('published') . ' = -2')
				->where('id IN (' . implode(',', $pks) . ')');
			$db->setQuery($query)
				->execute();

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_menus/tables');

			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/administrator/components/com_menus/models');
			$model = BaseDatabaseModel::getInstance('Item', 'MenusModel', array('ignore_request' => true));

			if (!empty($model->getErrors()))
			{
				$error = true;
				foreach ($model->getErrors() as $error)
				{
					$this->setError($error);
				}
			}
		}

		return !$error;
	}
}