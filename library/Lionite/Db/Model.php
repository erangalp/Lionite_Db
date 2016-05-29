<?php
/**
 * Lionite Database Model Class
 * 
 * Extends Zend_Db_Table with input validation / filtering and improved relationship-based query composition
 * 
 * @category   Lionite
 * @package    Lionite_Db
 * @author     Eran Galperin
 * @copyright  Eran Galperin All rights reserved
 */
class Lionite_Db_Model extends Zend_Db_Table {
	/**
	 * Filters array for insert/update methods
	 * @var array
	 */
	protected $_filters = null;
	
	/**
	 * Validators array for insert/update methods
	 * @var array
	 */
	protected $_validators = null;
	
	/**
	 * Ignored fields in update method
	 * @var array
	 */
	protected $_updateExclude = null;
	
	/**
	 * Validation errors
	 * @var array
	 */
	protected $_errors = null;

	/**
	 * Lionite_Db_Mapper object
	 * @var Lionite_Db_Mapper
	 */
	protected $_mapper = null;
	
	/**
	 * Lionite_Db_Mapper class
	 * @var string
	 */
	protected $_mapperClass = 'Lionite_Db_Mapper';
	
	/**
	 * Lionite_Db_Rowset class
	 * @var string
	 */
	protected $_rowsetClass = 'Lionite_Db_Rowset';
		
	/**
	 * Constructor
	 *
	 * @param array $config
	 */
	public function __construct($config = array()) {
		parent::__construct($config);
	}
	
	/**
	 * Returns model table name
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this -> _name;
	}
	
	/**
	 * Returns model reference map
	 * @return array
	 */
	public function getMap() {
		return is_array($this -> _referenceMap) ? $this -> _referenceMap : array();
	}
	
	/**
	 * Initializes and returns Lionite_Db_Mapper object
	 * @return Lionite_Db_Mapper
	 */
	public function mapper() {
		if(is_null($this -> _mapper)){
			$config = array(
				'db' => $this -> getAdapter(),
				'table' => $this -> _name,
				'map' => $this -> _referenceMap
			);
			if(!class_exists($this -> _mapperClass)){
				throw new Lionite_Db_Exception('mapper class "' . $this -> _mapperClass . "' does not exists");
			} else {
				$this -> _mapper = new $this -> _mapperClass($config);
			}
		}
		return $this -> _mapper;
	}

	/**
	 * Initialize and return mapper object
	 *
	 * @param array $cols
	 * @param string / array $where
	 * @return Lionite_Db_mapper
	 */
	public function get($cols = array('*'),$where = null) {
		return $this -> mapper() -> get($cols,$where);
	}
	
	/**
	 * Getter for a row paginator
	 * - Instanced with internal select object
	 * 
	 * @return Lionite_Db_Paginator
	 */
	public function getPaginator() {
		$paginator = new Lionite_Db_Paginator($this -> mapper() -> select());
		$paginator -> setPerpage($this -> mapper() -> getPerpage());
		return $paginator;
	}
	
	/**
	 * Get model validators
	 * @return array
	 */
	public function getValidators() {
		return $this -> _validators;
	}

	/**
	 * Get model filters
	 * @return array
	 */
	public function getFilters() {
		return $this -> _filters;
	}
	
	/**
	 * Validates and filters data array
	 *
	 * @param array $data 
	 * @param array $options Zend_Filter_Input options
	 * @return boolean / array Filtered data
	 */
	public function validate(array $data,$options = null) {	
		
		$input = new Zend_Filter_Input($this -> _filters,$this -> _validators,$data,$options);
		
		if (! $input->isValid() ){
			$this -> _errors = $input -> getMessages();
		} else {
			return $input -> getUnescaped();
		} 
		return false;
	}
	
	/**
	 * Validates data for insert operation
	 *
	 * Unless otherwise specified, all fields are considered required (indicate optional using 'presence' => 'optional')
	 * 
	 * @param array $data
	 * @return boolean Valid data
	 */
	public function isValidInsert($data) {
		$options = array('presence' => 'required','missingMessage' => "Please fill the required field '%field%'");
		return $this -> validate($data,$options);
	}
	
	/**
	 * Validates data for update operation
	 * - Update may include partial data - allowing unrequired fields
	 * 
	 * @param array $data
	 * @return boolean Valid data
	 */
	public function isValidUpdate($data) { 
		$options = array('presence' => 'optional');
		return $this -> validate($data,$options);
	}
	
	/**
	 * Validates database integer id
	 * - Numerical values larger than zero are considered valid
	 * 
	 * @param mixed $id
	 * @return boolean Valid
	 */
	public function isValidId($id) {
		return (is_numeric($id) && $id > 0) ? true : false;
	}
	
	/**
	 * Database insertion and validation method
	 * - Checks data validaty via the validate() method. 
	 * - Returns lastInsertId or an array of error messages.
	 * 
	 * @param array $data User input
	 * @return mixed Insertion identifier / Error messages array
	 */
	public function insertValid(array $data) {
		$data = $this -> isValidInsert($data);
		if($data !== false){
			$id = parent::insert($data);
			if(is_array($id)){
				$id = (array_key_exists('id',$id) ? $id['id'] : current($id));
			}
			return $id;
		} 
		return $this -> _errors;
	}

	/**
	 * Extension hook to add logic to insertValid() without overwrite
	 *
	 * @param array $data
	 * @return mixed @see insertValid()
	 */
	public function add(array $data) {
		return $this -> insertValid($data);
	}
	
	/**
	 * Database valid update method
	 * - Checks data validaty via the validate() method. 
	 * - Returns number of rows updated or an array of error messages.
	 *
	 * @param array $data User input
	 * @param array $where
	 * @return mixed Number of rows [int] / Error messages [array]
	 */
	public function updateValid(array $data,$where) {
		$data = $this -> isValidUpdate($data);
		if($data !== false && !empty($where)){
			if(is_array($where)) {
				$w = array();
				foreach($where as $key => $val){
					$w[] = $this -> getAdapter() -> quoteInto($key . '=?',$val);
				}
				$where = implode(' AND ',$w);
			}
			return parent::update($data,$where);
		} 
		return $this -> _errors;
	}
	
	/**
	 * Extension hook to add logic to updateValid() without overwrite
	 * @param array $data
	 * @param int / string $identifier Primary key value
	 * @return mixed @see updateValid()
	 */
	public function save(array $data,$identifier) {
		$identifier = is_numeric($identifier) ? (int) $identifier : $this -> quote($identifier);
		return $this -> updateValid($data,$this -> _primary . '=' . $identifier);
	}
	
	/**
	 * Database delete method
	 *
	 * Deletes rows by unique id. Casts or quotes primary key value depending on type.
	 * Performs delete operation by activating the Zend_Db_Row delete method to enforce cascading delete operations
	 * 
	 * @param mixed $id Id of row for deletion
	 * @return mixed Number of rows deleted [int] / Boolean false
	 */
	public function deleteValid($primary) {	
		if(!empty($primary)){
			if(is_array($this -> _primary)){
				$primaryKey = current($this -> _primary);
			} else {
				$primaryKey = $this -> _primary;
			}
			$primary = is_numeric($primary) ? (float) $primary : $this -> quote($primary);
			$row = $this -> fetchRow($primaryKey . "=" . $primary);
			
			if(!is_null($row)){
				return $row -> delete();
			} 
		} 
		return false;
		
	}

	/**
	 * Extension hook to add logic to deleteValid() without overwrite
	 * @param int $identifier
	 * @return mixed
	 */
	public function remove($identifier) {
		return $this -> deleteValid($identifier);
	}

	/**
	 * Return validation result errors
	 * @return array
	 */
	public function getValidationErrors() {
		return (array)$this -> _errors;
	}
		
	/**
	 * Quote a variable using vendor specific methods
	 *
	 * @param string $value
	 * @return string
	 */
	public function quote($value) {
		return $this -> getAdapter() -> quote($value);
	}

	/**
	 * Simple count method
	 * @param string $where
	 * @return int
	 */
	public function countAll($where = null) {
		$mapper = $this -> get(array('count' => 'COUNT(*)')); 
		if(!is_null($where)) {
			$mapper -> where($where);
		}
		$row = $mapper -> queryOne();
		return $row['count'];
	}

	/**
	 * Get one row by identifier
	 * @param mixed $identifier
	 * @return array
	 */
	public function getOne($identifier) {
		$identifier = is_numeric($identifier) ? ((int) $identifier) : $this -> quote($identifier);
		return $this -> get() -> where($this -> _primary . '=' . $identifier) -> queryOne();
	}
}