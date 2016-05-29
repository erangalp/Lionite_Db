<?php
/**
 * Lionite Database Paginator Class
 * 
 * Transforms a select object to a count of pages and rows
 * 
 * @category   Lionite
 * @package    Lionite_Db
 * @author     Eran Galperin
 * @copyright  Eran Galperin All rights reserved
 */
class Lionite_Db_Paginator {
	/**
	 * Select object
	 * @var Lionite_Db_Select $_select
	 */
	protected $_select;
	
	/**
	 * Total page count
	 * @var int $_pages
	 */
	protected $_pages = null;
	
	/**
	 * Total row count
	 * @var int $_rows
	 */
	protected $_rows = null;
		
	/**
	 * Row count page per page
	 * @var int $_perPage
	 */
	protected $_perPage = 10;
		
	/**
	 * Constructor
	 * - Optinally pass a column for the count query if differs from '*'
	 * 
	 * @param Lionite_Db_Select $select
	 * @param string $countCol
	 */
	public function __construct(Lionite_Db_Select $select,$countCol = '*'){
		$this -> _select = clone $select;
		$this -> _select -> prepareForCount('count',$countCol);
	}
	
	/**
	 * Rows per page setter
	 * @param int $perpage
	 */
	public function setPerpage($perpage = 10) {
		$this -> _perPage = $perpage;
	}
	
	/**
	 * Get page count
	 * @return int
	 */
	public function pages() {
		if(is_null($this -> _pages)) {
			$this -> _count();
		}
		return $this -> _pages;
	}
	
	/**
	 * Get total row count
	 * @return int
	 */
	public function total() {
		if(is_null($this -> _rows)) {
			$this -> _count();
		}
		return $this -> _rows;
	}
	
	/**
	 * Count rows
	 * 
	 * Stores row count and page count internally in the instance
	 */
	protected function _count() {
		if(!empty($this -> _perPage)) {
			$count = $this -> _select -> getAdapter() -> fetchOne($this -> _select);
			$this -> _pages = ((int)(($count - 1) / $this -> _perPage)) + 1;
			$this -> _rows = $count;
		}
		
	}
}