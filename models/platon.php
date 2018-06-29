<?php
/**
 * @package    Synchronization Component
 * @version    1.0.3
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

class SynchronizationModelPlaton extends AdminModel
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
		$pk    = 'companies';
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
		$form = $this->loadForm('com_synchronization.platon', 'platon',
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
		$data = $app->getUserState('com_synchronization.platon.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.platon', $data);

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
		$items = array();


		$select = ($data['total']) ? 'COUNT(*)' : '*';
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select($select)
			->from($db->quoteName('#__k2_items'))
			->where($db->quoteName('catid') . ' = 391')
			// ->where($db->quoteName('id') . ' IN (' . implode(',', $testIDS) . ')')
			->order('id ASC');
		if ($data['total'])
		{
			$db->setQuery($query);
			$count = $db->loadResult();

			return $count;
		}
		$db->setQuery($query, $data['offset'], $data['limit']);
		$k2Items = $db->loadObjectList('id');


		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_prototype/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_prototype/tables');

		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');

		$prototypeModel    = BaseDatabaseModel::getInstance('Item', 'PrototypeModel');
		$k2Model           = BaseDatabaseModel::getInstance('K2', 'NerudasModel');
		$imageFolderHelper = new imageFolderHelper('images/info');


		foreach ($k2Items as $k2Item)
		{
			if (!empty($k2Item->latitude) && !empty($k2Item->longitude) &&
				$k2Item->latitude !== '0.000000' && $k2Item->longitude !== '0.000000')
			{
				$text = preg_replace('/[\r\n]+/s', '\n',
					preg_replace('/[\r\n][ \t]+/s', '\n', strip_tags($k2Item->introtext)));

				$state = 0;
				if ($k2Item->published && !$k2Item->trash)
				{
					$state = 1;
				}
				elseif ($k2Item->trash)
				{
					$state = -2;
				}

				$data               = array();
				$data['html']       = '';
				$data['created_by'] = $k2Item->created_by;
				$data['title']      = $k2Item->title;
				$data['catid']      = 9;

				$data['map']                             = array();
				$data['map']['placemark']                = array();
				$data['map']['placemark']['coordinates'] = '["' . $k2Item->latitude . '", "' . $k2Item->longitude . '"]';
				$data['map']['placemark']['latitude']    = $k2Item->latitude;
				$data['map']['placemark']['longitude']   = $k2Item->longitude;
				$data['map']['params']                   = array();
				$data['map']['params']['center']         = '["' . $k2Item->latitude . '", "' . $k2Item->longitude . '"]';
				$data['map']['params']['latitude']       = $k2Item->latitude;
				$data['map']['params']['longitude']      = $k2Item->longitude;
				$data['map']['params']['zoom']           = 10;


				$data['placemark_id']   = '';
				$data['balloon_layout'] = '';
				$data['state']          = $state;
				$data['region']         = $k2Item->region;
				$data['access']         = $k2Item->access;
				$data['imagefolder']    = $imageFolderHelper->createTemporaryFolder();
				$data['extra']          = array('comment' => $text);
				$data['created']        = $k2Item->created;
				$data['publish_down']   = $k2Item->publish_down;
				$data['hits']           = $k2Item->hits;
				$data['id']             = 0;
				$data['tags']           = '';

				$newPrototypeID = $prototypeModel->save($data);
			}

		}
		$count = count($k2Items);

		return $count;
	}
}
