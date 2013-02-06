<?php
/**
 * base_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\ui\push;
use cenozo\lib, cenozo\log;

/**
 * Base class for all record "edit" push operations.
 */
abstract class base_edit extends base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The widget's subject.
   * @param array $args Push arguments
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'edit', $args );
  }
  
  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns', array() );
    
    // check for time range validity, if necessary
    if( array_key_exists( 'start_time', $columns ) ||
        array_key_exists( 'end_time', $columns ) )
    {
      $start_value = array_key_exists( 'start_time', $columns )
                   ? $columns['start_time']
                   : substr( $this->get_record()->start_time, 0, -3 );
      $end_value = array_key_exists( 'end_time', $columns )
                 ? $columns['end_time']
                 : substr( $this->get_record()->end_time, 0, -3 );

      if( strtotime( $start_value ) >= strtotime( $end_value ) )
      {
        throw lib::create( 'exception\notice',
          sprintf( 'Start and end times (%s to %s) are not valid.',
                   $start_value,
                   $end_value ),
          __METHOD__ );
      }   
    } 
    else if( array_key_exists( 'start_datetime', $columns ) ||
             array_key_exists( 'end_datetime', $columns ) )
    {
      $start_value = array_key_exists( 'start_datetime', $columns )
                   ? $columns['start_datetime']
                   : substr( $this->get_record()->start_datetime, 0, -3 );
      $end_value = array_key_exists( 'end_datetime', $columns )
                 ? $columns['end_datetime']
                 : substr( $this->get_record()->end_datetime, 0, -3 );

      if( strtotime( $start_value ) >= strtotime( $end_value ) )
      {
        throw lib::create( 'exception\notice',
          sprintf( 'Start and end date-times (%s to %s) are not valid.',
                   $start_value,
                   $end_value ),
          __METHOD__ );
      }   
    }
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $columns = $this->get_argument( 'columns', array() );
    $record = $this->get_record();
    $record_class_name = lib::get_class_name( 'database\\'.$record->get_table_name() );
    
    // set record column values if column exists in record
    $edit = false;
    foreach( $columns as $column => $value )
    {
      if( $record_class_name::column_exists( $column, true ) )
      {
        $record->$column = $value;
        $edit = true;
      }
    }
    
    if( $edit )
    { // only bother to save the record if at least one column has been edited
      try
      {
        $record->save();
      }
      catch( \cenozo\exception\database $e )
      { // help describe exceptions to the user
        if( $e->is_duplicate_entry() )
        {
          reset( $columns );
          throw lib::create( 'exception\notice',
            1 == count( $columns ) &&
            '_id' != substr( key( $columns ), -3 )
            ? sprintf( 'Unable to set %s to "%s" because that value is already being used.',
                       key( $columns ),
                       current( $columns ) )
            : sprintf( 'Unable to modify the %s because it conflicts with another pre-existing %s.',
                       $this->get_subject(),
                       $this->get_subject() ),
            __METHOD__, $e );
        }

        throw $e;
      }
    }
  }
}
