<?php
/**
 * service.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\service;
use cenozo\lib, cenozo\log;

/**
 * The base class for all services.
 *
 * All service classes extend this base service class.  All classes that extend this class are
 * used to fulfill some purpose executed by the user or machine interfaces.
 */
abstract class service extends \cenozo\base_object
{
  /**
   * Returns the associated database service for the provided service.
   * 
   * In addition to constructing the service object, the service is also validated against the
   * user's current role's access.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $method The request's method (DELETE, GET, HEAD, PATCH, POST)
   * @param string $path The URL of the service (not including the base)
   * @param array $args An associative array of arguments included in the request
   * @param string $file The raw file posted by PATCH and POST requests
   * @access public
   */
  public function __construct( $method, $path, $args = NULL, $file = NULL )
  {
    $code = $this->process_path( $path ) ? 200 : 400;
    if( 400 != $code && !self::is_method( $method ) ) $code = 405;
    $this->status = lib::create( 'service\status', $code );
    $this->path = $path;
    $this->method = strtoupper( $method );
    $this->arguments = $args;
    if( !is_array( $this->arguments ) ) $this->arguments = array();
    $this->file = $file;
  }

  /**
   * Processes the service by doing the following stages:
   * 1. prepare:  processes path and arguments, preparing them for the service
   * 2. validate: checks to make sure the arguments are valid, the user has access, etc
   * 3. setup:    a pre-execution phase that sets up the service
   * 4. execute:  execution of the service, completing the task
   * 5. finish:   a post-execution phase that finishes extra tasks after execution
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function process()
  {
    $util_class_name = lib::get_class_name( 'util' );

    try
    {
      if( self::$debug ) $time['begin'] = $util_class_name::get_elapsed_time();

      if( 300 <= $this->status->get_code() ) return;
      $this->prepare();
      if( self::$debug ) $time['prepare'] = $util_class_name::get_elapsed_time();

      if( 300 <= $this->status->get_code() ) return;
      $this->validate();
      if( self::$debug ) $time['validate'] = $util_class_name::get_elapsed_time();

      if( 300 <= $this->status->get_code() ) return;
      $this->setup();
      if( self::$debug ) $time['setup'] = $util_class_name::get_elapsed_time();

      if( 300 <= $this->status->get_code() ) return;
      $this->execute();
      if( self::$debug ) $time['execute'] = $util_class_name::get_elapsed_time();

      if( 300 <= $this->status->get_code() ) return;
      $this->finish();
      if( self::$debug ) $time['finish'] = $util_class_name::get_elapsed_time();

      if( self::$debug )
      {
        log::debug( sprintf( '[%s] %s times: (%s) => (%s)',
                             $this->method,
                             $this->path,
                             implode( ', ', array_keys( $time ) ),
                             implode( ', ', array_values( $time ) ) ) );
      }
    }
    catch( \cenozo\exception\notice $e )
    {
      $this->set_data( $e->get_notice() );
      $this->status->set_code( 406 );
    }
  }

  /**
   * Processes arguments, preparing them for the service.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    $util_class_name = lib::get_class_name( 'util' );
    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();
    $db_site = $session->get_site();
    $db_role = $session->get_role();

    // go through all collection/resource pairs
    foreach( $this->collection_name_list as $index => $subject )
    {
      if( array_key_exists( $index, $this->resource_value_list ) )
      {
        $record = $this->get_resource( $index );

        if( is_null( $record ) )
        {
          $this->status->set_code( 404 );
          break;
        }
        else
        {
          // ensure that this resource belongs to the parent record (if there is one)
          if( 0 < $index )
          {
            // get the parent record and test to see if this record is one of its children
            $parent_record = $this->get_resource( $index - 1 );
            $method_name = sprintf( 'get_%s_count', $subject );
            $modifier = lib::create( 'database\modifier' );
            $primary_key_name = $record::get_primary_key_name();
            $modifier->where(
              sprintf( '%s.%s', $record::get_table_name(), $primary_key_name ),
              '=', $record->$primary_key_name );
            if( 0 == $parent_record->$method_name( $modifier ) )
            {
              $this->status->set_code( 404 );
              break;
            }
          }
        }
      }
    }

    if( 404 != $this->status->get_code() &&
        in_array( $this->method, array( 'DELETE', 'POST' ) ) &&
        'self' != $this->get_leaf_subject() )
    { // record to the write log if the method is of write type
      $this->db_writelog = lib::create( 'database\writelog' );
      $this->db_writelog->user_id = $db_user->id;
      $this->db_writelog->site_id = $db_site->id;
      $this->db_writelog->role_id = $db_role->id;
      $this->db_writelog->method = $this->method;
      $this->db_writelog->path = $this->path;
      $this->db_writelog->datetime = $util_class_name::get_datetime_object();
      $this->db_writelog->save();
    }
  }

  /**
   * Validate the service.  If validation fails the service's status code will be set.
   * 
   * Validation works by checking each collection/resource pair and making sure the current role
   * has access to it.  For non-leaf pairs the method "GET" is checked.  For the leaf pair the
   * service's method is used instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function validate()
  {
    $relationship_class_name = lib::get_class_name( 'database\relationship' );
    $service_class_name = lib::get_class_name( 'database\service' );
    $session = lib::create( 'business\session' );

    if( $this->validate_access )
    {
      // check access for each collection/resource pair
      $parent_subject = NULL;
      $many_to_many = $relationship_class_name::MANY_TO_MANY === $this->get_leaf_parent_relationship();
      foreach( $this->collection_name_list as $index => $subject )
      {
        $has_resource = array_key_exists( $index, $this->resource_value_list );
        $method = 'GET';

        // for the leaf it depends on whether there is a many-to-many relationship with the parent
        if( $index == count( $this->collection_name_list ) - 1 && !$many_to_many ) $method = $this->method;
        if( 'HEAD' == $method ) $method = 'GET'; // HEAD access is based on GET access

        $db_service = $service_class_name::get_unique_record(
          array( 'method', 'subject', 'resource' ),
          array( $method, $subject, $has_resource ) );

        // make sure the service exists, is allowed and the module validates
        if( is_null( $db_service ) )
        {
          $this->status->set_code( 404 );
          break;
        }
        else if( is_null( $this->module_list[$index] ) )
        {
          throw lib::create( 'exception\runtime',
            sprintf( 'Service module for "%s" is not found', $subject ),
            __METHOD__ );
        }
        else if( 'HEAD' != $this->method )
        {
          if( !$session->is_service_allowed( $db_service ) ) $this->status->set_code( 403 );
          else $this->module_list[$index]->validate();
        }

        // don't bother continuing if we've got an error
        if( 300 <= $this->status->get_code() ) break;

        $parent_subject = $subject;
      }
    }
  }

  /**
   * Sets up the service with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup() {}

  /**
   * This method executes the service's purpose.  All services must implement this method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute() {}

  /**
   * Finishes the service with any post-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function finish() {}

  /**
   * Closes the writelog created by the service by adding the elapsed time and status
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function close_writelog()
  {
    if( !is_null( $this->db_writelog ) )
    {
      $util_class_name = lib::get_class_name( 'util' );
      $this->db_writelog->elapsed = $util_class_name::get_elapsed_time();
      $this->db_writelog->status = $this->status->get_code();
      $this->db_writelog->save();
    }
  }

  /**
   * Converts the service's path into a a list of collection and resource names.
   * 
   * This method will return false if there was a problem processing the path
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $path
   * @return boolean
   * @access protected
   */
  protected function process_path( $path )
  {
    $this->collection_name_list = array();
    $this->resource_value_list = array();
    $this->module_list = array();

    if( 0 < strlen( $path ) )
    {
      $module_index = 0;
      foreach( explode( '/', $path ) as $index => $part )
      {
        if( 0 == strlen( $part ) ) return false;

        if( 0 == $index % 2 )
        {
          $this->collection_name_list[] = $part;
          try
          {
            $this->module_list[] = lib::create( sprintf( 'service\%s\module', $part ), $module_index, $this );
          }
          // ignore runtime exceptions and instead just return a null module
          catch( \cenozo\exception\runtime $e )
          {
            $this->module_list[] = NULL;
          }
          $module_index++;
        }
        else $this->resource_value_list[] = $part;
      }
    }

    return true;
  }

  /**
   * Returns the service's method
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_method() { return $this->method; }

  /**
   * TODO: document
   */
  public function get_number_of_collections()
  {
    return count( $this->collection_name_list );
  }

  /**
   * Returns the subject for a particular index
   * 
   * The index is based on the service's path.  Every other item in the path identifies a
   * collection by name (string).  For instance, for the path /collection/1/participant/2
   * the first subject is "collection" and the second is "participant".  Null is returned
   * if there is no subject for the given index.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $index
   * @return string
   * @access public
   */
  public function get_subject( $index )
  {
    return array_key_exists( $index, $this->collection_name_list ) ? $this->collection_name_list[$index] : NULL;
  }

  /**
   * Returns the resource value for a particular index
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $index
   * @return string
   * @access protected
   */
  protected function get_resource_value( $index )
  {
    return array_key_exists( $index, $this->resource_value_list ) ? $this->resource_value_list[$index] : NULL;
  }

  /** 
   * TODO: document
   */
  protected function get_record_class_name( $index, $relative = false )
  {
    $subject = $this->get_subject( $index );
    if( is_null( $subject ) )
      throw lib::create( 'exception\runtime',
        sprintf( 'Tried to get record class name for invalid subject (index: %d)', $index ),
        __METHOD__ );

    $class = sprintf( 'database\%s', $subject );
    return $relative ? $class : lib::get_class_name( $class );
  }

  /**
   * Returns the resource for a particular index
   * 
   * The index is based on the service's path.  Every other item in the path identifies a
   * resource either by ID or some other set of key/value pair(s).  For instance, for the
   * path /collection/1/participant/2 the first resource would be a collection for ID 1
   * and the second a participant for ID 2.  Null is returned if there is no resource for
   * the given index.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $index
   * @return database\record
   * @access public
   */
  public final function get_resource( $index )
  {
    if( !array_key_exists( $index, $this->resource_cache ) )
      $this->resource_cache[$index] = $this->create_resource( $index );
    return $this->resource_cache[$index];
  }

  /**
   * TODO: document
   */
  protected function create_resource( $index )
  {
    $session = lib::create( 'business\session' );

    $record = NULL;

    if( array_key_exists( $index, $this->collection_name_list ) &&
        array_key_exists( $index, $this->resource_value_list ) )
    {
      $resource_value = $this->resource_value_list[$index];

      $util_class_name = lib::get_class_name( 'util' );
      $record_class_name = $this->get_record_class_name( $index );

      if( $util_class_name::string_matches_int( $resource_value ) )
      { // there is a resource, get the corresponding record
        try
        {
          $record = new $record_class_name( $resource_value );
        }
        catch( \cenozo\exception\notice $e )
        {
          $this->set_data( $e->get_notice() );
          $this->status->set_code( 406 );
        }
        // ignore runtime exceptions and instead just return a null record
        catch( \cenozo\exception\runtime $e ) {}
      }
      else if( false !== strpos( $resource_value, '=' ) )
      { // check unique keys
        $columns = array();
        $values = array();
        foreach( explode( ';', $resource_value ) as $part )
        {
          $pair = explode( '=', $part );
          if( 2 == count( $pair ) )
          {
            $columns[] = $pair[0];
            $values[] = $pair[1];
          }
        }

        if( 0 < count( $columns ) )
        {
          $parent_index = $index - 1;
          if( 0 <= $parent_index )
          {
            // add the parent ID to the unique key
            $parent_record = $this->get_resource( $parent_index );
            $columns[] = sprintf( '%s_id', $parent_record->get_class_name() );
            $values[] = $parent_record->id;
          }

          $record = $record_class_name::get_unique_record( $columns, $values );
        }
      }
    }

    return $record;
  }

  /**
   * Returns the resource of the second-to-last collection (based on the service's path)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string If there is no parent subject then NULL is returned
   * @access protected
   */
  protected function get_parent_record()
  {
    $count = count( $this->collection_name_list );
    return 1 < $count ? $this->get_resource( $count - 2 ) : NULL;
  }

  /**
   * Returns the subject of the last collection (based on the service's path)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string If there is no leaf subject then NULL is returned
   * @access public
   */
  public function get_leaf_subject()
  {
    return $this->get_subject( count( $this->collection_name_list ) - 1 );
  }

  /**
   * Returns the subject of the second-to-last collection (based on the service's path)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string If there is no parent subject then NULL is returned
   * @access public
   */
  public function get_parent_subject()
  {
    return $this->get_subject( count( $this->collection_name_list ) - 2 );
  }

  /**
   * TODO: document
   */
  public function get_leaf_record_class_name()
  {
    return $this->get_record_class_name( count( $this->collection_name_list ) - 1 );
  }

  /**
   * Returns the resource of the last collection (based on the service's path)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string If there is no leaf subject then NULL is returned
   * @access public
   */
  public function get_leaf_record()
  {
    $count = count( $this->collection_name_list );
    return 0 < $count ? $this->get_resource( $count - 1 ) : NULL;
  }

  /**
   * Returns the module of the last collection (based on the service's path)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return module If there is no leaf subject then NULL is returned
   * @access protected
   */
  protected function get_leaf_module()
  {
    $count = count( $this->collection_name_list );
    return 0 < $count ? $this->module_list[$count - 1] : NULL;
  }

  /**
   * Returns the database relationship between the leaf and its parent
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int See database\relationship for list of possible values
   * @access public
   */
  public function get_leaf_parent_relationship()
  {
    $parent_record = $this->get_parent_record();
    return is_null( $parent_record ) ? NULL : $parent_record::get_relationship( $this->get_leaf_subject() );
  }

  /**
   * Returns the file provided to the service (unchanged)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_file_as_raw()
  {
    return $this->file;
  }

  /**
   * Returns the file provided to the service decoded as an object
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_file_as_object()
  {
    if( null === $this->file_as_object )
    {
      $util_class_name = lib::get_class_name( 'util' );
      $this->file_as_object = $util_class_name::json_decode( $this->file );
    }

    return $this->file_as_object;
  }

  /**
   * Returns the file provided to the service decoded as an associate array
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_file_as_array()
  {
    if( null === $this->file_as_array )
    {
      $object = $this->get_file_as_object();
      if( !is_null( $object ) ) $this->file_as_array = get_object_vars( $object );
    }

    return $this->file_as_array;
  }

  /**
   * Get a query argument passed to the service.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the argument.
   * @param mixed $default The value to return if no argument exists.  If the default is null then
   *                       it is assumed that the argument must exist, throwing an argument
                           exception if it is not set.
   * @return mixed
   * @throws exception\argument
   * @access protected
   */
  public function get_argument( $name, $default = NULL )
  {
    $argument = NULL;
    if( !array_key_exists( $name, $this->arguments ) )
    {
      if( 1 == func_num_args() )
      {
        $this->status->set_code( 400 );
        throw lib::create( 'exception\argument', $name, NULL, __METHOD__ );
      }
      $argument = $default;
    }
    else
    { // the argument exists
      $argument = $this->arguments[$name];
    }

    return $argument;
  }

  /**
   * Returns the http status
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return service\status
   * @access public
   */
  public function get_status() { return $this->status; }

  /**
   * Returns any headers generated by the service.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( mixed )
   * @access public
   */
  public function get_headers() { return $this->headers; }

  /**
   * Returns any data generated by the service.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return mixed
   * @access public
   */
  public function get_data() { return $this->data; }

  /**
   * Sets the data returned by the service.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param mixed $data
   * @access public
   */
  public function set_data( $data ) { $this->data = $data; }

  /**
   * Gets whether to check if the user has access to the service
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access protected
   */
  protected function get_validate_access()
  {
    return $this->validate_access;
  }

  /**
   * Sets whether to check if the user has access to the service
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $access
   * @access protected
   */
  protected function set_validate_access( $access )
  {
    $this->validate_access = $access;
  }

  /**
   * Returns whether or not a method is valid
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param $method string
   * @return boolean
   * @access public
   * @static
   */
  public static function is_method( $method )
  {
    $method = strtoupper( $method );
    return array_key_exists( $method, self::$method_list );
  }

  /**
   * Returns whether or not a method is valid and read-based
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param $method string
   * @return boolean
   * @access public
   * @static
   */
  public static function is_read_method( $method )
  {
    $method = strtoupper( $method );
    return array_key_exists( $method, self::$method_list ) && !self::$method_list[$method];
  }

  /**
   * Returns whether or not a method is valid and write-based
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param $method string
   * @return boolean
   * @access public
   * @static
   */
  public static function is_write_method( $method )
  {
    $method = strtoupper( $method );
    return array_key_exists( $method, self::$method_list ) && self::$method_list[$method];
  }

  /**
   * When set to true all service processes will report elapsed times to the debug log
   * @var boolean
   * @static
   * @access public
   */
  public static $debug = false;

  /**
   * Header data generated by the service (if any).
   * @var array( mixed )
   * @access protected
   */
  protected $headers = array();

  /**
   * Data generated by the service (if any).
   * @var mixed
   * @access protected
   */
  protected $data = NULL;

  /**
   * The status object returned in response to the service request
   * @var service\status
   * @access protected
   */
  protected $status = NULL;

  /**
   * The path of the service
   * @var string
   * @access private
   */
  private $path = NULL;

  /**
   * The method used for the service
   * @var string
   * @access private
   */
  private $method = NULL;

  /**
   * The url query arguments.
   * @var array( array )
   * @access private
   */
  private $arguments = array();

  /**
   * The raw file posted by PATCH and POST requests
   * @var array( array )
   * @access private
   */
  private $file = NULL;

  /**
   * The PATCH/POST file as an object
   * @var array( array )
   * @access private
   */
  private $file_as_object = NULL;

  /**
   * The PATCH/POST file as an array
   * @var array( array )
   * @access private
   */
  private $file_as_array = NULL;

  /**
   * The writelog record associated with the service request (null for read-only services)
   * @var database\writelog
   * @access private
   */
  private $db_writelog = NULL;

  /**
   * A list of all collection names based on the service's path
   * @var array( string )
   * @access private
   */
  private $collection_name_list = NULL;

  /**
   * A list of all resource lookup values based on the service's path (may be an id or some other
   * set of key/value pair(s)
   * @var array( string )
   * @access private
   */
  private $resource_value_list = NULL;

  /**
   * TODO: document
   */
  private $module_list = NULL;

  /**
   * 
   * @var array( database\record )
   * @access private
   */
  private $resource_cache = array();

  /**
   * Whether to check if the user's access has permission to perform this service
   * @var boolean
   * @access private
   */
  private $validate_access = true;

  /**
   * A list of all valid methods (as keys) and whether they are write services (as value)
   * @var array( string => boolean )
   * @access private
   * @static
   */
  private static $method_list = array(
    'DELETE' => true,
    'GET' => false,
    'HEAD' => false,
    'PATCH' => true,
    'POST' => true );
}
