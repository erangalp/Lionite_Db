<?php
/**
 * Lionite Database Select Class
 * 
 * Provides additional methods for Zend_Db_Select
 * 
 * @category   Lionite
 * @package    Lionite_Db
 * @author     Eran Galperin
 * @copyright  Eran Galperin All rights reserved
 */
class Lionite_Db_Select extends Zend_Db_Select {
	/**
	 * Prepare select object for count query
	 * 
	 * Modifies original query to the minimum necessary to count the result set
	 *
	 * @param string $countColName Name of count column returned
	 * @return Lionite_Db_Select
	 */
	public function prepareForCount($countColName = 'count') {
		$this -> reset(Zend_Db_Select::COLUMNS);
		$this -> reset(Zend_Db_Select::LIMIT_COUNT);
		$this -> reset(Zend_Db_Select::LIMIT_OFFSET);
		$this -> reset(Zend_Db_Select::GROUP);
		$this -> reset(Zend_Db_Select::ORDER );
		$table = key($this -> _parts['from']);
		
		// Remove non-filtering left-joined tables
		$filteringTables = $this ->_getFilteringTables();
		foreach($this -> _parts['from'] as $tableKey => $clause) {
			if($clause['joinType'] == 'left join' && !in_array($tableKey,$filteringTables)) {
				unset($this -> _parts['from'][$tableKey]);
			}
		}

		$this -> _parts['columns'] = array(
			0 => array(
				0 => $table,
				1 => new Zend_Db_Expr('COUNT(*)'),
				2 => $countColName
			)
		);
		return $this;
	}
	
	/**
	 * Determine which tables are needed for filtering query results
	 * @return array
	 */
	protected function _getFilteringTables() {
		$filteringTables = array();
		foreach($this -> _parts['where'] as $where) {
			$where = str_replace(array('(',')'),'',$where);
			$parts = explode(' AND ',$where);
			foreach($parts as $part) {
				$orParts = explode(' OR ',$part);
				foreach($orParts as $col) {
					if(strpos($col,'.') !== false) {
						$colParts = explode('.',$col);
						$filteringTables[] = trim($colParts[0]);
					}

				}
			}
		}
		return $filteringTables;
	}
}