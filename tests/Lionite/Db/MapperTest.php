<?php

require_once('TestSetup.php');
require_once('MockModels/Users.php');
/**
 * Lionite_Db_mapper Test case
 *
 */
class Lionite_Db_MapperTest extends Lionite_Db_TestSetup
{
	protected $rules;
	protected $mapper;
	protected $_name = 'conversations';
	/**
	 * Set up basic reference map and a reusable mapper object
	 *
	 */
	public function setUp()
	{
		parent::setUp();
		$this -> rules = array(
			'Users' => array(
	            'columns'           => 'user_id',
	            'refTableClass'     => 'MockUsers',
	            'refColumns'        => 'id'
	       	)
		);
		$this -> mapper = new Lionite_Db_Mapper(array('map' => $this -> rules,'table' => $this -> _name));
		$db = $this -> getMock('Zend_Db_Adapter_Mysqli',array('__construct','fetchOne','_connect'),array(0 => array('dbname' => 'bla','username' => 'root','password' => '1234')));
		Zend_Db_Table::setDefaultAdapter($db);
	}
    /**
     * Integration test
     *
     * Compose complicated query
     */
    public function testQueryComposition()
    {
    	$this -> mapper -> get()
    					-> by('Users',array('id','user_id'))
						-> with(array('us' => 'Users'),array('name'))
						-> where(array('user_id' => 1))
						-> group('id')
						-> limitPage(1,10)
						-> order('created DESC','MockUsers');
			
    	
		$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
		$select -> from($this -> _name,array('*')) 
			-> join('users','users.id=' . $this -> _name .'.user_id',array('id','user_id'))
			-> join(array('us' => 'users'),'us.id=users.user_id',array('name'))
			-> group($this -> _name . '.id')
			-> limitPage(1,10)
			-> order('users.created DESC')
			-> where($this -> _name . ".user_id=1");
    	$this -> assertEquals((string)$select,(string)$this -> mapper -> select());
 
    }
    public function testBy()
    {
    	$mapper = $this -> mapper -> by('Users',array('id','user_id'));
    	$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
    	$select -> join('users','users.id=' . $this -> _name .'.user_id',array('id','user_id'));
    	$this -> assertEquals((string)$select,(string)$this -> mapper -> select());
    }
    public function testWith()
    {
    	$mapper = $this -> mapper -> with('Users',array('name'));
    	$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
    	$select -> join('users','users.id=conversation_posts.user_id',array('name'));
    	$this -> assertEquals((string)$select,(string)$this -> mapper -> select());
    }
    public function testWhere()
    {
    	$mapper = $this -> mapper -> where('utf.user_id=1');
    	$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
    	$select -> where('utf.user_id=1');
    	$this -> assertEquals($select -> __toString(),$this -> mapper -> select() -> __toString());
    }
    public function testGroup()
    {
    	$mapper = $this -> mapper -> group('users.id');
    	$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
    	$select -> group('users.id');
    	$this -> assertEquals($select -> __toString(),$this -> mapper -> select() -> __toString());
    }
    
    public function testOrder()
    {
    	$mapper = $this -> mapper -> order('created DESC');
    	$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
    	$select -> order($this -> _name . '.created DESC');
    	$this -> assertEquals($select -> __toString(),$this -> mapper -> select() -> __toString());
    }
    public function testGet()
    {
    	$this -> mapper -> get();
    	$select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
    	$select -> from($this -> _name,array('*'));
    	$this -> assertEquals((string)$select,$this -> mapper -> select() -> __toString());
    }
   
}