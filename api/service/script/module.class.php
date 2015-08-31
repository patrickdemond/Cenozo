<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\service\script;
use cenozo\lib, cenozo\log;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // add the total number of phases
    if( $select->has_column( 'phase_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'phase' );
      $join_sel->add_column( 'script_id' );
      $join_sel->add_column( 'COUNT( * )', 'phase_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->group( 'script_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS script_join_phase', $join_sel->get_sql(), $join_mod->get_sql() ),
        'script.id',
        'script_join_phase.script_id' );
      $select->add_column( 'IFNULL( phase_count, 0 )', 'phase_count', false );
    }
  }
}
