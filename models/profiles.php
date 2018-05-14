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

class SynchronizationModelProfiles extends AdminModel
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
		$pk    = 'profiles';
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
		$form = $this->loadForm('com_synchronization.profiles', 'profiles',
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
		$data = $app->getUserState('com_synchronization.profiles.edit.document.data', array());
		if (empty($data))
		{
			$data = $this->getItem();
		}
		$this->preprocessData('com_synchronization.profiles', $data);

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
		$data['type']     = 'profiles';
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
			->from($db->quoteName('#__users'));
		if ($data['total'])
		{
			$db->setQuery($query);
			$count = $db->loadResult();

			return $count;
		}
		$db->setQuery($query, $data['offset'], $data['limit']);
		$users = $db->loadObjectList('id');

		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__k2_items'))
			->where('catid = 10')
			->where('published = 1')
			->where('trash = 0')
			->where('access = 1')
			->where($db->quoteName('created_by') . ' IN (' . implode(',', array_keys($users)) . ')');
		$db->setQuery($query);
		$k2Items = $db->loadObjectList('created_by');

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/models');
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_nerudas/models');
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_profiles/tables');
		JLoader::register('K2HelperUtilities', JPATH_SITE . '/components/com_k2/helpers/utilities.php');
		JLoader::register('ProfilesHelperRoute', JPATH_SITE . '/components/com_profiles/helpers/route.php');

		$profileModel = BaseDatabaseModel::getInstance('Profile', 'ProfilesModel');
		$k2Model      = BaseDatabaseModel::getInstance('K2', 'NerudasModel');

		$site       = JApplication::getInstance('site');
		$siteRouter = $site->getRouter();

		$link = str_replace(Uri::base(true), trim(Uri::root(true), '/'),
			$siteRouter->build(ProfilesHelperRoute::getListRoute())->toString());

		$this->addRedirect('/persons.html', $link);

		// Profiles
		foreach ($users as $user)
		{
			$k2Item = (!empty($k2Items[$user->id])) ? $k2Items[$user->id] : false;
			if (!$k2Item)
			{
				continue;
			}

			$k2Item->extra_fields = $k2Model->getItemExtraFields($k2Item->extra_fields, $k2Item);
			$k2Item->image        = JPATH_ROOT . '/media/k2/items/src/' . md5('Image' . $k2Item->id) . '.jpg';
			if ($k2Item->extra_fields)
			{
				$k2Item->extra = $k2Model->getItemExtra($k2Item->extra_fields);
			}

			$user->name   = $k2Item->title;
			$user->params = new Registry($user->params);
			$user->params->set('region', $k2Item->region);
			$user->params = (string) $user->params;

			$db->updateObject('#__users', $user, 'id');

			$data                 = array();
			$data['id']           = $user->id;
			$data['name']         = $user->name;
			$data['block']        = 0;
			$data['registerDate'] = $user->registerDate;
			$data['params']       = $user->params;
			$data['alias']        = 'id' . $user->id;

			$query = $db->getQuery(true)
				->select('text')
				->from('#__profiles_status')
				->where('id = ' . $k2Item->id);
			$db->setQuery($query);
			$data['status'] = $db->loadResult();
			$data['about']  = $k2Item->introtext;

			$data['imagefolder'] = 'images/profiles/' . $user->id;
			$data['avatar']      = '';
			$data['header']      = '';
			if (!JFolder::exists(JPATH_ROOT . '/' . $data['imagefolder']))
			{
				JFolder::create(JPATH_ROOT . '/' . $data['imagefolder']);
				JFile::write(JPATH_ROOT . '/' . $data['imagefolder'] . '/index.html', '<!DOCTYPE html><title></title>');
			}
			if (JFile::exists($k2Item->image))
			{
				JFile::copy($k2Item->image, JPATH_ROOT . '/' . $data['imagefolder'] . '/avatar.jpg');
				$data['avatar'] = $data['imagefolder'] . '/avatar.jpg';
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

					$phones[] = $phone;
				}
				if (!empty($k2Item->extraFields->phone_b) && !empty($k2Item->extraFields->phone_b->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_b->value;
					$phone['display'] = $phone['code'] . $phone['number'];

					$phones[] = $phone;
				}
				if (!empty($k2Item->extraFields->phone_c) && !empty($k2Item->extraFields->phone_c->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_c->value;
					$phone['display'] = $phone['code'] . $phone['number'];

					$phones[] = $phone;
				}
				if (!empty($k2Item->extraFields->phone_d) && !empty($k2Item->extraFields->phone_d->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_d->value;
					$phone['display'] = $phone['code'] . $phone['number'];

					$phones[] = $phone;
				}
				if (!empty($k2Item->extraFields->phone_e) && !empty($k2Item->extraFields->phone_e->value))
				{
					$phone            = array();
					$phone['code']    = '+7';
					$phone['number']  = $k2Item->extraFields->phone_e->value;
					$phone['display'] = $phone['code'] . $phone['number'];

					$phones[] = $phone;
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
			}

			$data['access']  = 1;
			$data['attribs'] = array('profile_layout' => '');
			$data['region']  = $k2Item->region;
			$data['metakey']    = '';
			$data['metadesc']   = '';
			$data['metadata']   = array(
				'robots'     => '',
				'author'     => '',
				'rights'     => '',
				'xreference' => '',
			);
			$data['tags']    = '';

			$profileModel->save($data);

			$link = str_replace(Uri::base(true), trim(Uri::root(true), '/'),
				$siteRouter->build(ProfilesHelperRoute::getProfileRoute($data['id']))->toString());

			$this->addRedirect('/persons/' . $k2Item->id . '.html', $link);
		}

		// Socials
		$query = $db->getQuery(true)
			->delete('#__user_socials')
			->where($db->quoteName('user_id') . ' IN (' . implode(',', array_keys($users)) . ')');
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
			->select(array('user_id', 'slogin_id as social_id', 'provider'))
			->from($db->quoteName('#__slogin_users'))
			->where($db->quoteName('user_id') . ' IN (' . implode(',', array_keys($users)) . ')');
		$db->setQuery($query);
		$socials = $db->loadObjectList();

		$providers = array('vk', 'facebook', 'instagram', 'odnoklassniki');
		foreach ($socials as $social)
		{
			$social->provider = ($social->provider == 'vkontakte') ? 'vk' : $social->provider;
			if (in_array($social->provider, $providers))
			{
				$db->insertObject('#__user_socials', $social);
			}
		}

		$count = count($users);

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
