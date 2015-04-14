<?php
/**
 * select.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\database;
use cenozo\lib, cenozo\log;

/**
 * This class is used to create select queries
 */
class select extends \cenozo\base_object
{
  /**
   * Set the base table to select from
   * 
   * Only one table name should be provided.  Joining tables must be defined using a modifier object
   * @param string $table
   * @param string $alias An optional alias for the table
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function from( $table, $alias = NULL )
  {
    $this->table_name = $table;
    $this->table_alias = $alias;
  }

  /**
   * Returns the table name
   * 
   * @return string
   * @access public
   */
  public function get_table_name()
  {
    return 0 < strlen( $this->table_name ) ? $this->table_name : NULL;
  }

  /**
   * Returns the table alias
   * 
   * @return string
   * @access public
   */
  public function get_table_alias()
  {
    return $this->table_alias;
  }

  /**
   * Adds a column from a specific table to the select
   * 
   * Note that this will overwrite any existing column with the same parameters.
   * @param string $table The table to select the column from
   * @param string $column The column to select
   * @param string $alias The optional alias for the column (must be unique)
   * @param boolean $table_prefix Whether to prefix the column with the table name
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function add_table_column( $table, $column, $alias = NULL, $table_prefix = true )
  {
    // sanitize
    if( is_null( $column ) || 0 == strlen( $column ) )
      throw lib::create( 'exception\argument', 'column', $column, __METHOD__ );
    if( !is_null( $alias ) && 0 == strlen( $alias ) )
      throw lib::create( 'exception\argument', 'alias', $alias, __METHOD__ );

    if( is_null( $table ) ) $table = '';
    if( is_null( $alias ) ) $alias = $column;

    // remove any other column with the same alias
    foreach( $this->column_list as $t => $c )
      foreach( $c as $a => $details )
        if( $a == $alias ) unset( $this->column_list[$t][$a] );

    if( !array_key_exists( $table, $this->column_list ) ) $this->column_list[$table] = array();
    $this->column_list[$table][$alias] = array( 'column' => $column, 'table_prefix' => $table_prefix );
  }

  /**
   * Adds all columns for a table to the select (table.*)
   * 
   * @param string $table The table to select * on.  If no table is provided then the base table
   *               defined by the from() method will be used.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function add_all_table_columns( $table = NULL )
  {
    $this->add_table_column( $table, '*' );
  }

  /**
   * Adds a column from the main table defined by the from() method
   * 
   * @param string $column The column to select
   * @param string $alias The optional alias for the column
   * @param boolean $table_prefix Whether to prefix the column with the table name
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function add_column( $column, $alias = NULL, $table_prefix = true )
  {
    $this->add_table_column( NULL, $column, $alias, $table_prefix );
  }

  /**
   * Removes one or more columns from the select
   * 
   * @param string $table Restricts removal to a particular table (or the select's main table if null)
   *               If a table has an alias then the alias must be used
   * @param string $column Restricts removal to a particular column
   * @param string $alias Restricts removal to a particular alias
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function remove_column( $table = NULL, $column = NULL, $alias = NULL )
  {
    if( is_null( $table ) ) $table = '';

    if( array_key_exists( $table, $this->column_list ) )
    {
      if( is_null( $column ) && is_null( $alias ) ) unset( $this->column_list[$table] );
      else
      {
        if( is_null( $column ) )
        {
          unset( $this->column_list[$table][$alias] );
        }
        else
        {
          foreach( $this->column_list[$table] as $table_alias => $table_column )
            if( ( is_null( $column ) || $column == $table_column ) &&
                ( is_null( $alias ) || $alias == $table_alias ) )
              unset( $this->column_list[$table][$table_alias] );
        }
      }
    }
  }

  /**
   * Removes all columns which are from a particular table
   * 
   * @param string $table The table to remove
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function remove_column_by_table( $table )
  {
    $this->remove_column( $table, NULL, NULL );
  }

  /**
   * Removes all columns with a particular name
   * 
   * @param string $column The column to remove
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function remove_column_by_column( $column )
  {
    $this->remove_column( NULL, $column, NULL );
  }

  /**
   * Restricts removal to a particular alias
   * 
   * @param string $alias The column alias to remove
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function remove_column_by_alias( $alias )
  {
    $this->remove_column( NULL, NULL, $alias );
  }

  /**
   * Returns whether columns have been added to the select or not
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function has_columns()
  {
    return 0 < count( $this->column_list );
  }

  /**
   * Returns whether a column from a particular table has been added to the select or not
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table The table to search for
   * @param string $column The column (or alias) to search for (optional)
   * @return boolean
   * @access public
   */
  public function has_table_column( $table, $column = NULL )
  {
    return array_key_exists( $table, $this->column_list ) &&
           ( is_null( $column ) || array_key_exists( $column, $this->column_list[$table] ) );
  }

  /**
   * Returns whether any columns from a particular table have been added to the select or not
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $table The table to search for
   * @return boolean
   * @access public
   */
  public function has_table_columns( $table )
  {
    return $this->has_table_column( $table );
  }

  /**
   * Returns the select statement based on this object
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_sql()
  {
    if( 0 == strlen( $this->table_name ) )
      throw lib::create( 'exception\runtime',
        'Tried to get SQL from select before table "from" value is set', __METHOD__ );

    // figure out which table to select from
    $main_table = is_null( $this->table_alias ) ? $this->table_name : $this->table_alias;

    // figure out the columns
    $columns = array();
    foreach( $this->column_list as $table => $column_details )
    {
      // table prefix
      $table_prefix = 0 == strlen( $table ) ? $main_table : $table;
      $table_prefix .= '.';

      // now add the alias or table.column to the list of columns
      foreach( $column_details as $alias => $item )
      {
        $column = sprintf( '%s%s', $item['table_prefix'] ? $table_prefix : '', $item['column'] );
        // convert datetimes to ISO 8601 format
        if( false !== strpos( $item['column'], 'datetime' ) )
          $column = sprintf( 'DATE_FORMAT( %s, "%s" )', $column, '%Y-%m-%dT%T+00:00' );
        // add the alias
        $column = sprintf( '%s AS %s', $column, $alias );
        $columns[] = $column;
      }
    }

    $table = is_null( $this->table_alias )
           ? $this->table_name
           : sprintf( '%s AS %s', $this->table_name, $this->table_alias );
    return sprintf( 'SELECT %s FROM %s', join( ',', $columns ), $table );
  }

  /**
   * JSON-based select expected in the form:
   * {
   *   from: <table_name>
   *   OR
   *   from:
   *   {
   *     table: <table_name>
   *     alias: <table_alias>
   *   }
   *   column:
   *   [
   *     <column_name>,
   *     {
   *       table: <table_name> (optional)
   *       column: <column_name>
   *       alias: <column_alias> (optional)
   *       table_prefix: true|false (optional)
   *     },
   *   ],
   * }
   */
  public static function from_json( $json_string )
  {
    $select = lib::create( 'database\select' );

    $util_class_name = lib::get_class_name( 'util' );
    $json_object = $util_class_name::json_decode( $json_string );
    if( is_object( $json_object ) || is_array( $json_object ) )
    {
      foreach( (array) $json_object as $key => $value )
      {
        if( 'from' == $key )
        {
          if( is_array( $value ) )
          {
            if( array_key_exists( 'table', $value ) )
            {
              $this->from( $value['table'], array_key_exists( 'alias', $value ) ? $value['alias'] : NULL );
            }
            else throw lib::create( 'exception\runtime', 'Invalid from statement', __METHOD__ );
          }
          else if( is_string( $value ) ) $this->from( $value );
          else throw lib::create( 'exception\runtime', 'Invalid from statement', __METHOD__ );
        }
        else if( 'column' == $key )
        {
          // convert a statement into an array (for single arguments or objects)
          if( !is_array( $value ) ) $value = array( $value );
          
          foreach( $value as $column )
          {
            if( is_object( $column ) ) $column = (array) $column;
            if( is_array( $column ) )
            {
              if( array_key_exists( 'column', $column ) )
              {
                $select->add_table_column(
                  array_key_exists( 'table', $column ) ? $column['table'] : NULL,
                  $column['column'],
                  array_key_exists( 'alias', $column ) ? $column['alias'] : NULL,
                  array_key_exists( 'table_prefix', $column ) ? $column['table_prefix'] : true );
              }
              else throw lib::create( 'exception\runtime', 'Invalid column sub-statement', __METHOD__ );
            }
            else if( is_string( $column ) ) $select->add_column( $column );
            else throw lib::create( 'exception\runtime', 'Invalid column sub-statement', __METHOD__ );
          }
        }
      }
    }
    else throw lib::create( 'exception\runtime', 'Invalid format', __METHOD__ );

    return $select;
  }

  /**
   * The table to select from
   * @var string
   * @access protected
   */
  protected $table_name = '';

  /**
   * The alias to use for the table to select from
   * @var string
   * @access protected
   */
  protected $table_alias = NULL;

  /**
   * 
   * @var array
   * @access protected
   */
  protected $column_list = array();
}
