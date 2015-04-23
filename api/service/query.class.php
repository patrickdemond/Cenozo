<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\service;
use cenozo\lib, cenozo\log;

/**
 * The base class of all query (collection-based get) services
 */
class query extends read
{
  /**
   * Extends parent constructor
   */
  public function __construct( $path, $args = NULL )
  {
    parent::__construct( 'GET', $path, $args );
  }

  /**
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    $setting_manager = lib::create( 'business\setting_manager' );
    $relationship_class_name = lib::get_class_name( 'database\relationship' );

    $leaf_subject = $this->get_leaf_subject();
    if( !is_null( $leaf_subject ) )
    {
      // process "selected" mode
      $selected_mode = $this->get_argument( 'select_mode', false );
      if( $selected_mode )
      {
        if( $relationship_class_name::MANY_TO_MANY !== $this->get_leaf_parent_relationship() )
        { // must have table1/<id>/table2 where table1 N-to-N table2
          $this->status->set_code( 400 );
        }
        else
        {
          // create a sub-query identifying selected records
          $parent_record = $this->get_parent_record();
          $table_name = $parent_record::get_joining_table_name( $leaf_subject );
          $select = lib::create( 'database\select' );
          $select->from( $table_name );
          $select->add_column( 'COUNT(*)', 'total', false );
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( sprintf( '%s_id', $parent_record::get_table_name() ), '=', $parent_record->id );
          $modifier->where( sprintf( '%s_id', $leaf_subject ), '=', sprintf( '%s.id', $leaf_subject ), false );
          $sub_query = sprintf( '( %s %s )', $select->get_sql(), $modifier->get_sql() );
          $this->select->add_column( $sub_query, 'selected', false ); 
        }
      }
    }

    $this->modifier->limit( $setting_manager->get_setting( 'db', 'query_limit' ) );
  }

  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    // get the list of the LAST collection
    $leaf_subject = $this->get_leaf_subject();
    if( !is_null( $leaf_subject ) )
    {
      $record_class_name = lib::get_class_name( sprintf( 'database\%s', $leaf_subject ) );
      $this->headers['Columns'] = $record_class_name::db()->get_column_details( $leaf_subject );
      $this->headers['Limit'] = $this->modifier->get_limit();
      $this->headers['Offset'] = $this->modifier->get_offset();
      $this->headers['Total'] = $this->get_record_count();
      $this->data = $this->get_record_list();
    }
  }

  /**
   * TODO: document
   */
  protected function get_record_count()
  {
    $leaf_subject = $this->get_leaf_subject();
    $parent_record = $this->get_parent_record();
    $record_class_name = lib::get_class_name( sprintf( 'database\%s', $leaf_subject ) );
    $parent_record_method = sprintf( 'get_%s_count', $leaf_subject );
    $modifier = clone $this->modifier;

    return is_null( $parent_record ) || $this->get_argument( 'select_mode', false ) ?
           $record_class_name::count( $modifier ) :
           $parent_record->$parent_record_method( $modifier );
  }

  /**
   * TODO: document
   */
  protected function get_record_list()
  {
    $leaf_subject = $this->get_leaf_subject();
    $parent_record = $this->get_parent_record();
    $record_class_name = lib::get_class_name( sprintf( 'database\%s', $leaf_subject ) );
    $parent_record_method = sprintf( 'get_%s_list', $leaf_subject );
    $modifier = clone $this->modifier;

    return is_null( $parent_record ) || $this->get_argument( 'select_mode', false ) ?
           $record_class_name::select( $this->select, $modifier ) :
           $parent_record->$parent_record_method( $this->select, $modifier );
  }
}
