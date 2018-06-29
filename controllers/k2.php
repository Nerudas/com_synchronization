<?php
/**
 * @package    Synchronization Component
 * @version    1.0.4
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