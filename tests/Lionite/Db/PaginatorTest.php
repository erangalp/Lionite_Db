<?php
require_once('TestSetup.php');

class Lionite_Db_PaginatorTest extends Lionite_Db_TestSetup {
	public function setUp()	{
		parent::setUp();
		
		$db = $this -> getMock('Zend_Db_Adapter_Mysqli',array('__construct','fetchOne'),array(0 => array('dbname' => 'bla','username' => 'root','password' => '1234')));
		$this -> db = $db;
		
		$select = new Lionite_Db_Select($this -> db);
		$this -> paginator = new Lionite_Db_Paginator($select);
	}
	
	public function testPages() {
		$this -> db ->expects($this->once())
             ->method('fetchOne')
             ->will($this->returnValue(12));
		$pages = $this -> paginator -> pages();
		$this -> assertEquals($pages,2);
	}
	
	public function testTotal() {
		$this -> db ->expects($this->once())
             ->method('fetchOne')
             ->will($this->returnValue(37));
		$total = $this -> paginator -> total();
		$this -> assertEquals($total,37);
	}
	
	public function testSetPerpage() {
		$this -> paginator -> setPerpage(30);
		$this -> db ->expects($this->once())
             ->method('fetchOne')
             ->will($this->returnValue(59));
		$pages = $this -> paginator -> pages();
		$this -> assertEquals($pages,2);
	}
}