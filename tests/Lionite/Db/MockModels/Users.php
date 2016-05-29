<?php
class MockUsers extends Lionite_Db_Model {
	protected $_name = 'users';
	protected $_primary = 'id';
	
	protected $_validators = array(
		'name' => 'NotEmpty',
		'password' => 'NotEmpty',
		'email' => 'EmailAddress'
	);

	protected $_filters = array(
		'name' => 'StripTags'
	);
}