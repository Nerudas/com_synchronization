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

class SynchronizationControllerK2 extends FormController
{

	/**
	 * Create redirects function
	 */
	public function createRedirects()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->createRedirects())
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}

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
			10004,
			10019,
			10172,
			10009,
			10010,
			10104,
			10111,
			10155,
			10165,
			10173,
			10237,
			10243,
			10250,
			10251,
			10260,
			10167,
			10164,
			10132,
		)))
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}

	/**
	 * Delete modules function
	 */
	public function deleteModules()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->deleteModules(array(
			352,
			286,
			284,
			226,
			281,
			275,
			240,
			288,
			282,
			246,
			241,
			355,
		)))
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}

	/**
	 * Delete Menus function
	 */
	public function deleteMenus()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->deleteMenus(array(
			47,
			50,
			53,
			45,
			48,
			28
		)))
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}


	/**
	 * Delete Menus function
	 */
	public function deleteMenuItems()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->deleteMenuItems(array(
			647,
			1637,
			1623,
			2041,
			2042,
			1630,
		)))
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
	}

	/**
	 * Delete Menus function
	 */
	public function accessMenuItems()
	{
		$model = $this->getModel();

		$errors = array();
		if (!$model->accessMenuItems(array(
			2266,
			2267,
			2265,
			2268,
			2271
		)))
		{
			$errors = $model->getErrors();
		}

		return $this->setResponse($errors);
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