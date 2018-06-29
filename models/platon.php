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
use Joomla\CMS\Uri\Uri;

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

		exit();

		$select = ($data['total']) ? 'COUNT(*)' : '*';
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select($select)
			->from($db->quoteName('#__k2_items'))
			->where($db->quoteName('catid') . ' IN (' . implode(',', $categories) . ')')
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


		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_info/models');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_info/tables');

		JLoader::register('K2HelperUtilities', JPATH_SITE . '/components/com_k2/helpers/utilities.php');
		JLoader::register('InfoHelperRoute', JPATH_SITE . '/components/com_info/helpers/route.php');
		JLoader::register('K2HelperRoute', JPATH_SITE . '/components/com_k2/helpers/route.php');
		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');

		$infoModel         = BaseDatabaseModel::getInstance('Item', 'InfoModel');
		$k2Model           = BaseDatabaseModel::getInstance('K2', 'NerudasModel');
		$imageFolderHelper = new imageFolderHelper('images/info');

		$site       = JApplication::getInstance('site');
		$siteRouter = $site->getRouter();

		foreach ($k2Items as $k2Item)
		{
			$k2Item->extra_fields = $k2Model->getItemExtraFields($k2Item->extra_fields, $k2Item);
			$k2Item->image        = JPATH_ROOT . '/media/k2/items/src/' . md5('Image' . $k2Item->id) . '.jpg';
			if ($k2Item->extra_fields)
			{
				$k2Item->extra = $k2Model->getItemExtra($k2Item->extra_fields);
			}

			$state = 0;
			if ($k2Item->published && !$k2Item->trashed)
			{
				$state = 1;
			}
			elseif ($k2Item->trashed)
			{
				$state = -2;
			}

			$list_item_layout = '';
			if (in_array($k2Item->catid, $rabotaemCategories))
			{
				$list_item_layout = 'text';
			}
			if (in_array($k2Item->catid, $herakCategories))
			{
				$list_item_layout = 'image';
			}

			$comments_title = (!empty($k2Item->extraFields->comments) && !empty($k2Item->extraFields->comments->value))
				? $k2Item->extraFields->comments->value : '';

			$data                 = array();
			$data['id']           = 0;
			$data['title']        = $k2Item->title;
			$data['region']       = $k2Item->region;
			$data['state']        = $state;
			$data['access']       = $k2Item->access;
			$data['in_work']      = 0;
			$data['attribs']      = array(
				'item_layout'      => '',
				'list_item_layout' => $list_item_layout,
				'comments_title'   => $comments_title,
				'related_title'    => '',
			);
			$data['created']      = $k2Item->created;
			$data['publish_up']   = $k2Item->publish_up;
			$data['publish_down'] = $k2Item->publish_down;
			$data['modified']     = $k2Item->modified;
			$data['created_by']   = $k2Item->created_by;
			$data['hits']         = $k2Item->hits;
			$data['id']           = '';
			$data['metakey']      = '';
			$data['metadesc']     = '';
			$data['metadata']     = array(
				'robots'     => '',
				'author'     => '',
				'rights'     => '',
				'xreference' => '',
			);
			$data['tags']         = '';


			$data['imagefolder'] = $imageFolderHelper->createTemporaryFolder();
			$data['header']      = '';
			$data['introimage']  = '';
			if (JFile::exists($k2Item->image))
			{
				JFile::copy($k2Item->image, JPATH_ROOT . '/' . $data['imagefolder'] . '/intro.jpg');
				$data['introimage'] = $data['imagefolder'] . '/intro.jpg';
			}

			$fulltext = $k2Item->fulltext;
			preg_match_all('/<img(.*)src="([^ "]*)"/i', $fulltext, $matches);
			$fulltext_images = (!empty($matches[2])) ? array_unique($matches[2]) : array();
			foreach ($fulltext_images as $old)
			{
				$new      = '{imageFolder}/' . JFile::getName(JPATH_ROOT . '/' . rtrim($old, '/'));
				$fulltext = str_replace($old, $new, $fulltext);
			}
			$data['fulltext'] = str_replace(array('{hits}', '{commentsCount}', '{comments}', '{link}', '{date}'),
				'', $fulltext);

			$introtext = $k2Item->introtext;
			preg_match_all('/<img(.*)src="([^ "]*)"/i', $introtext, $matches);
			$introtext_images = (!empty($matches[2])) ? array_unique($matches[2]) : array();
			foreach ($introtext_images as $old)
			{
				$new       = '{imageFolder}/' . JFile::getName(JPATH_ROOT . '/' . rtrim($old, '/'));
				$introtext = str_replace($old, $new, $introtext);
			}
			$data['introtext'] = str_replace(array('{hits}', '{commentsCount}', '{comments}', '{link}', '{date}'),
				'', $introtext);

			$old_images = array_unique(array_merge($fulltext_images, $introtext_images));

			$data['images'] = array();

			if (!empty($old_images))
			{
				$i = 1;
				foreach ($old_images as $old)
				{

					$delete   = true;
					$fullpath = JPATH_ROOT . '/' . rtrim($old, '/');
					if (!JFile::exists($fullpath))
					{
						$delete   = false;
						$fullpath = str_replace('images/', 'images-old/', $fullpath);
					}
					$key  = 'image_' . $i;
					$file = JFile::getName($fullpath);
					$src  = $data['imagefolder'] . '/content/' . $file;

					if (!JFolder::exists(JPATH_ROOT . '/' . $data['imagefolder'] . '/content'))
					{
						JFolder::create(JPATH_ROOT . '/' . $data['imagefolder'] . '/content');
					}

					if (JFile::exists($fullpath) && JFile::move($fullpath, JPATH_ROOT . '/' . $src))
					{
						$data['images'][$key] = array(
							'file' => $file,
							'src'  => $src,
							'text' => ''
						);

						if ($delete)
						{
							$dirname = dirname($fullpath);

							$files = JFolder::files($dirname, '', true, true, 'index.html');
							if (empty($files))
							{
								JFolder::delete($dirname);
							}
						}

						$i++;
					}
				}
			}

			$newInfoID = $infoModel->save($data);
			$oldInfoID = $k2Item->id;

			$oldLink = str_replace(Uri::base(true), trim(Uri::root(true), '/'),
				$siteRouter->build(K2HelperRoute::getItemRoute($oldInfoID, $k2Item->catid))->toString());
			$oldLink = str_replace('info-old', 'info', $oldLink);

			// Set Redirect
			$newlink = str_replace(Uri::base(true), trim(Uri::root(true), '/'),
				$siteRouter->build(InfoHelperRoute::getItemRoute($newInfoID))->toString());
			$this->addRedirect($oldLink, $newlink);
		}
		$count = count($k2Items);

		return $count;
	}

	/**
	 * Method to create redirect
	 *
	 * @param string $old old url
	 * @param string $new new url
	 *
	 * @return  bool
	 *
	 * @since 1.0.1
	 */
	protected function addRedirect($old, $new)
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

		return (!empty($id)) ? $db->updateObject('#__sefwizard_redirects', $redirect, 'id') :
			$db->insertObject('#__sefwizard_redirects', $redirect);
	}
}
