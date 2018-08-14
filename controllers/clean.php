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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

class SynchronizationControllerClean extends FormController
{

	/**
	 * Create redirects function
	 */
	public function deleteDB()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->deleteDB())
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}

	/**
	 * Delete extension function
	 */
	public function deleteExtensions()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->deleteExtensions(array(
			10347,
			10168,
			10261,
			10187,
			10147,
			10161,
		)))
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}


	/**
	 * Create redirects function
	 */
	public function deleteFolders()
	{
		$errors = array();
		foreach (array('images/old_regions') as $folder)
		{
			if (JFolder::exists(JPATH_ROOT . '/' . $folder))
			{
				JFolder::delete(JPATH_ROOT . '/' . $folder);
			}
		}

		return $this->setResponse();
	}


	/**
	 * Method to send json response
	 *
	 * @param array $errors Errors text
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	protected function setResponse($errors = array())
	{
		$msg = implode(PHP_EOL, $errors);

		echo new JsonResponse('', $msg, (!empty($errors)));

		Factory::getApplication()->close();

		return true;
	}
}