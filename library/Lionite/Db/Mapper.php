<?php
/**
 * Class for SQL data mapping
 *
 * Lionite_Db_Mapper abstracts query composition via Zend_Db_Select and Zend_Db_Table relationships
 * 
 * @category   Lionite
 * @package    Lionite_Db
 * @author     Eran Galperin
 * @copyright  Eran Galperin All rights reserved
 */
class Lionite_Db_Mapper 
{
	const ADAPTER = 'db';
	const TABLE = 'table';
	const MAP = 'map';
	
	const THIS = false;
	const LASTJOINED = true;
	
	/**
	 * Internal query composition object
	 * @var Zend_Db_Select
	 */
	protected $_select;
	
	/**
	 * Mapper table name
	 * @var string 
	 */
	protected $_name;
	
	/**
     * Database adapter
     * @var Zend_Db_Adapter_Abstract
     */
	protected $_db;
	
	/**
	 * Last joined table name
	 * @see with() method
	 * @var string
	 */
	protected $_lastJoin = null;
	
	/**
	 * Last joined model class
	 * @see with() method
	 * @var string
	 */
	protected $_lastJoinClass = null;

	/**
	 * Join types enumeration
	 * @var array
	 */
	protected $_joinTypes = array(
		'joinLeft','joinRight','joinFull','joinCross','joinNatural','join'
	);
	
	/**
	 * Tables name cache
	 * @var array
	 */
	protected $_nameCache = array();

	/**
	 * Number of items per page
	 * Used for paginator generation
	 * @var int
	 */
	protected $_perPage;

	/**
     * Constructor.
     *
     * Supported params for $config are:
     * - db              = user-supplied instance of database adapter
     * - table           = mapper table name.
     * - map		     = table relationships declaration (Array)
     * @param  mixed $config Array of user-specified config options OR the database adapter
     */
	public function __construct($config = array()) {
		if (!is_array($config)) {
            $config = array(self::ADAPTER => $config);
        }
		foreach ($config as $key => $value) {
            switch ($key) {
                case self::ADAPTER:
                    $this-> _setAdapter($value);
                    break;
                case self::TABLE:
                    $this-> _setTable($value);
                    break;
                case self::MAP:
                	$this -> _map = $value;
                default:
                	break;
            }
		 }
	}
	
	/**
	 * Set database adapter
	 * @param Zend_Db_Adapter_Abstract $db
	 */
	protected function _setAdapter(Zend_Db_Adapter_Abstract $db) {
		$this -> _db = $db;
	}
	
	/**
	 * Get database adapter
	 * Fetch default adapater if internal adapater is not set
	 * 
	 * @return Zend_Db_Adapter_Abstract
	 */
	public function getAdapter() {
		if(!isset($this -> _db)){
			$this -> _db = Zend_Db_Table::getDefaultAdapter();
		}
		return $this -> _db;
	}
	
	/**
	 * Set base mapper table name
	 * @param string / Zend_Db_Table $table
	 */
	protected function _setTable($table) {
		if($table instanceof Zend_Db_Table){
			$name = $table -> getTableName();
		} else if(is_string($table)){
			$name = $table;
		} else {
			throw new Lionite_Db_Exception('Provided table parameter should be string OR instance of Zend_Db_Table');
		}
		$this -> _name = $name;
	}
	
	/**
	 * Get mapper query compositor
	 *
	 * - A partially initialized Zend_Db_Select object may be injected
	 * - Passing a boolean true will force the creation of a new select object
	 * 
	 * @param mixed Zend_Db_Select $select / boolean
	 * @return Zend_Db_Select
	 */
	public function select($select = null) {
		if($select instanceof Zend_Db_Select){
			$this -> _select = $select;
		} else if($select === true || is_null($this -> _select)){
			$this -> _select = new Lionite_Db_Select($this -> getAdapter());
		}
		return $this -> _select;
	}
	
	/**
	 * Internal Zend_Db_Select reset method
	 * @param string $part Select part to reset
	 */
	public function reset($part = null) {
		if($this -> _select instanceof Zend_Db_Select){
			$this -> _select -> reset($part);
		}
	}
	
	/**
	 * Query database with prepared statement
	 * @return array
	 */
	public function query()	{
		return $this -> getAdapter() -> fetchAll($this -> select());
	}
	
	/**
	 * Query database with prepared statement, retrieve one row
	 * @return array
	 */
	public function queryOne(){
		return $this -> getAdapter() -> fetchRow($this -> select());
	}
	
	/**
	 * Get all rows from mapper table
	 * 
	 * - Optional columns array may be provided
	 * - Common start to most query compositions before additional clauses are added
	 * - Returns self for fluid interface
	 * @param array $cols
	 * @param string / array $where
	 * @return self
	 */
	public function get($cols = array('*'),$where = null) {
		$this -> select(true) -> from($this -> _name,$cols);
		if(is_string($where)){
			$this -> where($where);
		}
		return $this;
	}
	
	/**
	 * Add where clause
	 *
	 * @see self::parseTableName()
	 * @param string $clause
	 * @param string $tableName Table class name
	 * @return self
	 */
	public function where($clause,$tableName = null) {
		$table = $this -> parseTableName($tableName);
		if(is_string($clause)){
			if(strpos($clause,'AND') === false && strpos($clause,'OR') === false && $tableName !== false) {
				$clause = $table . '.' . $clause;
			}
			$this -> select() -> where($clause);
		} else if(is_array($clause)){
			foreach($clause as $key => $val){
				$this -> select() -> where($table . '.' . $key . '=?',$val);
			}
		}
		return $this;
	}
	
	/**
	 * Add OR where clause
	 *
	 * @see self::parseTableName()
	 * @param string $clause
	 * @param string $tableName Table class name
	 * @return self
	 */
	public function orWhere($clause,$tableName = null) {
		$table = $this -> parseTableName($tableName);
		if(is_string($clause)){
			$this -> select() -> orWhere($table . '.' . $clause);
		} else if(is_array($clause)){
			foreach($clause as $key => $val){
				$this -> select() -> orWhere($table . '.' . $key . '=?',$val);
			}
		}
		return $this;
	}
	
	/**
	 * Add group by clause
	 *
	 * @see self::parseTableName()
	 * @param string $clause
	 * @param string $tableName Table class name
	 * @return self
	 */
	public function group($clause,$tableName = null) {
		if($tableName !== false) {
		 	$clause = $this -> parseTableName($tableName) . '.' . $clause;
		}
		$this -> select() -> group($clause);
		return $this;
	}
	
	/**
	 * Add order by clause
	 *
	 * @see self::parseTableName()
	 * @param string $clause
	 * @param $tableName Table class name
	 * @return self
	 */
	public function order($clause,$tableName = null) {
		if(is_string($clause)){
			if(strpos($clause,'.') === false && $tableName !== false){
				$table = $this -> parseTableName($tableName);
				$this -> select() -> order($table . '.' . $clause);
			} else {
				$this -> select() -> order($clause);
			}
		}
		return $this;
	}
	
	/**
	 * Add having clause
	 * 
	 * @param string $clause
	 * @param string $tableName Table class name
	 */
	public function having($clause,$tableName = null) {
		if($tableName !== false) {
		 	$clause = $this -> parseTableName($tableName) . '.' . $clause;
		}
		$this -> select() -> having($clause);
	}

	/**
	 * Add limit clause
	 * 
	 * @param int $limit
	 * @param int $offset
	 * @return self
	 */
	public function limit($limit,$offset = null) {
		$this -> select() -> limit($limit,$offset);
		return $this;
	}
	
	/**
	 * Add limit clause by page
	 * 
	 * @param int $page
	 * @param int $perpage
	 */
	public function limitPage($page,$rowCount = 10) {
		$this -> _rowCount = $rowCount;
		$this -> _perPage = $rowCount;
		$this -> select() -> limitPage($page,$rowCount);
		return $this;
	}
	
	/**
	 * Count pages using a clone of internal select object
	 * - Invoked prior to query methods
	 * 
	 * @param Lionite_Db_Select $selectOjb
	 * @param string $countCol COUNT column
	 */
	protected function _countPages(Lionite_Db_Select $selectObj,$countCol = '*') {
		$countSelect = clone $selectObj;
		$countSelect -> prepareForCount('count',$countCol);
		$result = $this -> getAdapter() -> fetchRow($countSelect);
		$count = ((int)(($result['count'] - 1) / $this -> _perPage)) + 1;
		$this -> _pageCount = $count;
		$this -> _rowCount = $result['count'];
	}
	
	/**
	 * Get amount of items per result page
	 * @return int
	 */
	public function getPerpage() {
		return $this -> _perPage;
	}
	
	/**
	 * Return page count
	 * @return int
	 */
	public function getPageCount() {
		return $this -> _pageCount;
	}
	
	/**
	 * Return row count
	 * @return int
	 */
	public function getRowCount() {
		return $this -> _rowCount;
	}
	
	/**
	 * Parse table name
	 *
	 * Options for $tableNAme
	 * 	 - self::THIS OR null to return mapper table name
	 * 	 - self::LASTJOINED to return last joined table name
	 *   - Existing table class name to retrieve table name via class
	 *
	 * @param mixed $tableName
	 * @return string
	 */
	protected function parseTableName($tableName) {
		if(is_null($tableName) || $tableName === self::THIS){
			$table = $this -> _name;
		} else if($tableName === self::LASTJOINED){
			$table = $this -> getLastJoined();
		} else if(is_string($tableName)){
			$table = $this -> getTableName($tableName);
		}
		return $table;
	}

	/**
	 * Add reference map relationship
	 * @param array $map
	 */
	public function addMap(array $map) {
		$this -> _map = array_merge($this -> _map,$map);
	}
	
	/**
	 * Perform join by reference rule
	 * 
	 * Uses relationship map to perform joins.
	 * Rule parameter can be an array with a correlation name (alias) as the key
	 * 
	 * @param string / array $rule Relationship map rule
	 * @param array $cols Columns array
	 * @param string $type Join type
	 * @return self / boolean $this object on success, false on failure
	 */
	public function by($rule,$cols = array('*'),$type = 'join',$extraClause = null)
	{
		$ref = $this -> _parseRule($rule,self::THIS);
		if($this -> _joinByRule($ref,$cols,$type,$extraClause) === false){
			throw new Lionite_Db_Exception('Join on rule "' . $rule . "' failed");
		}
		return $this;
	}

	/**
	 * Perform join with reference rule
	 * 
	 * Same as by() with the exception the join is performed with the last joined table instead of the mapper table
	 * @see by() method
	 * 
	 * @param string / array $rule Relationship map rule
	 * @param array $cols Columns array
	 * @param string $type Join type
	 * @return self / boolean $this object on success, false on failure
	 */
	public function with($rule,$cols = array('*'),$type = 'join',$extraClause = null)
	{
		$ref = $this -> _parseRule($rule,self::LASTJOINED);
		if($this -> _joinByRule($ref,$cols,$type,$extraClause) === false){
			throw new Zend_Db_Exception('Join on rule "' . $rule . "' failed");
		}
		return $this;
	}
	/**
	 * Join by reference rule
	 *
	 * - Reference array produced by self::parseRule() method
	 *
	 * @param array $reference
	 * @param array $cols Columns array
	 * @param string $type Join type
	 * @return self / boolean $this object on success, false on failure
	 */
	protected function _joinByRule($reference,$cols = array('*'),$type = 'join',$extraClause = null) {
		$on = '';
		if(is_array($extraClause)){
			foreach($extraClause as $key => $val) {
				$on .= ' AND ' . key($reference['table']) . '.' . $key . "='" . $val . "'";
			}
		} else if(is_string($extraClause) && !empty($extraClause)) {
			$on .= ' AND (' . $extraClause . ')';
		}
		if(!in_array($type,$this -> _joinTypes)){
			throw new Zend_Db_Exception('Join type ' . $type . ' is not valid');
		}
		if($reference !== false){
			$this -> select() -> $type($reference['table'],$reference['cond'] . $on,$cols);
			$this -> _lastJoin = key($reference['table']);
			return $this;
		}
		return false;
	}
	
	/**
	 * Parse reference rule
	 *
	 * Returned reference array for method self::_joinByRule():
     * - table           = joined table name
     * - cond		     = ON join condition
     * 
	 * @param string / array $rule
	 * @param boolean $byPreviousParsing Perform join against mapper table OR previously joined table
	 * @return array / boolean
	 */
	protected function _parseRule($rule,$byPreviousParsing = false) {
		if(is_array($rule)){
			$correlation = key($rule);
			$rule = current($rule);
		} else {
			$correlation = null;
		}
		if(isset($this -> _map[$rule])){
			$rule = $this -> _map[$rule];
			$tableName = $this -> getTableName($rule['refTableClass']);
			if(is_null($correlation)){
				$correlation = $tableName;
			}
			if($byPreviousParsing === self::LASTJOINED && !is_null($this -> _lastJoin)){
				$matchTable = $this -> _lastJoin;
			} else {
				$matchTable = $this -> _name;
			}
			$ref = array(
				'table' => array($correlation => $tableName),
				'cond' => $correlation . '.' . $rule['refColumns'] . '=' . $matchTable . '.' . $rule['columns']
			);
			
			return $ref;
		}
		return false;
	}
	
	/**
	 * Get table name from table class name
	 *
	 * @param string $tableClass
	 * @return string
	 */
	public function getTableName($tableClass) {
		if(!isset($this -> _nameCache[$tableClass])){
			if(!class_exists($tableClass)){
				throw new Zend_Db_Exception('Table class ' . $tableClass . ' does not exist');
			}
			$table = new $tableClass();
			$this -> _nameCache[$tableClass] = $table -> getTableName();
			$rules = $table -> getMap();
			$this -> _map = array_merge($rules,$this -> _map);
		}
		return $this -> _nameCache[$tableClass];
	}
	
	/**
	 * Get last joined table name
	 * @return string
	 */
	public function getLastJoined() {
		return $this -> _lastJoin;
	}
	
	/**
	 * Converts internal select object to query string
	 */
	public function __toString() {
		return $this -> select() -> __toString();
	}
	
}