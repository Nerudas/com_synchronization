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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

class SynchronizationControllerUsers extends FormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $text_prefix = 'COM_SYNCHRONIZATION_USERS';


	/**
	 * Method to save a record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.0.0
	 */
	public function synchronize($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$model = $this->getModel();

		$this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_item, false));

		if (!$model->synchronize())
		{
			$this->setError(Text::sprintf('COM_SYNCHRONIZATION_ERROR_SYNCHRONIZE', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			return false;
		}

		$this->setMessage(Text::_('COM_SYNCHRONIZATION_USERS_SYNCHRONIZE_COMPLITE'));


		return true;
	}

	/**
	 * Method to synchronizeProfiles
	 *
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.0.0
	 */
	public function synchronizeProfiles()
	{

		$app   = Factory::getApplication();
		$model = $this->getModel();
		$users = $model->synchronizeProfiles();

		if (!$users || !Session::checkToken())
		{
			if (!$users)
			{
				$this->setError(Text::sprintf('COM_SYNCHRONIZATION_ERROR_SYNCHRONIZE', $model->getError()));
			}
			if (!Session::checkToken())
			{
				$this->setError(Text::_('JINVALID_TOKEN'));
			}
			echo new JsonResponse('', $this->getError(), true);
			$app->close();

			return false;
		}

		$response        = new stdClass();
		$response->html  = '';
		$response->count = count($users);
		foreach ($users as $id => $name)
		{
			$response->html .= '<li data-id="' . $id . '">' . '<i class="icon-loop"></i>' . $name . '</li>';
		}
		echo new JsonResponse($response);

		$app->close();

		return true;
	}

	/**
	 * Method to synchronizeProfiles
	 *
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.0.0
	 */
	public function synchronizeProfile()
	{

		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));


		$app   = Factory::getApplication();
		$model = $this->getModel();
		$id    = $app->input->get('id', 0, 'int');
		$user  = $model->synchronizeProfile($id);
		if (!$user || !Session::checkToken())
		{
			if (!$user)
			{
				$this->setError($model->getError());
			}
			if (!Session::checkToken())
			{
				$this->setError(Text::_('JINVALID_TOKEN'));

			}
			$error = (!empty($this->getError())) ? ' <span class="text-error">' . $this->getError() . '</span>' : '';
			$text  = '<i class="icon-cancel-2 text-error"></i>' . Factory::getUser($id)->name . $error;
			echo new JsonResponse($text, $this->getError(), true);
			$app->close();

			return false;
		}


		echo new JsonResponse('<i class="icon-ok text-success"></i>' . $user);

		$app->close();

		return true;
	}

}
