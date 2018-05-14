<?php
/**
 * @package    Synchronization Component
 * @version    1.0.2
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

class SynchronizationModelCompanies extends AdminModel
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
		$form = $this->loadForm('com_synchronization.companies', 'companies',
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
		$data = $app->getUserState('com_synchronization.companies.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.companies', $data);

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
		$items  = array();
		$select = ($data['total']) ? 'COUNT(*)' : '*';
		$db     = Factory::getDbo();

		// Get users
		$query = $db->getQuery(true)
			->select($select)
			->from($db->quoteName('#__k2_items'))
			->where('catid = 3')
			->where('published = 1')
			->where('trash = 0')
			->where('access = 1');
		if ($data['total'])
		{
			$db->setQuery($query);
			$count = $db->loadResult();

			return $count;
		}
		$db->setQuery($query, $data['offset'], $data['limit']);
		$k2Items = $db->loadObjectList('id');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_companies/models');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_companies/tables');

		JLoader::register('K2HelperUtilities', JPATH_SITE . '/components/com_k2/helpers/utilities.php');
		JLoader::register('CompaniesHelperRoute', JPATH_SITE . '/components/com_companies/helpers/route.php');
		JLoader::register('imageFolderHelper', JPATH_PLUGINS . '/fieldtypes/ajaximage/helpers/imagefolder.php');


		$companyModel      = BaseDatabaseModel::getInstance('Company', 'CompaniesModel');
		$k2Model           = BaseDatabaseModel::getInstance('K2', 'NerudasModel');
		$imageFolderHelper = new imageFolderHelper('images/companies');

		$site       = JApplication::getInstance('site');
		$siteRouter = $site->getRouter();
		$link       = str_replace(Uri::base(true), trim(Uri::root(true), '/'),
			$siteRouter->build(CompaniesHelperRoute::getListRoute())->toString());

		$this->addRedirect('/company.html', $link);

		foreach ($k2Items as $k2Item)
		{
			$k2Item->extra_fields = $k2Model->getItemExtraFields($k2Item->extra_fields, $k2Item);
			$k2Item->image        = JPATH_ROOT . '/media/k2/items/src/' . md5('Image' . $k2Item->id) . '.jpg';
			if ($k2Item->extra_fields)
			{
				$k2Item->extra = $k2Model->getItemExtra($k2Item->extra_fields);
			}

			$data                = array();
			$data['name']        = $k2Item->title;
			$data['alias']       = '';
			$data['about']       = $k2Item->introtext;
			$data['region']      = $k2Item->region;
			$data['state']       = 1;
			$data['access']      = 1;
			$data['imagefolder'] = $imageFolderHelper->createTemporaryFolder();
			$data['header']      = '';
			$data['logo']        = '';
			if (JFile::exists($k2Item->image))
			{
				JFile::copy($k2Item->image, JPATH_ROOT . '/' . $data['imagefolder'] . '/logo.jpg');
				$data['logo'] = $data['imagefolder'] . '/logo.jpg';
			}

			if (!empty($k2Item->extraFields))
			{
				$data['contacts']           = array();
				$data['contacts']['phones'] = array();
				$phones                     = array();
				if (!empty($k2Item->extraFields->phone_a) && !empty($k2Item->extraFields->phone_a->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_a->value;
					$phone['display'] = $phone['code'] . $phone['number'];
					$phones[]         = $phone;
				}
				if (!empty($k2Item->extraFields->phone_b) && !empty($k2Item->extraFields->phone_b->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_b->value;
					$phone['display'] = $phone['code'] . $phone['number'];
					$phones[]         = $phone;
				}
				if (!empty($k2Item->extraFields->phone_c) && !empty($k2Item->extraFields->phone_c->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_c->value;
					$phone['display'] = $phone['code'] . $phone['number'];
					$phones[]         = $phone;
				}
				if (!empty($k2Item->extraFields->phone_d) && !empty($k2Item->extraFields->phone_d->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_d->value;
					$phone['display'] = $phone['code'] . $phone['number'];
					$phones[]         = $phone;
				}
				if (!empty($k2Item->extraFields->phone_e) && !empty($k2Item->extraFields->phone_e->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_e->value;
					$phone['display'] = $phone['code'] . $phone['number'];
					$phones[]         = $phone;
				}
				$i = 1;
				foreach ($phones as $phone)
				{
					if ($i <= 3)
					{
						$data['contacts']['phones']['phone_' . $i] = $phone;
					}
					else
					{
						break;
					}
					$i++;
				}
				if (empty($data['contacts']['phones']))
				{
					unset($data['contacts']['phones']);
				}
				if (!empty($k2Item->extraFields->email) && !empty($k2Item->extraFields->email->value))
				{
					$data['contacts']['email'] = $k2Item->extraFields->email->value;
				}
				if (!empty($k2Item->extraFields->site) && !empty($k2Item->extraFields->site->value))
				{
					$data['contacts']['site'] = $k2Item->extraFields->site->value;
				}
				if (!empty($k2Item->extraFields->vk) && !empty($k2Item->extraFields->vk->value))
				{
					$data['contacts']['vk'] = str_replace(array('http://', 'https://', '/', 'www.', 'vk.com'), '',
						$k2Item->extraFields->vk->value);
				}
				if (!empty($k2Item->extraFields->fb) && !empty($k2Item->extraFields->fb->value))
				{
					$data['contacts']['facebook'] = str_replace(array('http://', 'https://', '/', 'www.', 'facebook.com'),
						'', $k2Item->extraFields->fb->value);
				}
				if (!empty($k2Item->extraFields->ok) && !empty($k2Item->extraFields->ok->value))
				{
					$data['contacts']['odnoklassniki'] = str_replace(array('http://', 'https://', '/', 'www.', 'ok.ru'),
						'', $k2Item->extraFields->ok->value);
				}

				$data['requisites'] = array();
				if (!empty($k2Item->extraFields->address) && !empty($k2Item->extraFields->address->value))
				{
					$data['requisites']['legal_address'] = $k2Item->extraFields->address->value;
				}
				if (!empty($k2Item->extraFields->inn) && !empty($k2Item->extraFields->inn->value))
				{
					$data['requisites']['inn'] = $k2Item->extraFields->inn->value;
				}
			}

			$data['attribs']    = array('company_layout' => '');
			$data['created']    = $k2Item->created;
			$data['created_by'] = $k2Item->created_by;
			$data['hits']       = $k2Item->hits;
			$data['id']         = '';
			$data['metakey']    = '';
			$data['metadesc']   = '';
			$data['metadata']   = array(
				'robots'     => '',
				'author'     => '',
				'rights'     => '',
				'xreference' => '',
			);
			$data['tags']       = '';

			$newCompanyID = $companyModel->save($data);
			$oldCompanyID = $k2Item->id;

			// Set Redirect
			$link = str_replace(Uri::base(true), trim(Uri::root(true), '/'),
				$siteRouter->build(CompaniesHelperRoute::getCompanyRoute($newCompanyID))->toString());
			$this->addRedirect('/company/' . $oldCompanyID . '.html', $link);

			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select(array('profile.created_by as user_id', 'profile.extra_fields as extra'))
				->from($db->quoteName('#__k2_related_items', 'r'))
				->where('parentID = ' . $oldCompanyID)
				->join('LEFT', $db->quoteName('#__k2_items', 'profile')
					. ' ON ' . $db->quoteName('profile.id') . ' = ' . $db->quoteName('r.childID'));
			$db->setQuery($query);
			$employees = $db->loadObjectList();
			foreach ($employees as $employee)
			{
				if (!empty($employee->user_id))
				{
					$employee->company_id = $newCompanyID;
					$employee->position   = '';
					if (!empty($employee->extra))
					{
						$extra = json_decode($employee->extra);
						unset($employee->extra);

						foreach ($extra as $value)
						{
							if ($value->id == 48)
							{
								$employee->position = $value->value;
							}
						}
					}
					$query = $db->getQuery(true)
						->select('company_id')
						->from('#__companies_employees')
						->where('company_id = ' . $employee->company_id)
						->where('user_id = ' . $employee->user_id);
					$db->setQuery($query);

					if (!empty($db->loadResult()))
					{
						$db->updateObject('#__companies_employees', $employee, array('company_id', 'user_id'));
					}
					else
					{
						$db->insertObject('#__companies_employees', $employee);
					}
				}
			}
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
