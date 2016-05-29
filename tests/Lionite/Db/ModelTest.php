<?php

require_once('TestSetup.php');
require_once('MockModels/Users.php');
/**
 * Lionite_Db_Model Test case
 *
 */
class Lionite_Db_ModelTest extends Lionite_Db_TestSetup 
{
	protected $_model;
	protected $_name;
	public function setUp()
	{
		parent::setUp();
		$this -> _model = new MockUsers();
		$this -> _name = 'users';
	}
	protected function getInsertData()
	{
		$data = array(
			'name' => 'John Doe',
			'email' => 'john@hotmail.com',
			'password' => 'mypass'
		);
		return $data;
	}
	public function tearDown()
	{
		unset($this -> _model);
	}
	public function testGetTableName()
	{
		$name = $this -> _model -> getTableName();
		$this -> assertEquals($name,$this -> _name);
	}
	public function testMapper()
	{
		$mapper = $this -> _model -> mapper();
		$this -> assertTrue($mapper instanceof Lionite_Db_Mapper);
	}
	public function testGet()
	{
		$mapper = $this -> _model -> get();
		$this -> assertType('Lionite_Db_Mapper',$mapper);
	}
	public function testValidate()
	{
		$data = $this -> getInsertData();
		$valid = $this -> _model -> validate($data);
		$this -> assertTrue(is_array($valid));
	}
	
	public function testValidateFailure()
	{
		$data = $this -> getInsertData();
		$data['name'] = null;
		$valid = $this -> _model -> validate($data);
		$this -> assertFalse($valid); 
	}
	
	public function testValidInsert()
	{
		$data = $this -> getInsertData();
		$valid = $this -> _model -> isValidInsert($data);
		$this -> assertTrue(is_array($valid));
	}
	
	public function testInvalidInsert()
	{
		$data = $this -> getInsertData();
		unset($data['password']);
		$valid = $this -> _model -> isValidInsert($data);
		$this -> assertFalse($valid);
	}
	
	public function testValidUpdate()
	{
		$data = $this -> getInsertData();
		$valid = $this -> _model -> isValidUpdate($data);
		$this -> assertTrue(is_array($valid));
	}
	
	public function testInvalidUpdate()
	{
		$data = $this -> getInsertData();
		$data['email'] = '1234'; 
		$valid = $this -> _model -> isValidUpdate($data);
		$this -> assertFalse($valid);
	}
	
	public function testIsValidIdInt()
	{
		$id = 1;
		$this -> assertTrue($this -> _model -> isValidId($id));
	}
	
	public function testIsValidIdNumeric()
	{
		$id = '1';
		$this -> assertTrue($this -> _model -> isValidId($id));
	}
	
	public function testNotValidIdString()
	{
		$id ='1$';
		$this -> assertFalse($this -> _model -> isValidId($id));
	}
	public function testGetValidators() {
		$validators = $this -> _model ->getValidators();
		$this -> assertType('array',$validators);
	}

	public function testGetFilters() {
		$filters = $this -> _model -> getFilters();
		$this -> assertType('array',$filters);
	}
}