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

class SynchronizationControllerGeolocations extends FormController
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  1.0.5
	 */
	protected $text_prefix = 'COM_SYNCHRONIZATION_GEOLOCATIONS';

	/**
	 * Method to parse a record.
	 *
	 * @param   string $key    The name of the primary key of the URL variable.
	 * @param   string $urlVar The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   1.0.5
	 */
	public function parse($key = null, $urlVar = null)
	{

		$app = Factory::getApplication();

		$data  = $this->input->post->get('jform', array(), 'array');
		$first = ($this->input->post->get('first', 'true') == 'true');
		$model = $this->getModel();
		if ($first)
		{
			if (!parent::save($key, $urlVar))
			{
				return false;
			}

			$data['total'] = true;

			$result = $model->parse($data);
			echo new JsonResponse($result);

			$app->close();

			return true;

		}

		$data['total']  = false;
		$data['offset'] = $this->input->post->get('offset', 0);
		$data['limit']  = $this->input->post->get('limit', 0);

		echo new JsonResponse($model->parse($data));

		$app->close();

		return true;
	}
}
