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

use Joomla\CMS\Table\Table;

class SynchronizationTableSynchronization extends Table
{

	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver &$db Database connector object
	 *
	 * @since   1.0.0
	 */
	function __construct(&$db)
	{
		parent::__construct('#__synchronization', 'type', $db);
	}

	/**
	 * Validate that the primary key has been set.
	 *
	 * @return  boolean  True if the primary key(s) have been set.
	 *
	 * @since   1.0.0
	 */
	public function hasPrimaryKey()
	{
		$query = $this->_db->getQuery(true)
			->select('COUNT(*)')
			->from($this->_tbl);
		$this->appendPrimaryKeys($query);

		$this->_db->setQuery($query);
		$count = $this->_db->loadResult();

		if ($count == 1)
		{
			$empty = false;
		}
		else
		{
			$empty = true;
		}

		return !$empty;
	}


}