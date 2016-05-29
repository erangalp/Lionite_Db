<?php
require_once('TestSetup.php');

class Lionite_Db_SelectTest extends Lionite_Db_TestSetup {
	public function setUp()	{
		parent::setUp();
		$db = Zend_Db_Table::getDefaultAdapter();
		$this -> lioniteselect = new Lionite_Db_Select($db);
		$this -> zendselect = new Zend_Db_Select($db);
	}
	
	public function testPrepareForCount() {
		$this -> lioniteselect -> from('users',array('name','user'))
							   -> order("id DESC")
							   -> group('email')
							   -> limitPage(1,10)
							   -> prepareForCount();
		$this -> zendselect -> from('users',array('count' => 'COUNT(*)'));
		$this -> assertEquals($this -> lioniteselect -> __toString(),$this -> zendselect -> __toString());
	}

	/**
	 * Left joins with where clause are filtering and should not be removed
	 */
	public function testLeftjoinWithWhere() {
		$this -> lioniteselect -> from('users',array('name'))
			-> joinLeft('users_profile','users_profile.user_id=users.id',array('about'))
			-> where('users_profile.status=1')
			-> prepareForCount();
		$this -> zendselect -> from('users',array('count' => 'COUNT(*)'))
			-> joinLeft('users_profile','users_profile.user_id=users.id',array())
			-> where('users_profile.status=1');
		$this -> assertEquals($this -> lioniteselect -> __toString(),$this -> zendselect -> __toString());
	}

	/**
	 * Left join without where clause should be removed
	 */
	public function testLeftjoinWithoutWhere() {
		$this -> lioniteselect -> from('users',array('name'))
			-> joinLeft('users_profile','users_profile.user_id=users.id',array('about'))
			-> prepareForCount();
		$this -> zendselect -> from('users',array('count' => 'COUNT(*)'));
		$this -> assertEquals($this -> lioniteselect -> __toString(),$this -> zendselect -> __toString());
	}
}