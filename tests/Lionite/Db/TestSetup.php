<?php

class Lionite_Db_TestSetup extends PHPUnit_Framework_TestCase {
	public function setUp()
    {
    	parent::setUp();
        $this->_setUpAdapter();
        $this->_setUpStructure();
    }

    public function tearDown()
    {
        $this->_tearDownStructure();
        $this->_db = null;
    }

    protected function _setUpAdapter()
    {
    	$config = array(
    		'host' => 'localhost',
    		'username' => 'root',
    		'password' => '1234',
    		'dbname' => 'lionite_tests'
    	);
        $db = Zend_Db::factory('MySQLi',$config);
		$this -> _db = $db;
		
    }

    protected function _setUpStructure()
    {
	   Zend_Db_Table::setDefaultAdapter($this -> _db);
	   $this -> _cache = $this -> _getCache();
	   Zend_Db_Table::setDefaultMetadataCache($this -> _cache);
    }
	protected function _getCache()
    {
    	$path = dirname(__FILE__);
        $folder =  dirname(__FILE__) . '/tmp'; 
        if(!is_dir($folder)) {
        	mkdir($folder,0755);
        }
        $frontendOptions = array(
            'automatic_serialization' => true
        );
        $backendOptions  = array(
            'cache_dir'                 => $folder,
            'file_name_prefix'          => 'Tests_Db'
        );
        $cacheFrontend = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        $cacheFrontend->clean(Zend_Cache::CLEANING_MODE_ALL);
        return $cacheFrontend;
    }
    protected function _tearDownStructure()
    {
       $this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
       $this->_cache = null;
    }
}