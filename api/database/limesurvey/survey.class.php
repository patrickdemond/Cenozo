<?php
/**
 * survey.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\database\limesurvey;
use cenozo\lib, cenozo\log;

/**
 * Access to limesurvey's survey_SID tables.
 */
class survey extends sid_record
{
  /**
   * Returns a response to this survey
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $question_code
   * @return string
   * @access public
   */
  public function get_response( $question_code )
  {
    $select = lib::create( 'database\select' );
    $select->add_column( 'gid' );
    $select->add_column( 'qid' );
    $select->from( 'questions' );

    // the questions table has more than one column in its primary key so custom sql is needed
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'sid', '=', static::get_sid() );
    $modifier->where( 'title', '=', $question_code );
    $modifier->group( 'sid' );
    $modifier->group( 'gid' );
    $modifier->group( 'qid' );
    $sql = sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() );

    $row = static::db()->get_row( $sql );
    if( 0 == count( $row ) )
      throw lib::create( 'exception\runtime', 'Question code not found in survey.', __METHOD__ );

    $column_name = sprintf( '%sX%sX%s', static::get_sid(), $row['gid'], $row['qid'] );
    return $this->$column_name;
  }

  /**
   * Returns a participant's response to a particular question.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $question_code
   * @param database\modifier $modifier A modifier applied to the survey selection
   *                          This usually has: token LIKE 'A123456_%' or something similar
   * @return string
   * @access public
   */
  public static function get_responses( $question_code, $modifier = NULL )
  {
    $question_sel = lib::create( 'database\select' );
    $question_sel->add_column( 'gid' );
    $question_sel->add_column( 'qid' );
    $question_sel->from( 'questions' );

    // the questions table has more than one column in its primary key so custom sql is needed
    $question_mod = lib::create( 'database\modifier' );
    $question_mod->where( 'sid', '=', static::get_sid() );
    $question_mod->where( 'title', '=', $question_code );
    $question_mod->group( 'sid' );
    $question_mod->group( 'gid' );
    $question_mod->group( 'qid' );
    $sql = sprintf( '%s %s', $question_sel->get_sql(), $question_mod->get_sql() );

    $row = static::db()->get_row( $sql );
    if( 0 == count( $row ) )
      throw lib::create( 'exception\runtime', 'Question code not found in survey.', __METHOD__ );

    $select = lib::create( 'database\select' );
    $select->add_column( sprintf( '%sX%sX%s', static::get_sid(), $row['gid'], $row['qid'] ) );
    $select->from( static::get_table_name() );

    $sql = $select->get_sql();
    if( !is_null( $modifier ) ) $sql .= ' '.$modifier->get_sql();
    return static::db()->get_col( $sql );
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'id';
}
