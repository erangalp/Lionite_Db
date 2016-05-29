# Lionite_Db

Providing the missing link in the Zend Framework MVC structure, Lionite_Db_Model provide a strong base structure for database-based domain models. 

The Zend Framework MVC structure (ZF1) has very good implementations for the V & C parts (View and Controller), however the last piece, the Model, is missing on purpose and left for developers to implement themselves.

Building on the excellent Zend_Db abstraction classes, this component provides a strong base structure for implementing database-based domain models in the Zend Framework.

* Integrated user input validation and filtering using Zend_Filter_Input. This decouples the validation / filtering rules from specific forms, allowing re-use in many different scenarios.
* Extends Lionite_Db_Table relationships to allow abstraction of Join operations, creating more semantic and concise query methods
* Helper utilities for counting total rows and pages for complicated queries

# Usage Examples

## Model-based user input validation and filtering

The Lionite model class (Lionite_Db_Model) provides integrated interface for validating and filtering user input using Zend_Filter_Input. This decouples validation / filtering logic from specific forms and allows re-use in other non-form related scenario (such as HTTP requests from an API). Validation / filtering rules are declared in array properties in the model.

Example users class:

	class Users extends Lionite_Db_Model {
		protected $_name = 'users';
		protected $_primary = 'id';

		protected $_filters = array(
			'name' => 'StripTags',
			'about' => 'StripTags'
		);

		protected $_validators = array(
			'name' => array('NotEmpty','messages' => 'Please enter your full name'),
			'email' => array('EmailAddress',array('Db_NoRecordExists','content_pages','url'),
				'messages' => array(0 => 'Please enter a valid Email address',1 => 'Email is already in use')),
			'password' => array('NotEmpty', array('StringLength',4),'messages' => 'Please choose a password'),
			'about' => array('allowEmpty' => true,'presence' => 'optional')
		);
	}

The $_filters and $_validators arrays contain validation and filtering rules that are used by the insertion and update methods.

We can then apply validation and filtering as part of a form submission by calling the insertValid() or updateValid() methods of the model. Complete controller action example:

	public function registerAction() {
		if($this -> getRequest() -> isPost()) {
			$users = new Users();
			$result = $users -> insertValid($_POST);
			
			// Success returns lastInsertId
			if(is_numerc($result)) { 
				$this -> _redirect('/register/thanks'); // Redirect to 'Thank you' page
			
			//Failure returns an array of error messages, which we pass on to the view
			} else {
				$this -> view -> errors = $result;
			}
		}
	}

The insertValid() and updateValid() return a positive numeric (typically) on success and an array of error messages on failure. We can use the logic shown in the example to handle most form submissions. The view can render the actual messages if they are present.

Read more about validation and filtering in the model documentation.

## Advanced query composition using table relationships

The Lionite model gives utilizes the reference map used by Zend_Db_Table to improve dynamic query perparation. Lets review a query prepared using Zend_Db_Select and Lionite_Db_Model and compare it:

	$apps = new Apps();
	$mapper = $apps -> get(array('id','user_id','name','category_id','demourl','summary'))
		-> by('Category',array('category' => 'title'),'joinLeft')
		-> by('Creator',array('creator' => 'name'))
		-> with('Profile',array('description'))
		-> limitPage($page,$limit);

	$select = new Zend_Db_Select();
	$select -> from('apps',array('id','user_id','name','category_id','demourl','summary'))
		-> joinLeft('categories','categories.id=apps.category_id',array('category' => 'title'))
		-> join('users','users.id=apps.user_id',array('creator' => 'name'))
		-> join('users_profile','users.id=users_profile.user_id',array('description'))
		-> limitPage($page,$limit);

Those two statements create identical queries. The model mapper object (instanced by the get() method) uses the Zend_Db_Table relationships to abstract join statements, with the reference map keys used as names for the relationship. There are several advantages to this approach -

* Join operations are abstracted - you do no need to declare the join condition more than once. This is useful to protect from changes in the conditions or table names, which would otherwise require to change all parts in the code that reference it (as table names and conditions appear explicitly in the Zend_Db_Select join methods).
* The creation of a more readable syntax for query composition. The first example (using the Lionite model mapper) reads much more like natural language - get columns by category and by creator with profile.
* More compact statements - since you do not have to explicitly write the table names and columns participating in the join, the query methods you write are reduced in length. This is important when writing complex query methods, that can become somewhat difficult to read.

Read more about composing queries using table relationships in the mapper documentation.

## Lionite Database Paginator

The Lionite model / mapper package comes with a helper paginator class that is intended to work together with those classes but can also be used as a standalone. The paginator modifies a prepared select object and removes all the fetched columsn and non-filtering tables / clauses:

// We'll build the query from the previous example
$apps = new Apps();
$mapper = $apps -> get(array('id','user_id','name','category_id','demourl','summary'))
    -> by('Category',array('category' => 'title'),'joinLeft')
    -> by('Creator',array('creator' => 'name'))
    -> with('Profile',array('description'))
    -> limitPage($page,$limit);

// Get page count for the prepared query
$pageCount = $apps -> getPaginator() -> pages();
// Get total row count for the prepared query
$rowCount = $apps -> getPaginator() -> total();

// Actual COUNT() query ran
// SELECT COUNT(*) AS count FROM `apps` 

Using this approach we can avoid composing a second, similar query with complex filtering criteria just for counting. You can read more about the paginator class in the paginator documentation.

# Class Documentation

## Lionite_Db_Model class

extends Zend_Db_Table

Location in package:

/library/Lionite/Db/Model.php

The Lionite_Db_Model class is a base model class for use in Zend framework MVC applications. It offers validation and filtering of user data, alternative syntax for composing queries using table relationships (see mapper documentation) and helper methods.
Filtering and validation

Filtering and validation are necessary when dealing with user input, to prevent malicious or unintentional abuse of our database. The Zend Framework comes with an assortment of validators and filters that cover a wide range of data types.

The Lionite_Db_Model uses the Zend_Filter_Input component for validation and filtering. Usage of validators and filters is the same as with Zend_Form, only you pass validators and filters as an array instead of attaching it to individual form elements. In this way we decouple validation / filtering logic from specific form instances, allowing us to use it in many scenarios other than form handling (such as API calls, or model to model operations).

We define our validation and filtering rules in two properties in the model called $_validators and $_filters respectively. The following users class will be used as an example:

	class Users extends Lionite_Db_Model {
		protected $_name = 'users';
		protected $_primary = 'id';

		protected $_filters = array(
			'name' => 'StripTags',
			'about' => 'StripTags'
		);

		protected $_validators = array(
			'name' => array('NotEmpty','messages' => 'Please enter your full name'),
			'email' => array('EmailAddress',array('Db_NoRecordExists','users','email'),
				'messages' => array(0 => 'Please enter a valid Email address',1 => 'Email is already in use')),
			'password' => array('NotEmpty', array('StringLength',4),'messages' => 'Please choose a password'),
			'about' => array('allowEmpty' => true,'presence' => 'optional')
		);
	}

The rules defined in this examples cover very common attributes for a Users model.

* The 'name' field will not be empty
* The 'email' field be a valid Email address and it should not be registered in the database yet
* The 'password' field should not be empty and should be at least 4 chararcters long
* The 'about' field is optional and can be left empty 
* Both the 'name' and 'about' fields will be stripped of HTML tags

(You can see more use examples in the Zend_Filter_Input documentation)

We attached a custom message to each validator, that will be returned if one of the fields does not pass validation.
Inserting / updating data

After defining the validation / filtering rules in the model, we can use the insertValid() and updateValid() to insert and update data respectively in the model. Use example:

	$users = new Users();
	$result = $users -> insertValid($_POST);
	...
	$users = new Users();
	$result = $users -> updateValid($_POST,"id=" . (int) $id);

The insertValid() and updateValid() methods are similar in syntax to the Zend_Db_Table insert() and update() methods. Internally user input is validated and filtered before being passed on to the actual database methods.

Complete use example in a controller action:

	public function registerAction() {
		if($this -> getRequest() -> isPost()) {
			$users = new Users();
			$result = $users -> insertValid($_POST);
			
			// Success returns lastInsertId
			if(is_numerc($result)) { 
				$this -> _redirect('/register/thanks'); // Redirect to 'Thank you' page
				
			//Failure returns an array of error messages which we pass on to the view
			} else {
				$this -> view -> errors = $result;
			}
		}
	}

The insertValid() and updateValid() return a positive numeric on success (last insert ID for insertion and the number of affected rows for an update) and an array of error messages on failure. We can use the logic shown in the example to handle any kind of array request. The view can render the actual messages if they are present.
Hooking into insertValid() and updateValid()

In some cases, we want to run additional logic before inserting / updating data into the database. For this purpose we have two methods - add() and save() that we can extend without changing the behaviour of the insertValid() and updateValid() methods. Example registration in the Users model:

	class Users {
		protected $_name = 'users';
		protected $_primary = 'id';
		protected $_filters = array(...);
		protected $_validators = array(...);    

		public function add(array $data) {
			$data['hash'] = md5(uniqid());
			$result = $this -> insertValid($data);    
			if(is_numeric($result)) {
				$this -> _sendConfirmationMail($data);
			} 
			return $result;
		}
	}

We added some minor logic to the basic use of insertValid() - adding a unique hash to each user and in the case of successful registration, a confirmation mail will be sent to the user.
Deleting rows

Since the vast majority of row deletion operations are used by the primary key, the deleteValid() provides a shortcut for deleting rows by passing the value of that key for deletion. The value will be casted / quoted depending on its type.

	$id = 5;
	$users -> deleteValid($id);

Note that the deleteValid() method will invoke the delete() method of the Zend_Db_Table_Row class, which will activate any cascasding delete rules you might have in place.
Advanced usage

Sometimes we need to perform more advanced validation operations that might involve multiple models or complex logic. For this purpose we have direct access to the validation / filtering API.

	public function validate($data,$options) {...}

The validate() function activates the validation / filtering chain on the passed $data array. Additional options may be passed through the $options array (to see the full list of options review the options section of the Zend_Filter_Input docs).

The validate() method will return valid data on success (fields that don't have declared validation rules are discarded) and false on failure. In the case of failure, the error messages array can be retrieved via the getValidationErrors() method.

There are two preconfigured validation methods - isValidInsert() and isValidUpdate() - that are used internally by insertValid() and updateValid() and can be used to perform the validation for those methods without actually touching the database yet.

If you need to get the validators or filters array from the model to use in an advanced validation scheme that involves multiple models, you can use the getValidators() and getFilters() methods.
Helper methods

The Lionite model provides some shortcut and convenience methods to streamline use.

* The get() method returns an instance of Lionite_Db_Mapper and is used to start composing queries applies a get() method on it (see docs for more details)
* The mapper() method returns the mapper used by the current model
* The quote() method applies the adapter native method for quoting strings
* The countAll() method returns a count of all the rows in the table
* The getOne() method returns a single row by identifier
* The getTableName() method returns the name of table the model is using for storage
* The getMap() method returns the reference map used for table relationships

# Lionite_Db_Mapper class

Location in package:

/library/Lionite/Db/Mapper.php

The Lionite_Db_Mapper acts as a wrapper for Zend_Db_Select, providing additional capabilities and syntax for composing complex queries. It is meant to be used internally by Lionite_Db_Model and must be passed an instance of Lionite_Db_Model in the constructor.
Accessing the mapper object

The mapper object is usually instanced by a Lionite_Db_Model object which will automatically configure the mapper object. A mapper object can be created by two methods of the model - get() and mapper(). The difference between those two methods is that get() will also call a get() method on the created mapper object (see below), while mapper() just returns the currently set mapper instance.

	// Users model
	$users = new Users(); 

	// Instances a mapper object and runs a get method on it
	$mapper = $users -> get(); 

	// Instances a mapper object. If the mapper is already instanced, returns the active instance
	$mapper = $users -> mapper(); 

## Differences from Zend_Db_Select

### get() instead of from()

The get() method is similar to the from() method used by Zend_Db_Select, however there is no need to pass the table as the mapper has that information from the model that instanced it.

	$mapper = $users -> mapper();
	$select = new Zend_Db_Select();
	// The following statement have identical results
	$mapper -> get(array('id'));
	$select -> from('users',array('id'));

	// We could have skipped instancing the mapper explicitly and calling get() directly on the model
	$mapper = $users -> get(array('id'));

You can pass an array of columns to fetch, same as Zend_Db_Select (default is all columns).

### by() and with() instead of join() and its variations

	public function by($rule, $columns = array('*'), $joinType = 'join',$extraClause = null)
	public function with($rule, $columns = array('*'), $joinType = 'join',$extraClause = null)

The mapper uses table relationships defined in the model to construct join statements. Use the by() or with() methods and pass the relationship mapping rule to join:

	// Join users table with the users_profile table using the 'Profile' relationship rule  
	$mapper = $users -> get() -> by('Profile'); 
	// Can also use the with() method
	$mapper = $users -> get() -> with('Profile');

	// Those statements produce identical results to the following using Zend_Db_Select
	$select -> from('users') -> join('users_profile','users_profile.user_id=users.id');

	The 'Profile' relationship map would be of the following form:

	'Profile' => array(
		'columns' => 'id',
		'refTableClass' => 'Users_Profile',
		'refColumns' => 'user_id'
	)

(You can read more about table relationship maps used by Zend_Db_Table in the docs)

### Differences between by() and with()

Since we are not declaring the join condition explicitly, the mapper assumes it is against the table of the instancing model (in our example, we are joining 'users_profile' against the table 'users'). This is the behavior of the by() method.

We do not always want to join against the base table in the 'from' clause. For that purpose we use the with() method, which joins against the last joined table. For example:

	$mapper -> get() 
			-> by('Reservations') 
			-> with('Products');

	// Creating the same statement with Zend_Db_Select
	$select -> from('users')
				 -> join('reservations','reservations.user_id=users.id')
				 -> join('products','products.id=reservations.product_id');

Both methods accept the same parameters :

    $rule - relationship map rule
    $columns - An array of columns to fetch (default is all - '*')
    $joinType - The join type, should be the same as the Zend_Db_Select join methods. Acceptable values include 'join','joinLeft','joinRight','joinFull','joinCross','joinNatural'
    $extraClause - Additional join conditions. Can be either a string or an array of column => value pairs.

Example using all optional parameters:

	$mapper -> get() -> by('Profile',array('avatar','description'),'joinLeft',array('status' => 1));

	// Alternative extra clause syntax
	$mapper -> get() -> by('Profile',array('avatar','description'),'joinLeft','status=1'));

	// Both statements are equal to the following using Zend_Db_Select
	$select -> from('users') -> joinLeft('users_profile','users_profile.user_id=users.id AND users_profile.status=1', array('avatar','description'));

## Additional SELECT clauses

Most clauses available by Zend_Db_Select are available by Lionite_Db_Mapper, including where(), orWhere(), group(), order(), having(), limit(), limitPage(). The only difference is that, by default, the table name of the instancing model is given to the clause. For example:

	$mapper = $users -> get() -> where('id=1');
	// The following Zend_Db_Select statement is equal
	$select -> from('users') -> where('users.id=1');

The reason being that most real-world queries involve multiple table and as such the table name should be specified in the different clauses. If we want to use a different table for any clause, we pass the class name of the model that handles that table as a second parameter.

	$mapper = $users -> get() -> by('Profile') -> where('status=1','Users_Profile');
	// The following Zend_Db_Select statement is equal
	$select -> from('users') -> join('users_profile','users_profile.user_id=users.id') -> where('users_profile.id=1');

We can also prevent adding the table name to the clause by passing 'false' in the second parameter.

	$mapper = $users -> get() -> where("name='john'",false); 

The only exceptions are limit() and limitPage() which do not require specifying table name.
Fetching rows

When query composition is complete, we can execute by calling the query() method:

	$users = new Users();
	$rows = $users -> get() -> by('Profile') -> query();
	// The following Zend_Db statements are equal
	$select = new Zend_Db_Select();
	$select -> from('users') -> join('users_profile','users_profile.user_id=users.id');
	// $db is an instanced database adapter
	$rows = $db -> fetchAll($select);

We can also get only one row by calling queryOne() instead of query().

	$users = new Users();
	$row = $users -> get() -> where('id=1') -> queryOne();
	// The following Zend_Db statements are equal
	$select = new Zend_Db_Select();
	$select -> from('users') -> where('id=1');
	// $db is an instanced database adapter
	$row = $db -> fetchRow($select);

Converting mapper to string

The mapper can be cast to a string to see the actual SELECT statement generated. This is useful for debugging purposes:

	$mapper = $users -> get() -> where('id=1');
	echo $mapper;
	// Prints 'SELECT * FROM `users` WHERE `id`=1

## Advanced usage

### Modifying internal select object

Not every edge case is covered by the mapper API. In those cases, we can access the internal Zend_Db_Select object and modify it directly with the full Zend_Db_Select API.

	$mapper = $users -> get() -> by('Profile');
	// Get internal Zend_Db_Select object
	$select = $mapper -> select();

A common scenario when this is necessary is to perform subqueries.

	$mapper = $users -> get(array('name'));
	// We want to add a subquery, which is not possible using the mapper API
	$mapper -> select() -> joinLeft(array('revenue' => 
		new Zend_Db_Expr('(SELECT SUM(amount) AS amount,user_id FROM reservations GROUP BY user_id)')),
		'revenue.user_id=users.id'
	);

### Resetting clauses

If we want to reset a clause we set previously, we can use the reset() method to do so:

	$ids = array(4,5,24);
	$mapper = $users -> get(array('name'));
	// Composing queries in a loop, we want to reset some clauses for each iteration
	foreach($ids as $id) {
		$mapper -> reset('where');
		$mapper -> where('id=' . $id);
	}
	
# Lionite_Db_Paginator class

Location in package:

/library/Lionite/Db/Paginator.php

The Lionite_Db_Paginagor is a helper class that works in tandem with Lionite_Db_Model to calculate the number of pages or total row count using a recently prepared query. We instance the paginator by calling the getPaginator() method on the model, and then use one of the counting methods - pages() and total():

	$rows = $users -> get() -> where('name LIKE ' . $users -> quote('%' . $name . '%') ) -> limitPage(1,15);
	$paginator = $users -> getPaginator();
	$pageCount = $paginator -> pages(); 
	$rowCount = $paginator -> total();
	// COUNT() query ran - SELECT COUNT(*) AS count FROM `users` WHERE name LIKE '%{$name}%' 

Paginator counting is especially useful for complex queries that will take some effort to turn into COUNT() queries:

	$mapper = $users -> get(array('id','name')) 
		 -> by('Reservations',array('created','amount'),'joinLeft')
		 -> with('Product',array('product_name' => 'name'),'joinLeft')
		 -> limitPage(1,10);
	$paginator = $users -> getPaginator();
	$pageCount = $paginator -> pages(); 
	$rowCount = $paginator -> total();
	// Left Joins without filtering WHERE criteria are discarded
	// COUNT() query ran - SELECT COUNT(*) AS count FROM `users` 

If the limitPage() clause is invoked on the mapper, the value of $perPage (second parameter) will be used to calculate the number of pages. It can also be set manually by calling the setPerpage() method on the paginator:

	$mapper = $users -> get(array('id','name')) 
		 -> by('Reservations',array('created','amount'),'joinLeft')
		 -> with('Product',array('product_name' => 'name'),'joinLeft')
		 -> limit(5);
	$paginator = $users -> getPaginator();
	// limitPage() was not called, so we set the item count per page manually
	$paginator -> setPerpage(10);
	$pageCount = $paginator -> pages(); 

## Edge cases in complex queries

The paginator tries to prepare a COUNT() query automatically by removing clauses and tables that do not affect or distort the row count. When using it with very complex queries, the count queries should be tested to make sure they are counting correctly as the automatic process might not work for all edge cases.