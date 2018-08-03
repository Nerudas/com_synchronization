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

class SynchronizationModelRegions extends AdminModel
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
		$pk    = 'regions';
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
		$form = $this->loadForm('com_synchronization.regions', 'regions',
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
		$data = $app->getUserState('com_synchronization.regions.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.regions', $data);

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
	 * Method to parse the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  mixed boolean  True on success.
	 *
	 * @since   1.0.1
	 */

	public function parse($data)
	{
		$select = ($data['total']) ? 'COUNT(*)' : '*';
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__regions'))
			->order('parent ASC')
			->order('ordering ASC');

		if ($data['total'])
		{
			$db->setQuery($query);
			$items = $db->loadObjectList();

			$query = $db->getQuery(true)
				->delete('#__location_regions')
				->where('id > 0');
			$db->setQuery($query)->execute();

			foreach ($items as $item)
			{
				$insert            = new stdClass();
				$insert->id        = $item->id;
				$insert->name      = 'pre';
				$insert->parent_id = ($item->parent == 0) ? -1 : (int) $item->parent;

				$db->insertObject('#__location_regions', $insert);
			}


			$count = count($items);

			return $count;
		}


		$limit = $data['limit'];
		$db->setQuery($query, $data['offset'], $limit);
		$oldItems = $db->loadObjectList();


		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_location/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_location/tables');

		$imageFolderHelper = new imageFolderHelper('images/location/regions');
		$regionModel       = BaseDatabaseModel::getInstance('Region', 'LocationModel');

		foreach ($oldItems as $oldItem)
		{
			$data                 = array();
			$data['name']         = $oldItem->name;
			$data['id']           = $oldItem->id;
			$data['abbreviation'] = '';
			$data['alias']        = '';
			$data['parent_id']    = ($oldItem->parent == 0) ? -1 : (int) $oldItem->parent;
			$data['default']      = ($oldItem->id == 100) ? 1 : 0;
			$data['show_all']     = ($oldItem->id == 100 || $oldItem->id == 110) ? 1 : 0;

			$data['state']  = $oldItem->published;
			$data['access'] = 1;

			$data['latitude']  = $oldItem->latitude;
			$data['longitude'] = $oldItem->longitude;
			$data['zoom']      = $oldItem->zoom;

			$imagefolder = $imageFolderHelper->getItemImageFolder($oldItem->id);
			if (JFile::exists(JPATH_ROOT . '/images/old_regions/' . $oldItem->id . '.png'))
			{
				$data['icon'] = $imagefolder . '/icon.png';
				JFile::move(JPATH_ROOT . '/images/old_regions/' . $oldItem->id . '.png', JPATH_ROOT . '/' . $data['icon']);
			}

			$regionModel->save($data);
		}

		$count = count($oldItems);

		if ($count < $limit)
		{
			$regionModel->rebuild();
		}

		return $count;
	}
}
