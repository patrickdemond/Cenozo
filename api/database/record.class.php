<?php
/**
 * record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\database;
use cenozo\lib, cenozo\log;

/**
 * record: abstract database table object
 *
 * The record class represents tables in the database.  Each table has its own class which
 * extends this class.  Furthermore, each table must have a single 'id' column as its primary key.
 */
abstract class record extends \cenozo\base_object
{
  const OBJECT_FORMAT = 0;
  const ARRAY_FORMAT = 1;
  const ID_FORMAT = 2;

  /**
   * Constructor
   * 
   * The constructor either creates a new object which can then be insert into the database by
   * calling the {@link save} method, or, if an primary key is provided then the row with the
   * requested primary id will be loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $id The primary key for this object.
   * @throws exception\runtime
   * @access public
   */
  public function __construct( $id = NULL )
  {
    $util_class_name = lib::get_class_name( 'util' );

    // now loop through all tables and fill in the default values
    foreach( $this->get_working_table_list() as $table )
    {
      // determine the columns for this table
      $columns = static::db()->get_column_names( $table['name'] );

      if( !is_array( $columns ) || 0 == count( $columns ) )
        throw lib::create( 'exception\runtime', sprintf(
          'No column names returned for table "%s"', $table['name'] ),
          __METHOD__ );

      // set the default value for all columns
      foreach( $columns as $name )
      {
        // If the default is CURRENT_TIMESTAMP, or if there is a DATETIME column by the name
        // 'start_datetime' then make the default the current date and time.
        // Because mysql does not allow setting the default value for a DATETIME column to be
        // NOW() we need to set the default here manually
        $default = static::db()->get_column_default( $table['name'], $name );
        if( 'start_datetime' == $name ||
            ( 'CURRENT_TIMESTAMP' == $default && 'datetime' == $name ) )
        {
          $date_obj = $util_class_name::get_datetime_object();
          $table['columns'][$name] = $date_obj->format( 'Y-m-d H:i:s' );
        }
        else
        {
          $table['columns'][$name] = $default;
        }
      }
    }

    if( NULL != $id )
    {
      // make sure this table has an id column as the primary key
      $primary_key_names = static::db()->get_primary_key( static::get_table_name() );
      if( 0 == count( $primary_key_names ) )
      {
        throw lib::create( 'exception\runtime',
          'Unable to create record, single-column primary key "'.
          static::get_primary_key_name().'" does not exist.', __METHOD__ );
      }
      else if( 1 < count( $primary_key_names ) )
      {
        throw lib::create( 'exception\runtime',
          'Unable to create record, multiple primary keys found (there may be tables in the '.
          'application and framework with the same name).', __METHOD__ );
      }
      else if( static::get_primary_key_name() != $primary_key_names[0] )
      {
        throw lib::create( 'exception\runtime',
          'Unable to create record, the table\'s primary key name, "'.
          $primary_key_names[0].'", does not match the class\' primary key name, "'.
          static::get_primary_key_name().'".', __METHOD__ );
      }

      $this->column_values[static::get_primary_key_name()] = intval( $id );
    }

    // now load the data from the database
    // (this gets skipped if a primary key has not been set)
    $this->load();
  }

  /**
   * Loads the record from the database.
   * 
   * If this is a new record then this method does nothing, if the record's primary key is set then
   * the data from the corresponding row is loaded.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function load()
  {
    $util_class_name = lib::get_class_name( 'util' );
    $database_class_name = lib::get_class_name( 'database\database' );

    if( isset( $this->column_values[static::get_primary_key_name()] ) )
    {
      $primary_key_value = $this->column_values[static::get_primary_key_name()];

      foreach( $this->get_working_table_list() as $table )
      {
        // not using a modifier here is ok since we're forcing id to be an integer
        $sql = sprintf( 'SELECT * FROM %s WHERE %s = %d',
                        $table['name'],
                        $table['key'],
                        $primary_key_value );

        $row = static::db()->get_row( $sql );

        if( 0 == count( $row ) )
        {
          if( static::get_table_name() == $table['name'] )
          {
            throw lib::create( 'exception\runtime',
              sprintf( 'Load failed to find record for %s with %s = %d.',
                       $table['name'],
                       $table['key'],
                       $primary_key_value ),
              __METHOD__ );
          }
          else // extending tables need their foreign key set if row is missing
          {
            $table['columns'][static::get_table_name().'_id'] =
              $this->column_values[static::get_primary_key_name()];
          }
        }
        else
        {
          // convert any date, time or datetime columns
          foreach( $row as $key => $val )
          {
            if( array_key_exists( $key, $table['columns'] ) )
            {
              if( $database_class_name::is_time_column( $key ) )
                $table['columns'][$key] = $util_class_name::from_server_datetime( $val, 'H:i:s' );
              else if( $database_class_name::is_datetime_column( $key ) )
                $table['columns'][$key] = $util_class_name::from_server_datetime( $val );
              else $table['columns'][$key] = $val;
            }
          }
        }
      }
    }
  }

  /**
   * Saves the record to the database.
   * 
   * If this is a new record then a new row will be inserted, if not then the row with the
   * corresponding id will be updated.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function save()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to save read-only record.' );
      return;
    }

    $util_class_name = lib::get_class_name( 'util' );
    $database_class_name = lib::get_class_name( 'database\database' );
    
    $primary_key_value = $this->column_values[static::get_primary_key_name()];

    foreach( $this->get_working_table_list() as $table )
    {
      // if we have start and end time or datetime columns (which can't be null), make sure the end
      // time comes after start time
      if( static::db()->column_exists( $table['name'], 'start_time' ) &&
          static::db()->column_exists( $table['name'], 'end_time' ) &&
          !is_null( static::db()->get_column_default( $table['name'], 'end_time' ) ) )
      {
        $start_obj = $util_class_name::get_datetime_object( $table['columns']['start_time'] );
        $end_obj = $util_class_name::get_datetime_object( $table['columns']['end_time'] );
        $interval = $start_obj->diff( $end_obj );
        if( 0 != $interval->invert ||
          ( 0 == $interval->days && 0 == $interval->h && 0 == $interval->i && 0 == $interval->s ) )
        {
          throw lib::create( 'exception\runtime',
            'Tried to set end time which is not after the start time.', __METHOD__ );
        }
      }
      else if(
        static::db()->column_exists( $table['name'], 'start_datetime' ) &&
        static::db()->column_exists( $table['name'], 'end_datetime' ) &&
        !is_null( static::db()->get_column_default( $table['name'], 'end_datetime' ) ) )
      {
        $start_obj = $util_class_name::get_datetime_object( $table['columns']['start_datetime'] );
        $end_obj = $util_class_name::get_datetime_object( $table['columns']['end_datetime'] );
        $interval = $start_obj->diff( $end_obj );
        if( 0 != $interval->invert ||
          ( 0 == $interval->days && 0 == $interval->h && 0 == $interval->i && 0 == $interval->s ) )
        {
          throw lib::create( 'exception\runtime',
            'Tried to set end datetime which is not after the start datetime.', __METHOD__ );
        }
      }

      // building the SET list since it is identical for inserts and updates
      $sets = '';
      $first = true;
      
      if( $this->include_timestamps && static::get_table_name() == $table['name'] )
      {
        // add the create_timestamp column if this is a new record
        if( is_null( $table['columns'][$table['key']] ) )
        {
          $sets .= 'create_timestamp = NULL';
          $first = false;
        }
      }

      // now add the rest of the columns
      foreach( $table['columns'] as $key => $val )
      {
        if( static::get_table_name() != $table['name'] || $table['key'] != $key )
        {
          // convert any time or datetime columns
          if( $database_class_name::is_time_column( $key ) )
            $val = $util_class_name::to_server_datetime( $val, 'H:i:s' );
          else if( $database_class_name::is_datetime_column( $key ) )
            $val = $util_class_name::to_server_datetime( $val );
          
          $sets .= sprintf( '%s %s = %s',
                            $first ? '' : ',',
                            $key,
                            static::db()->format_string( $val ) );

          $first = false;
        }
      }
      
      // either insert or update the row based on whether the primary key is set
      if( static::get_table_name() == $table['name'] )
      {
        $sql = sprintf(
          is_null( $primary_key_value ) ?
          'INSERT INTO %s SET %s' :
          'UPDATE %s SET %s WHERE %s = %d',
          $table['name'],
          $sets,
          $table['key'],
          $primary_key_value );
      }
      else // extending table row may not exist yet
      {
        $sql = sprintf( 'INSERT INTO %s SET %s ON DUPLICATE KEY UPDATE %s',
                        $table['name'],
                        $sets,
                        $sets );
      }

      static::db()->execute( $sql );
      
      // get the new primary key
      if( $table['name'] == static::get_table_name() &&
          is_null( $table['columns'][$table['key']] ) )
      {
        $primary_key_value = static::db()->insert_id();
        $table['columns'][$table['key']] = $primary_key_value;
      }
    }
  }
  
  /**
   * Deletes the record.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function delete()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to delete read-only record.' );
      return;
    }

    // check the primary key value
    if( is_null( $this->column_values[static::get_primary_key_name()] ) )
    {
      log::warning( 'Tried to delete record with no id.' );
      return;
    }
    
    $primary_key_value = $this->column_values[static::get_primary_key_name()];

    // loop through the working tables in reverse order (to avoid reference problems)
    foreach( array_reverse( $this->get_working_table_list() ) as $table )
    {
      // not using a modifier here is ok since we're forcing id to be an integer
      $sql = sprintf( 'DELETE FROM %s WHERE %s = %d',
                      $table['name'],
                      $table['key'],
                      $primary_key_value );
      static::db()->execute( $sql );
    }
  }

  /**
   * Magic get method.
   *
   * Magic get method which returns the column value from the record's table or any extending
   * tables.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column or table being fetched from the database
   * @return mixed
   * @throws exception\argument
   * @access public
   */
  public function __get( $column_name )
  {
    // see if the column name starts with an extending table's name
    foreach( self::get_extending_table_list() as $table )
    {
      $len = strlen( $table ) + 1;
      $extending_prefix = substr( $column_name, 0, $len );
      $extending_column = substr( $column_name, $len );
      if( $table.'_' == $extending_prefix &&
          array_key_exists( $extending_column, $this->extending_column_values[$table] ) )
      {
        return isset( $this->extending_column_values[$table][$extending_column] ) ?
          $this->extending_column_values[$table][$extending_column] : NULL;
      }
    }

    // not an column from the extending table list, make sure the column exists in the main table
    if( !static::column_exists( $column_name ) )
      throw lib::create( 'exception\argument', 'column_name', $column_name, __METHOD__ );
    
    return isset( $this->column_values[$column_name] ) ?
      $this->column_values[$column_name] : NULL;
  }

  /**
   * Magic set method.
   *
   * Magic set method which sets the column value to a record's table or any extending tables.
   * For this change to be writen to the database see the {@link save} method
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column
   * @param mixed $value The value to set the contents of a column to
   * @throws exception\argument
   * @access public
   */
  public function __set( $column_name, $value )
  {
    // see if the column name starts with an extending table's name
    foreach( self::get_extending_table_list() as $table )
    {
      $len = strlen( $table ) + 1;
      $extending_prefix = substr( $column_name, 0, $len );
      $extending_column = substr( $column_name, $len );
      if( $table.'_' == $extending_prefix &&
          array_key_exists( $extending_column, $this->extending_column_values[$table] ) )
      {
        $this->extending_column_values[$table][$extending_column] = $value;
        return;
      }
    }

    // not an column from the extending table list, make sure the column exists in the main table
    // make sure the column exists
    if( !static::column_exists( $column_name ) )
      throw lib::create( 'exception\argument', 'column_name', $column_name, __METHOD__ );
    
    $this->column_values[$column_name] = $value;
  }

  /**
   * TODO: document
   */
  public function get_column_values()
  {
    return $this->column_values;
  }
  
  /**
   * Magic call method.
   * 
   * Magic call method which allows for several methods which get information about records in
   * tables linked to by this table by either a foreign key or joining table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the called function (should be get_<record>,
   *                     get_<record>_count() or get_<record>_list(), where <record> is the name
   *                     of an record class related to this record.
   * @param array $args The arguments passed to the called function.  This can either be null or
   *                    a modifier to be applied to the magic methods.
   * @throws exception\runtime, exception\argument
   * @return mixed
   * @access public
   * @method array get_<record>() Returns the record with foreign keys referencing the <record>
   *               table.  For instance, if a record has a foreign key "other_id", then
   *               get_other() will return the "other" record with the id equal to other_id.
   * @method array get_record_list() Returns an array of records from the joining <record> table
   *               given the provided modifier.  If a record has a joining "has" table then
   *               calling get_other_list() will return an array of "other" records which are
   *               linked in the joining table, and get_other_count() will return the number of
   *               "other" recrods found in the joining table.
   * @method array get_<record>_list_inverted() This is the same as the non-inverted method but it
   *               returns all items which are NOT linked to the joining table.
   * @method array get_<record>_id_list() Returns an array of primary ids from the joining <record>
                   table.
   * @method array get_<record>_id_list_inverted() This is the same as the non-inverted method but
   *               returns all ids which are NOT linked to the joining table.
   * @method int get_<record>_count() Returns the number of records in the joining <record> table
   *             given the provided modifier.
   * @method int get_<record>_count_inverted() This is the same as the non-inverted method but it
   *             returns the number of records NOT in the joining table.
   * @method null add_<record>() Given an array of ids, this method adds associations between the
   *              current and foreign <record> by adding rows into the joining "has" table.
   * @method null remove_<record>() Given an id, this method removes the association between the
                  current and foreign <record> by removing the corresponding row from the joining
                  "has" table.
   */
  public function __call( $name, $args )
  {
    // create an exception which will be thrown if anything bad happens
    $exception = lib::create( 'exception\runtime',
      sprintf( 'Call to undefined function: %s::%s().',
               get_called_class(),
               $name ), __METHOD__ );

    $return_value = NULL;
    
    // set up regular expressions
    $start = '/^add_|remove_|get_/';
    $end = '/(_list|_arraylist|_idlist|_count)(_inverted)?$/';
    
    // see if the start of the function name is a match
    if( !preg_match( $start, $name, $match ) ) throw $exception;
    $action = substr( $match[0], 0, -1 ); // remove underscore

    // now get the subject by removing the start and end of the function name
    $subject = preg_replace( array( $start, $end ), '', $name );
    
    // make sure the foreign table exists
    if( !static::db()->table_exists( $subject ) ) throw $exception;
    
    if( 'add' == $action )
    { // calling: add_<record>( $ids )
      // make sure the first argument is a non-empty array of ids
      if( 1 != count( $args ) || !is_array( $args[0] ) || 0 == count( $args[0] ) )
        throw lib::create( 'exception\argument', 'args', $args, __METHOD__ );

      $ids = $args[0];
      $this->add_records( $subject, $ids );
      return;
    }
    else if( 'remove' == $action )
    { // calling: remove_<record>( $ids )
      // make sure the first argument is a non-empty array of ids
      if( 1 != count( $args ) || 0 >= $args[0] )
        throw lib::create( 'exception\argument', 'args', $args, __METHOD__ );

      $id = $args[0];
      $this->remove_record( $subject, $id );
      return;
    }
    else if( 'get' == $action )
    {
      // get the end of the function name
      $sub_action = preg_match( $end, $name, $match ) ? substr( $match[0], 1 ) : false;
      
      if( !$sub_action )
      {
        // calling: get_<record>()
        // make sure this table has the correct foreign key
        if( !static::column_exists( $subject.'_id' ) ) throw $exception;
        return $this->get_record( $subject );
      }
      else
      { // calling one of: get_<record>_list( $modifier = NULL )
        //                 get_<record>_list_inverted( $modifier = NULL )
        //                 get_<record>_arraylist( $modifier = NULL )
        //                 get_<record>_arraylist_inverted( $modifier = NULL )
        //                 get_<record>_idlist( $modifier = NULL )
        //                 get_<record>_idlist_inverted( $modifier = NULL )
        //                 get_<record>_count( $modifier = NULL )
        //                 get_<record>_count_inverted( $modifier = NULL )
  
        // if there is an argument, make sure it is a modifier
        if( 0 < count( $args ) &&
            !is_null( $args[0] ) &&
            is_object( $args[0] ) &&
            'cenozo\database\modifier' != get_class( $args[0] ) )
          throw lib::create( 'exception\argument', 'args', $args, __METHOD );
        
        // determine the sub action and whether to invert the result
        $inverted = false;
        if( 'list' == $sub_action ||
            'arraylist' == $sub_action ||
            'idlist' == $sub_action ||
            'count' == $sub_action ) {}
        else if( 'arraylist_inverted' == $sub_action )
        {
          $sub_action = 'arraylist';
          $inverted = true;
        }
        else if( 'idlist_inverted' == $sub_action )
        {
          $sub_action = 'idlist';
          $inverted = true;
        }
        else if( 'list_inverted' == $sub_action )
        {
          $sub_action = 'list';
          $inverted = true;
        }
        else if( 'count_inverted' == $sub_action )
        {
          $sub_action = 'count';
          $inverted = true;
        }
        else throw $exception;
        
        // execute the function
        $modifier = 0 == count( $args ) ? NULL : $args[0];
        $distinct = 1 >= count( $args ) ? NULL : $args[1];

        if( 'list' == $sub_action )
        {
          return is_null( $distinct )
            ? $this->get_record_list( $subject, $modifier, $inverted )
            : $this->get_record_list( $subject, $modifier, $inverted, false, $distinct );
        }
        else if( 'arraylist' == $sub_action )
        {
          return is_null( $distinct )
            ? $this->get_record_arraylist( $subject, $modifier, $inverted )
            : $this->get_record_arraylist( $subject, $modifier, $inverted, $distinct );
        }
        else if( 'idlist' == $sub_action )
        {
          return is_null( $distinct )
            ? $this->get_record_idlist( $subject, $modifier, $inverted )
            : $this->get_record_idlist( $subject, $modifier, $inverted, $distinct );
        }
        else if( 'count' == $sub_action )
        {
          return is_null( $distinct )
            ? $this->get_record_count( $subject, $modifier, $inverted )
            : $this->get_record_count( $subject, $modifier, $inverted, $distinct );
        }
      }
    }

    // if we get here then something went wrong
    throw $exception;
  }
  
  /**
   * Returns the record with foreign keys referencing the record table.
   * This method is used to select a record's parent record in many-to-one relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @return record
   * @access protected
   */
  protected function get_record( $record_type )
  {
    // check the primary key value
    if( is_null( $this->column_values[static::get_primary_key_name()] ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return NULL;
    }
    
    $foreign_key_name = $record_type.'_id';

    // make sure this table has the correct foreign key
    if( !static::column_exists( $foreign_key_name ) )
    { 
      log::warning( 'Tried to get invalid record type: '.$record_type );
      return NULL;
    }

    // create the record using the foreign key
    $record = NULL;
    if( !is_null( $this->column_values[$foreign_key_name] ) )
    {
      $record = lib::create( 'database\\'.$record_type, $this->column_values[$foreign_key_name] );
    }

    return $record;
  }

  /**
   * Returns an array of records (or primary keys of those records) from the joining record table.
   * This method is used to select a record's child records in one-to-many or many-to-many
   * relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the list or count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @param boolean $count If true then this method returns the count instead of list of records.
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @param enum $format Whether to return an object, column data or only the record id
   * @return array( record ) | array( int ) | int
   * @access protected
   */
  public function get_record_list(
    $record_type,
    $modifier = NULL,
    $inverted = false,
    $count = false,
    $distinct = true,
    $format = 0 )
  {
    $table_name = static::get_table_name();
    $primary_key_name = sprintf( '%s.%s', $table_name, static::get_primary_key_name() );
    $foreign_class_name = lib::get_class_name( 'database\\'.$record_type );

    // check the primary key value
    $primary_key_value = $this->column_values[static::get_primary_key_name()];
    if( is_null( $primary_key_value ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return $count ? 0 : array();
    }
      
    // this method varies depending on the relationship type
    $relationship_class_name = lib::get_class_name( 'database\relationship' );
    $relationship = static::get_relationship( $record_type );
    if( $relationship_class_name::NONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to get a %s list from a %s, but there is no relationship between the two.',
                 $record_type,
                 $table_name ) );
      return $count ? 0 : array();
    }
    else if( $relationship_class_name::ONE_TO_ONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to get a %s list from a %s, but there is a '.
                 'one-to-one relationship between the two.',
                 $record_type,
                 $table_name ) );
      return $count ? 0 : array();
    }
    else if( $relationship_class_name::ONE_TO_MANY == $relationship )
    {
      $column_name = sprintf( '%s.%s_id', $record_type, $table_name );
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

      if( self::ARRAY_FORMAT == $format )
      {
        if( $inverted )
        {
          $modifier->left_join( $record_type, $column_name, $primary_key_name );
          $modifier->having( $column_name, '=', NULL );
          $modifier->or_having( $column_name, '!=', $primary_key_value );
        }
        else
        {
          $modifier->join( $table_name, $column_name, $primary_key_name );
        }
        $modifier->where( $column_name, '=', $primary_key_value );
      }
      else
      {
        if( $inverted )
        {
          $modifier->where( $column_name, '=', NULL );
          $modifier->or_where( $column_name, '!=', $primary_key_value );
        }
        else $modifier->where( $column_name, '=', $primary_key_value );
      }

      return $foreign_class_name::select( $modifier, $count, $distinct, $format );
    }
    else if( $relationship_class_name::MANY_TO_MANY == $relationship )
    {
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $joining_table_name = static::get_joining_table_name( $record_type );

      // check to see if the modifier is sorting a value in a foreign table
      $table_list = array( $table_name, $joining_table_name, $record_type );
      if( !is_null( $modifier ) )
      {
        // build an array of all foreign tables in the modifier
        $columns = $modifier->get_where_columns();
        $columns = array_merge( $columns, $modifier->get_order_columns() );
        $tables = array();
        foreach( $columns as $index => $column ) $tables[] = strstr( $column, '.', true );
        $tables = array_unique( $tables, SORT_STRING );

        foreach( $tables as $table )
        {
          if( $table && 0 < strlen( $table ) &&
              // skip joins which are done below
              !in_array( $table, $table_list ) &&
              // check to see if the joined table has a foreign key for this table and join if it
              // does but make sure not to do this check for N-to-N joining tables
              false === strpos( $table, '_has_' ) &&
              // don't add joins that already exist
              !$modifier->has_join( $table ) )
          {
            $foreign_key_name = $table.'_id';
            $table_class_name = lib::get_class_name( 'database\\'.$table );
            if( static::db()->column_exists( $record_type, $foreign_key_name ) )
            {
              $modifier->cross_join(
                $table, 
                sprintf( '%s.%s', $record_type, $foreign_key_name ),
                sprintf( '%s.%s', $table, $table_class_name::get_primary_key_name() ) );
            }
            // check to see if the joining table has this table as a foreign key
            else if( static::db()->column_exists( $joining_table_name, $foreign_key_name ) )
            {
              $modifier->where(
                $table,
                sprintf( '%s.%s', $joining_table_name, $foreign_key_name ),
                sprintf( '%s.%s', $table, $table_class_name::get_primary_key_name() ) );
            }
            // check to see if the origin table has this table as a foreign key
            else if( static::column_exists( $foreign_key_name ) )
            {
              $modifier->where(
                $table,
                sprintf( '%s.%s', $table_name, $foreign_key_name ),
                sprintf( '%s.%s', $table, $table_class_name::get_primary_key_name() ) );
            }
          }
        }
      }

      $foreign_key_name =
        sprintf( '%s.%s', $record_type, $foreign_class_name::get_primary_key_name() );
      $joining_primary_key_name = sprintf( '%s.%s_id', $joining_table_name, $table_name );
      $joining_foreign_key_name = sprintf( '%s.%s_id', $joining_table_name, $record_type );
  
      if( $inverted )
      { // we need to invert the list
        // first create SQL to match all records in the joining table
        $sub_modifier = lib::create( 'database\modifier' );
        $sub_modifier->cross_join( $joining_table_name, $primary_key_name, $joining_primary_key_name );
        $sub_modifier->cross_join( $record_type, $joining_foreign_key_name, $foreign_key_name );
        $sub_modifier->where( $primary_key_name, '=', $primary_key_value );
        $sub_select_sql =
          sprintf( 'SELECT %s FROM %s %s',
                   $joining_foreign_key_name,
                   $table_name,
                   $sub_modifier->get_sql() );
  
        // now create SQL that gets all primary ids that are NOT in that list
        $modifier->where( $foreign_key_name, 'NOT IN', $sub_select_sql, false );
        $sql = sprintf( $count
                          ? 'SELECT COUNT( %s%s ) FROM %s %s'
                          : 'SELECT %s%s FROM %s %s',
                        $distinct ? 'DISTINCT ' : '',
                        !$count && static::ARRAY_FORMAT == $format ?
                          $record_type.'.*' : $foreign_key_name,
                        $record_type,
                        $modifier->get_sql() );
      }
      else
      { // no inversion, just select the records from the joining table
        $modifier->cross_join( $joining_table_name, $primary_key_name, $joining_primary_key_name );
        $modifier->cross_join( $record_type, $joining_foreign_key_name, $foreign_key_name );
        $modifier->where( $primary_key_name, '=', $primary_key_value );
        $sql = sprintf( $count
                          ? 'SELECT COUNT( %s%s ) FROM %s %s'
                          : 'SELECT %s%s FROM %s %s',
                        $distinct ? 'DISTINCT ' : '',
                        !$count && static::ARRAY_FORMAT == $format ?
                          $record_type.'.*' : $joining_foreign_key_name,
                        $table_name,
                        $modifier->get_sql() );
      }
      
      if( $count )
      {
        return intval( static::db()->get_one( $sql ) );
      }
      else if( static::ARRAY_FORMAT == $format )
      {
        $rows = static::db()->get_all( $sql );
        foreach( $rows as $index => $row )
        {
          if( array_key_exists( 'update_timestamp', $row ) ) unset( $rows[$index]['update_timestamp'] );
          if( array_key_exists( 'create_timestamp', $row ) ) unset( $rows[$index]['create_timestamp'] );
        }
        return $rows;
      }
      else
      {
        $ids = static::db()->get_col( $sql );
        
        if( static::ID_FORMAT == $format )
        {
          return $ids; // requested to return a list of ids only
        }
        else
        {
          // create records from the ids
          $records = array();
          foreach( $ids as $id ) $records[] = lib::create( 'database\\'.$record_type, $id );
          return $records;
        }
      }
    }
    
    // if we get here then the relationship type is unknown
    log::crit(
      sprintf( 'Record %s has an unknown relationship to %s.',
               $table_name,
               $record_type ) );
    return $count ? 0 : array();
  }

  /**
   * Returns an array of column values from the joining record table.
   * This method is used to select a record's child records in one-to-many or many-to-many
   * relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @return int
   * @access protected
   */
  protected function get_record_arraylist(
    $record_type, $modifier = NULL, $inverted = false, $distinct = true )
  {
    return $this->get_record_list( $record_type, $modifier, $inverted, false, $distinct, static::ARRAY_FORMAT );
  }

  /**
   * Returns an array of primary keys from the joining record table.
   * This method is used to select a record's child records in one-to-many or many-to-many
   * relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @return int
   * @access protected
   */
  protected function get_record_idlist(
    $record_type, $modifier = NULL, $inverted = false, $distinct = true )
  {
    return $this->get_record_list( $record_type, $modifier, $inverted, false, $distinct, static::ID_FORMAT );
  }

  /**
   * Returns the number of records in the joining record table.
   * This method is used to count a record's child records in one-to-many or many-to-many
   * relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @return int
   * @access protected
   */
  protected function get_record_count(
    $record_type, $modifier = NULL, $inverted = false, $distinct = true )
  {
    return $this->get_record_list( $record_type, $modifier, $inverted, true, $distinct );
  }

  /**
   * Given an array of ids, this method adds associations between the current and foreign record
   * by adding rows into the joining "has" table.
   * This method is used to add child records for many-to-many relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param int|array(int) $ids A single or array of primary key values for the record(s) being
   *                       added.
   * @access protected
   */
  protected function add_records( $record_type, $ids )
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning(
        'Tried to add '.$record_type.' records to read-only record.' );
      return;
    }

    $util_class_name = lib::get_class_name( 'util' );
    
    // check the primary key value
    $primary_key_value = $this->column_values[static::get_primary_key_name()];
    if( is_null( $primary_key_value ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return;
    }

    // this method only supports many-to-many relationships.
    $relationship_class_name = lib::get_class_name( 'database\relationship' );
    $relationship = static::get_relationship( $record_type );
    if( $relationship_class_name::MANY_TO_MANY != $relationship )
    {
      log::err(
        sprintf( 'Tried to add %s to a %s without a many-to-many relationship between the two.',
                 $util_class_name::prulalize( $record_type ),
                 static::get_table_name() ) );
      return;
    }
    
    $database_class_name = lib::get_class_name( 'database\database' );
    $joining_table_name = static::get_joining_table_name( $record_type );
    
    // if ids is not an array then create a single-element array with it
    if( !is_array( $ids ) ) $ids = array( $ids );

    $values = '';
    $first = true;
    foreach( $ids as $foreign_key_value )
    {
      if( !$first ) $values .= ', ';
      $values .= sprintf( $this->include_timestamps
                          ? '(NULL, %s, %s)'
                          : '(%s, %s)',
                          static::db()->format_string( $primary_key_value ),
                          static::db()->format_string( $foreign_key_value ) );
      $first = false;
    }
    
    static::db()->execute(
      sprintf( $this->include_timestamps
               ? 'INSERT INTO %s (create_timestamp, %s_id, %s_id) VALUES %s'
               : 'INSERT INTO %s (%s_id, %s_id) VALUES %s',
               $joining_table_name,
               static::get_table_name(),
               $record_type,
               $values ) );
  }

  /**
   * Given an id, this method removes the association between the current and record by removing
   * the corresponding row from the joining "has" table.
   * This method is used to remove child records from one-to-many or many-to-many relationships.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param int $id The primary key value for the record being removed.
   * @access protected
   */
  protected function remove_record( $record_type, $id )
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning(
        'Tried to remove '.$foreign_table_name.' records to read-only record.' );
      return;
    }

    // check the primary key value
    $primary_key_value = $this->column_values[static::get_primary_key_name()];
    if( is_null( $primary_key_value ) )
    { 
      log::warning( 'Tried to query record with no id.' );
      return;
    }

    // this method varies depending on the relationship type
    $relationship_class_name = lib::get_class_name( 'database\relationship' );
    $relationship = static::get_relationship( $record_type );
    if( $relationship_class_name::NONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to remove a %s from a %s, but there is no relationship between the two.',
                 $record_type,
                 static::get_table_name() ) );
    }
    else if( $relationship_class_name::ONE_TO_ONE == $relationship )
    {
      log::err(
        sprintf( 'Tried to remove a %s from a %s, but there is a '.
                 'one-to-one relationship between the two.',
                 $record_type,
                 static::get_table_name() ) );
    }
    else if( $relationship_class_name::ONE_TO_MANY == $relationship )
    {
      $record = lib::create( 'database\\'.$record_type, $id );
      $record->delete();
    }
    else if( $relationship_class_name::MANY_TO_MANY == $relationship )
    {
      $joining_table_name = static::get_joining_table_name( $record_type );
  
      $modifier = lib::create( 'database\modifier' );
      $column_name = sprintf( '%s.%s_id', $joining_table_name, static::get_table_name() );
      $modifier->where( $column_name, '=', $primary_key_value );
      $column_name = sprintf( '%s.%s_id', $joining_table_name, $record_type );
      $modifier->where( $column_name, '=', $id );
  
      static::db()->execute(
        sprintf( 'DELETE FROM %s %s',
                 $joining_table_name,
                 $modifier->get_sql() ) );
    }
    else
    {
      // if we get here then the relationship type is unknown
      log::crit(
        sprintf( 'Record %s has an unknown relationship to %s.',
                 static::get_table_name(),
                 $record_type ) );
    }
  }
  
  /**
   * Gets the name of the joining table between this record and another.
   * If no such table exists then an empty string is returned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @static
   * @access protected
   */
  protected static function get_joining_table_name( $record_type )
  {
    // the joining table may be <table>_has_<foreign_table> or <foreign>_has_<table>
    $table_name = static::get_table_name();
    $forward_joining_table_name = $table_name.'_has_'.$record_type;
    $reverse_joining_table_name = $record_type.'_has_'.$table_name;
    
    $joining_table_name = "";
    if( static::db()->table_exists( $forward_joining_table_name ) )
    {
      $joining_table_name = $forward_joining_table_name;
    }
    else if( static::db()->table_exists( $reverse_joining_table_name ) )
    {
      $joining_table_name = $reverse_joining_table_name;
    }
    
    return $joining_table_name;
  }
  
  /**
   * Gets the type of relationship this record has to another record.
   * See the relationship class for return values.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @return int (relationship::const)
   * @static
   * @access public
   */
  public static function get_relationship( $record_type )
  {
    $relationship_class_name = lib::get_class_name( 'database\relationship' );
    $type = $relationship_class_name::NONE;
    $table_class_name = lib::get_class_name( 'database\\'.$record_type );
    if( $table_class_name::column_exists( static::get_table_name().'_id' ) )
    { // the record_type has a foreign key for this record
      $type = static::column_exists( $record_type.'_id' )
            ? $relationship_class_name::ONE_TO_ONE
            : $relationship_class_name::ONE_TO_MANY;
    }
    else if( 0 < strlen( static::get_joining_table_name( $record_type ) ) )
    { // a joining table was found
      $type = $relationship_class_name::MANY_TO_MANY;
    }

    return $type;
  }

  /**
   * Select a number of records.
   * 
   * This method returns an array of records.
   * The modifier may include any columns from tables which this record has a foreign key
   * relationship with.  To sort by such columns make sure to include the table name along with
   * the column name (for instance 'table.column') as the sort column value.
   * Be careful when calling this method.  Based on the modifier object a record is created for
   * every row being selected, so selecting a very large number of rows (100+) isn't a good idea.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @param enum $format Whether to return an object, column data or only the record id
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select(
    $modifier = NULL, $count = false, $distinct = true, $format = 0 )
  {
    $class_index = lib::get_class_name( get_called_class(), true );
    $this_table = static::get_table_name();
    
    // check to see if the modifier is sorting a value in a foreign table
    if( !is_null( $modifier ) )
    {
      // build an array of all foreign tables in the modifier
      $columns = $modifier->get_where_columns();
      $columns = array_merge( $columns, $modifier->get_order_columns() );
      $tables = array();
      foreach( $columns as $index => $column ) $tables[] = strstr( $column, '.', true );
      $tables = array_unique( $tables, SORT_STRING );

      foreach( $tables as $table )
      {
        if( $table && 0 < strlen( $table ) &&
            // don't join this table to itself
            $table != $this_table &&
            // don't add joins that already exist
            !$modifier->has_join( $table ) )
        {
          // check to see if we have a foreign key for this table and join if we do
          $foreign_key_name = $table.'_id';
          if( static::column_exists( $foreign_key_name ) )
          {
            $table_class_name = lib::get_class_name( 'database\\'.$table );
            // add the table to the list to select and join it in the modifier
            $modifier->cross_join(
              $table,
              sprintf( '%s.%s', $this_table, $foreign_key_name ),
              sprintf( '%s.%s', $table, $table_class_name::get_primary_key_name() ) );
          }
          // check to see if the foreign table has this table as a foreign key
          else if( static::db()->column_exists( $table, $this_table.'_id' ) )
          {
            // add the table to the list to select and join it in the modifier
            $modifier->cross_join(
              $table,
              sprintf( '%s.%s_id', $table, $this_table ),
              sprintf( '%s.%s', $this_table, static::get_primary_key_name() ) );
          }
        }
      }
    }
    
    $sql = sprintf( 'SELECT%s %s %s.%s %sFROM %s %s',
                    $count ? ' COUNT(' : '',
                    $distinct ? 'DISTINCT' : '',
                    $this_table,
                    !$count && static::ARRAY_FORMAT == $format ? '*' : static::get_primary_key_name(),
                    $count ? ') ' : '',
                    $this_table,
                    is_null( $modifier ) ? '' : $modifier->get_sql() );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else if( static::ARRAY_FORMAT == $format )
    {
      $rows = static::db()->get_all( $sql );
      foreach( $rows as $index => $row )
      {
        if( array_key_exists( 'update_timestamp', $row ) ) unset( $rows[$index]['update_timestamp'] );
        if( array_key_exists( 'create_timestamp', $row ) ) unset( $rows[$index]['create_timestamp'] );
      }
      return $rows;
    }
    else // return objects or a list of ids
    {
      $id_list = static::db()->get_col( $sql );
      if( static::ID_FORMAT == $format )
      {
        return $id_list;
      }
      else
      {
        // create records from the ids
        $records = array();
        foreach( $id_list as $id ) $records[] = new static( $id );
        return $records;
      }
    }
  }

  /**
   * Select a number of records as an array
   * 
   * A convenience method that returns an array of record primary ids.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @return array( int )
   * @static
   * @access public
   */
  public static function arrayselect( $modifier = NULL, $count = false, $distinct = true )
  {
    return static::select( $modifier, $count, $distinct, static::ARRAY_FORMAT );
  }

  /**
   * Select a number of record ids.
   * 
   * A convenience method that returns an array of record primary ids.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @return array( int )
   * @static
   * @access public
   */
  public static function idselect( $modifier = NULL, $count = false, $distinct = true )
  {
    return static::select( $modifier, $count, $distinct, static::ID_FORMAT );
  }

  /**
   * Count the total number of rows in the table.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @return int
   * @static
   * @access public
   */
  public static function count( $modifier = NULL, $distinct = true )
  {
    return static::select( $modifier, true, $distinct );
  }

  /**
   * Get record using the columns from a unique key.
   * 
   * This method returns an instance of the record using the name(s) and value(s) of a unique key.
   * If the unique key has multiple columns then the $column and $value arguments should be arrays.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string|array $column A column with the unique key property (or array of columns)
   * @param string|array $value The value of the column to match (or array of values)
   * @return database\record
   * @static
   * @access public
   */
  public static function get_unique_record( $column, $value )
  {
    $record = NULL;
    
    // create an associative array from the column/value arguments and sort
    if( is_array( $column ) && is_array( $value ) )
    {
      foreach( $column as $index => $col ) $columns[$col] = $value[$index];
    }
    else
    {
      $columns[$column] = $value;
    }
    ksort( $columns );

    // make sure the column(s) complete a unique key
    $found = false;
    foreach( static::db()->get_unique_keys( static::get_table_name() ) as $unique_key )
    {
      if( count( $columns ) == count( $unique_key ) )
      {
        sort( $unique_key );
        reset( $unique_key );
        foreach( $columns as $col => $val )
        {
          $found = $col == current( $unique_key );
          if( !$found ) break;
          next( $unique_key );
        }
      }

      if( $found ) break;
    }

    // make sure the column is unique
    if( !$found )
    {
      log::err( 'Trying to get unique record from table "'.
                static::get_table_name().'" using invalid columns.' );
    }
    else
    {
      $modifier = lib::create( 'database\modifier' );
      foreach( $columns as $col => $val ) $modifier->where( $col, '=', $val );

      // this returns null if no records are found
      $id = static::db()->get_one(
        sprintf( 'SELECT %s FROM %s %s',
                 static::get_primary_key_name(),
                 static::get_table_name(),
                 $modifier->get_sql() ) );

      if( !is_null( $id ) ) $record = new static( $id );
    }

    return $record;
  }

  /**
   * Returns the name of the table associated with this record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @static
   * @access public
   */
  public static function get_table_name()
  {
    // Table and class names (without namespaces) should always be identical (with the exception
    // of the table prefix
    $prefix = static::db()->get_prefix();
    return $prefix.substr( strrchr( get_called_class(), '\\' ), 1 );
  }
  
  /**
   * Returns an array of column names for this table.  Any columns in the database by the name
   * 'timestamp' are always ignored and left out of the active record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( string )
   * @access public
   */
  public function get_column_names()
  {
    return static::db()->get_column_names( static::get_table_name() );
  }

  /**
   * Returns the name of this record's primary key.
   * The schema does not currently support multiple-column primary keys, so this method always
   * returns a single column name.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @static
   * @access public
   */
  public static function get_primary_key_name()
  {
    return static::$primary_key_name;
  }
  
  /**
   * Returns an array of all enum values for a particular column.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name A column name in the record's corresponding table.
   * @return array( string )
   * @static
   * @access public
   */
  public static function get_enum_values( $column_name )
  {
    // match all strings in single quotes, then cut out the quotes from the match and return them
    $type = static::db()->get_column_type( static::get_table_name(), $column_name );
    preg_match_all( "/'[^']+'/", $type, $matches );
    $values = array();
    foreach( current( $matches ) as $match ) $values[] = substr( $match, 1, -1 );

    return $values;
  }
  
  /**
   * Returns an array of all distinct values for a particular column.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name A column name in the record's corresponding table.
   * @return array( string )
   * @static
   * @access public
   */
  public static function get_distinct_values( $column_name )
  {
    // not an column from the extending table list, make sure the column exists in the main table
    if( !static::column_exists( $column_name ) )
      throw lib::create( 'exception\argument', 'column_name', $column_name, __METHOD__ );

    $sql = sprintf( 'SELECT DISTINCT %s FROM %s ORDER BY %s',
                    $column_name,
                    static::get_table_name(),
                    $column_name );
    return static::db()->get_col( $sql );
  }
  
  /**
   * Convenience method for database::column_exists(), but for this record
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name A column name.
   * @param boolean $include_extending_tables Whether to include the column search in the
   *                extending tables
   * @return string
   * @static
   * @access public
   */
  public static function column_exists( $column_name, $include_extending_tables = false )
  {
    $found = static::db()->column_exists( static::get_table_name(), $column_name );

    if( $include_extending_tables && !$found )
    {
      foreach( self::get_extending_table_list() as $extending_table )
      {
        $table_name = sprintf( '%s_%s', static::get_table_name(), $extending_table );
        $len = strlen( $extending_table ) + 1;
        $extending_column = substr( $column_name, $len );
        if( static::db()->column_exists( $table_name, $extending_column ) )
        {
          $found = true;
          break;
        }
      }
    }

    return $found;
  }

  /**
   * Returns the record's database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @static
   * @access public
   */
  public static function db()
  {
    if( is_null( static::$db ) ) static::$db = lib::create( 'business\session' )->get_database();
    return static::$db;
  }

  /**
   * A list of tables which extend this record's data.  This is to be used by extending classes
   * of a record defined in the framework.  For instance, to add address details to the user
   * record a new table, user_address, is created with a column "user_id" as a primary key which
   * is also a foreign key to the user table.  Then, this method is called at the end of the user
   * class declaration with the argument "address".  The record will then act as if the columns in
   * user_address (not including the user_id column) are in the user table.
   * NOTE: do not include update_timestamp or create_timestamp columns in the extending table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @static
   * @access public
   */
  public static function add_extending_table( $table )
  {
    $class_index = lib::get_class_name( get_called_class(), true );
    if( !array_key_exists( $class_index, self::$extending_table_list ) )
      self::$extending_table_list[$class_index] = array();

    self::$extending_table_list[$class_index][] = $table;
  }

  /**
   * Returns an array of all extending tables, or an empty array if there are no extending tables.
   * Note, these names do not include the table_ prefix
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @static
   * @access protecteda
   */
  protected static function get_extending_table_list()
  {
    $class_index = lib::get_class_name( get_called_class(), true );
    return array_key_exists( $class_index, self::$extending_table_list ) ?
      self::$extending_table_list[$class_index] : array();
  }

  /**
   * Returns an array of table name, key and reference to column values for all tables associated
   * with this record.  This includes the main table first, then all extending tables in the order
   * they were added to the record using the add_extending_tables() method.
   * This method is used internally by this class only.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access private
   */
  private function get_working_table_list()
  {
    // make an array containing this record's table name, key and a reference to its column values
    // then add the same for any extending tables
    $tables = array();
    $tables[] = array( 'name' => static::get_table_name(),
                       'key' => static::get_primary_key_name(),
                       'columns' => & $this->column_values );
    
    foreach( self::get_extending_table_list() as $extending_table )
    {
      // make sure the extending column values array exists
      if( !array_key_exists( $extending_table, $this->extending_column_values ) )
        $this->extending_column_values[$extending_table] = array();

      $table_name = sprintf( '%s_%s', static::get_table_name(), $extending_table );
      $tables[] = array(
        'name' => $table_name,
        'key' => sprintf( '%s_%s', static::get_table_name(), static::get_primary_key_name() ),
        'columns' => & $this->extending_column_values[$extending_table] );
    }

    return $tables;
  }

  /**
   * TODO: document
   */
  protected static $db = null;

  /**
   * Determines whether the record is read only (no modifying the database).
   * @var boolean
   * @access protected
   */
  protected $read_only = false;

  /**
   * Holds all table column values in an associative array where key=>value is
   * column_name=>column_value
   * @var array
   * @access private
   */
  private $column_values = array();

  /**
   * Determines whether or not to include create_timestamp and update_timestamp when writing
   * records to the database.
   * @var boolean
   * @static
   * @access protected
   */
  protected $include_timestamps = true;

  /**
   * The name of the table's primary key column.
   * @var string
   * @static
   * @access protected
   */
  protected static $primary_key_name = 'id';

  /**
   * Defines which unique key to use when asking to convert primary and unique keys.
   * @var array( string )
   * @static
   * @access private
   */
  private static $primary_unique_key_list = array();

  /**
   * A list of tables which extend this record's data.  See add_extending_table() for more details.
   * @var array
   * @static
   * @access private
   */
  private static $extending_table_list = array();

  /**
   * Holds all extending column values in an associative array where key=>key=>value is
   * extending_table=>column=>column_value
   * @var array
   * @access private
   */
  private $extending_column_values = array();
}
