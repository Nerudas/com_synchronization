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

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class SynchronizationModelBoard extends AdminModel
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
		$pk    = 'board';
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
		$form = $this->loadForm('com_synchronization.board', 'board',
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
		$data = $app->getUserState('com_synchronization.board.edit.document.data', array());
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
		$data['type']     = 'board';
		$data['last_run'] = Factory::getDate()->toSql();

		// Prepare attribs json
		if (isset($data['params']) && is_array($data['params']))
		{
			$registry       = new Registry($data['params']);
			$data['params'] = (string) $registry;
		}

		return parent::save($data);
	}

	protected $imageFolder = 'images/board/items';

	/**
	 * Method to parse the form data.
	 *
	 * @param   array $data The form data.
	 *
	 * @return  mixed boolean  True on success.
	 *
	 * @since   1.0.0
	 */

	public function parse($data)
	{
		$categories = NerudasK2Helper:: getCategoryTree(177);
		$select     = ($data['total']) ? 'COUNT(*)' : '*';

		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($select)
			->from($db->quoteName('#__k2_items'))
			->where($db->quoteName('catid') . ' IN (' . implode(',', $categories) . ')')
			->where('published = 1')
			->where('trash = 0')
			->where('access = 1')
			->order('created DESC');


		if ($data['total'])
		{
			$db->setQuery($query);
			$count = $db->loadResult();

			return $count;
		}
		$db->setQuery($query, $data['offset'], $data['limit']);
		$items = $db->loadObjectList('id');


		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_board/models');
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_nerudas/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_board/tables');
		JLoader::register('K2HelperUtilities', JPATH_SITE . '/components/com_k2/helpers/utilities.php');

		foreach ($items as $k2ID => $k2Item)
		{
			$boardModel = BaseDatabaseModel::getInstance('item', 'BoardModel');
			$k2Model    = BaseDatabaseModel::getInstance('K2', 'NerudasModel');


			$k2Item->extra_fields = $k2Model->getItemExtraFields($k2Item->extra_fields, $k2Item);
			$k2Item->image        = JPATH_ROOT . '/media/k2/items/src/' . md5('Image' . $k2ID) . '.jpg';;
			if ($k2Item->extra_fields)
			{
				$k2Item->extra = $k2Model->getItemExtra($k2Item->extra_fields);
			}

			$item               = array();
			$item['id']         = 0;
			$item['title']      = $k2Item->title;
			$item['text']       = $k2Item->introtext;
			$item['region']     = $k2Item->region;
			$item['state']      = $k2Item->published;
			$item['access']     = $k2Item->access;
			$item['attribs']    = array('item_layout' => '');
			$item['created']    = $k2Item->created;
			$item['created_by'] = $k2Item->created_by;
			$item['hits']       = $k2Item->hits;
			$item['for_when']   = 'today';

			// Price
			$item['price']          = (!empty($k2Item->extra) && !empty($k2Item->extra['price'])
				&& !empty($k2Item->extra['price']->value) && is_numeric($k2Item->extra['price']->value)) ?
				$k2Item->extra['price']->value : '-0';
			$item['payment_method'] = 'all';
			if (!empty($k2Item->extra) && !empty($k2Item->extra['payment_method'])
				&& !empty($k2Item->extra['payment_method']->value))
			{
				if ($k2Item->extra['payment_method']->value == 'безнал')
				{
					$item['payment_method'] = 'cashless';
				}
				if ($k2Item->extra['payment_method']->value == 'наличные')
				{
					$item['payment_method'] = 'cash';
				}
			}
			$item['prepayment'] = 'all';
			if (!empty($k2Item->extra) && !empty($k2Item->extra['prepayment'])
				&& !empty($k2Item->extra['prepayment']->value))
			{
				if ($k2Item->extra['prepayment']->value == 'обязательно!')
				{
					$item['prepayment'] = 'required';
				}
				if ($k2Item->extra['prepayment']->value == 'без предоплаты')
				{
					$item['prepayment'] = 'no';
				}
			}

			// Map
			if (!empty($k2Item->latitude) && !empty($k2Item->longitude) &&
				$k2Item->latitude !== '0.000000' && $k2Item->longitude !== '0.000000')
			{
				$item['map'] = array();

				$item['map']['placemark']                = array();
				$item['map']['placemark']['coordinates'] = '["' . $k2Item->latitude . '", "' . $k2Item->longitude . '"]';
				$item['map']['placemark']['latitude']    = $k2Item->latitude;
				$item['map']['placemark']['longitude']   = $k2Item->longitude;

				$item['map']['params']              = array();
				$item['map']['params']['center']    = '["' . $k2Item->latitude . '", "' . $k2Item->longitude . '"]';
				$item['map']['params']['latitude']  = $k2Item->latitude;
				$item['map']['params']['longitude'] = $k2Item->longitude;
				$item['map']['params']['zoom']      = 10;
			}


			// Contacts
			$profile          = NerudasProfilesHelper::getProfile($k2Item->created_by);
			$item['contacts'] = array();
			if (!empty($profile->phone))
			{
				$item['contacts']['phones'] = array(
					'phone_1' => array(
						'code'    => '+7',
						'number'  => $profile->phone->sysnumber,
						'display' => '+7' . $profile->phone->sysnumber,
					)
				);
			}
			if (!empty($profile->email))
			{
				$item['contacts']['email'] = $profile->email;
			}

			if (!empty($profile->site))
			{
				$item['contacts']['site'] = $profile->site;
			}
			if (!empty($profile->vk))
			{
				$item['contacts']['vk'] = $profile->vk;
			}
			if (!empty($profile->fb))
			{
				$item['contacts']['facebook'] = $profile->fb;
			}

			// Create Image folder
			$path   = $this->imageFolder . '/tmp_' . uniqid();
			$folder = JPATH_ROOT . '/' . $path;
			while (JFolder::exists($folder))
			{
				$path   = $this->imageFolder . '/tmp_' . uniqid();
				$folder = JPATH_ROOT . '/' . $path;
			}
			JFolder::create($folder);
			JFile::write($folder . '/index.html', '<!DOCTYPE html><title></title>');
			$item['imagefolder'] = str_replace(JPATH_ROOT . '/', '', $folder);


			$item['images'] = array();
			if (JFile::exists($k2Item->image))
			{
				// Upload image
				$newImage = uniqid() . '.jpg';
				JFile::copy($k2Item->image, $folder . '/' . $newImage);
				$item['images'] = array('image_1' => array(
					'file' => $newImage,
					'src'  => $item['imagefolder'] . '/' . $newImage
				));
			}

			$boardModel->save($item);
		}
		$count = count($items);

		return $count;
	}
}
