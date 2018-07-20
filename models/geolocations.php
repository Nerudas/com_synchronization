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

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class SynchronizationModelGeolocations extends AdminModel
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
		$pk    = 'geolocations';
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
		$form = $this->loadForm('com_synchronization.geolocations', 'geolocations',
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
		$data = $app->getUserState('com_synchronization.geolocations.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.geolocations', $data);

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
			->select($select)
			->from($db->quoteName('ipgeobase'));

		if ($data['total'])
		{
			$db->setQuery($query);
			$count = $db->loadResult();

			return $count;
		}

		$db->setQuery($query, $data['offset'], $data['limit']);
		$ipgeobase = $db->loadObjectList();


		$db   = Factory::getDbo();
		$date = Factory::getDate()->toSql();

		foreach ($ipgeobase as $data)
		{
			$data  = ArrayHelper::fromObject($data);
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from('#__location_geolocations');

			foreach ($data as $col => $val)
			{
				$query->where($db->quoteName($col) . ' = ' . $db->quote($val));
			}

			$db->setQuery($query);
			$exist = !empty($db->loadResult());
			if (!$exist)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from('#__geolocations');

				foreach ($data as $col => $val)
				{
					$val = ($val == '-') ? '0' : $val;
					$query->where($db->quoteName($col) . ' = ' . $db->quote($val));
				}

				$state = 0;
				if (!empty($old))
				{
					$state = $old->published;
				}

				$region_id = -1;
				if (!empty($old))
				{
					$region_id = $old->region_id;
				}

				if ($data['country'] != 'RU')
				{
					$state     = 1;
					$region_id = 110;
				}


				$db->setQuery($query);
				$old               = $db->loadObject();
				$record            = (object) $data;
				$record->created   = $date;
				$record->state     = $state;
				$record->region_id = $region_id;

				$db->insertObject('#__location_geolocations', $record);
			}

		}

		$count = count($ipgeobase);

		return $count;
	}
}
