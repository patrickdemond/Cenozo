<?php
/**
 * participant_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\ui\push;
use cenozo\lib, cenozo\log;

/**
 * push: participant edit
 *
 * Edit a participant.
 */
class participant_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
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

    $service_class_name = lib::get_class_name( 'database\service' );
    $columns = $this->get_argument( 'columns', array() );
    
    // look for preferred site column(s)
    foreach( $service_class_name::select() as $db_service )
    {
      $column_name = $db_service->name.'_site_id';

      if( array_key_exists( $column_name, $columns ) )
      {
        $site_id = $columns[$column_name];
        $db_site = $site_id ? lib::create( 'database\site', $site_id ) : NULL;
        $this->get_record()->set_preferred_site( $db_service, $db_site );
      }
    }
  }
}
