<?php
/**
 * data_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\business;
use cenozo\lib, cenozo\log;

/**
 * A manager to provide various data to external sources based on string-based keys
 */
class data_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\argument
   * @access protected
   */
  protected function __construct()
  {
    // nothing required
  }

  /**
   * Returns whether the key is a valid value type
   * 
   * This is a way of testing whether the get_value() method will return a value for the
   * given key.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $key The key string defining which data to test
   * @return boolean
   * @access public
   */
  public function is_value( $key )
  {
    // split the key into table/column parts
    $parts = explode( '.', $key );
    return 2 <= count( $parts );
  }

  /**
   * Get generic data
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $key The key string defining which data to return
   * @return string
   * @access public
   */
  public function get_value( $key )
  {
    // parse the key
    $parts = $this->parse_key( $key );
    $subject = $parts[0];

    $value = NULL;
    if( 'constant' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $value = $parts[1];
    }
    else if( 'cookie' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $variable = $parts[1];

      // cookie.<name>
      if( array_key_exists( $variable, $_COOKIE ) ) $value = $_COOKIE[$variable];
    }
    else if( 'role' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $column = $parts[1];

      // role.<column>
      $db_role = lib::create( 'business\session' )->get_role();
      if( !$db_role->column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $value = $db_role->$column;
    }
    else if( 'site' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $column = $parts[1];

      // site.<column>
      $db_site = lib::create( 'business\session' )->get_site();
      if( !$db_site->column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $value = $db_site->$column;
    }
    else if( 'user' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $column = $parts[1];

      // user.<column>
      $db_user = lib::create( 'business\session' )->get_user();
      if( !$db_user->column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $value = $db_user->$column;
    }

    return $value;
  }

  /**
   * Get participant-based data
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant
   * @param string $key The key string defining which data to return
   * @return string
   * @access public
   */
  public function get_participant_value( $db_participant, $key )
  {
    $util_class_name = lib::get_class_name( 'util' );

    // make sure the db_participant object is valid
    if( is_null( $db_participant ) ||
        false === strpos( get_class( $db_participant ), 'database\participant' ) )
      throw lib::create( 'exception\argument', 'db_participant', $db_participant, __METHOD__ );

    // parse the key
    $parts = $this->parse_key( $key, true );
    $subject = $parts[0];

    $value = NULL;
    if( 'alternate' == $subject ||
        'informant' == $subject ||
        'proxy' == $subject )
    {
      if( 'count()' == $parts[1] )
      {
        // participant.<alternate|informant|proxy>.count() or <alternate|informant|proxy>.count()
        $alternate_mod = lib::create( 'database\modifier' );
        $alternate_mod->where( $subject, '=', true );
        $value = $db_participant->get_alternate_count( $alternate_mod );
      }
    }
    else if( 'address' == $subject ||
        'primary_address' == $subject ||
        'first_address' == $subject )
    {
      if( 'count()' == $parts[1] )
      {
        // participant.address.count() or address.count()
        $value = $db_participant->get_address_count();
      }
      else
      {
        $db_address = NULL;
        if( 'address' == $subject )
        {
          // participant.address.<n>.<column> or address.<n>.<column>
          if( 3 != count( $parts ) )
            throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

          // get the rank and make sure it's a number
          $rank = $parts[1];
          if( !$util_class_name::string_matches_int( $rank ) )
            throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
          $column = $parts[2];

          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'rank', '=', $rank );
          $address_list = $db_participant->get_address_object_list( $modifier );
          if( 1 == count( $address_list ) ) $db_address = current( $address_list );
        }
        else if( 'primary_address' == $subject )
        {
          // participant.primary_address.<column> or primary_address.<column>
          if( 2 != count( $parts ) )
            throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
          $column = $parts[1];

          $db_address = $db_participant->get_primary_address();
        }
        else if( 'first_address' == $subject )
        {
          // participant.first_address.<column> or first_address.<column>
          if( 2 != count( $parts ) )
            throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
          $column = $parts[1];

          $db_address = $db_participant->get_first_address();
        }
        else throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

        if( !is_null( $db_address ) )
        {
          // return column (exception: street = address1 + address2)
          if( 'street' == $column )
          {
            $value = $db_address->address1;
            if( !is_null( $db_address->address2 ) ) $value .= ' '.$db_address->address2;
          }
          else
          {
            if( !$db_address->column_exists( $column ) )
              throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
            $value = $db_address->$column;
          }
        }
      }
    }
    else if( 'cohort' == $subject )
    {
      // participant.cohort.<column> or cohort.<column>
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $db_cohort = $db_participant->get_cohort();
      $column = $parts[1];
      if( !$db_cohort->column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $value = $db_cohort->$column;
    }
    else if( 'collection' == $subject )
    {
      // participant.collection.<name> or collection.<name>
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $collection_name = $parts[1];
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'collection.name', '=', $collection_name );
      $value = 0 < $db_participant->get_collection_count( $modifier ) ? 1 : 0;
    }
    else if( 'consent' == $subject ||
             'written_consent' == $subject ||
             'last_consent' == $subject ||
             'last_written_consent' == $subject )
    {
      $consent_type_class_name = lib::get_class_name( 'database\consent_type' );
      if( 2 >= count( $parts ) ) throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $db_consent_type = $consent_type_class_name::get_unique_record( 'name', $parts[1] );
      if( is_null( $db_consent_type ) ) throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $consent_mod = lib::create( 'database\modifier' );
      $consent_mod->where( 'consent_type_id', '=', $db_consent_type->id );

      if( 'count()' == $parts[2] )
      {
        if( 'consent' == $subject )
        { // participant.consent.<type>.count() or consent.<type>.count()
          $value = $db_participant->get_consent_count();
        }
        else if( 'written_consent' == $subject )
        { // participant.written_consent.<type>.count() or written_consent.count()
          $consent_mod->where( 'written', '=', true );
          $value = $db_participant->get_consent_count( $consent_mod );
        }
        else throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      }
      else
      {
        $db_consent = NULL;
        $default = NULL;

        if( 4 < count( $parts ) && 'default' == $parts[ count( $parts )-2 ] )
        {
          $default = array_pop( $parts );
          array_pop( $parts );
        }

        if( 'consent' == $subject )
        {
          // participant.consent.<type>.<n>.<column> or consent.<type>.<n>.<column>
          if( 4 != count( $parts ) ) throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

          // get the rank and make sure it's a number
          $rank = $parts[2];
          if( !$util_class_name::string_matches_int( $rank ) )
            throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
          $column = $parts[3];

          $consent_mod->order( 'date' );
          $consent_mod->limit( 1 );
          $consent_mod->offset( $rank - 1 );
          $consent_list = $db_participant->get_consent_object_list( $consent_mod );
          if( 1 == count( $consent_list ) ) $db_consent = current( $consent_list );
        }
        else if( 'last_consent' == $subject )
        {
          // participant.last_consent.<type>.<column> or last_consent.<type>.<column>
          if( 3 != count( $parts ) ) throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

          $column = $parts[2];
          $db_consent = $db_participant->get_last_consent( $db_consent_type );
        }
        else if( 'last_written_consent' == $subject )
        {
          // participant.last_written_consent.<type>.<column> or last_written_consent.<type>.<column>
          if( 3 != count( $parts ) ) throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

          $column = $parts[2];
          $db_consent = $db_participant->get_last_written_consent( $db_consent_type );
        }
        else throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

        if( !is_null( $db_consent ) )
        {
          // return column
          if( !$db_consent->column_exists( $column ) )
            throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
          $value = $db_consent->$column;
        }
        else if( !is_null( $default ) )
        {
          $value = $default;
        }
      }
    }
    else if( 'event' == $subject )
    {
      $event_class_name = lib::get_class_name( 'database\event' );

      if( 5 < count( $parts ) && 'default' == $parts[ count( $parts )-2 ] )
      {
        $value = array_pop( $parts );
        array_pop( $parts );
      }

      // participant.event.<type>.<column>.<first|last> or event.<type>.<column>.<first|last>
      if( !( 3 <= count( $parts ) && count( $parts ) <= 4 ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $type = $parts[1];
      $column = $parts[2];
      $last = false;
      if( 4 == count( $parts ) )
      {
        if( 'first' != $parts[3] && 'last' != $parts[3] )
          throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
        $last = 'last' == $parts[3];
      }

      if( !$event_class_name::column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $event_mod = lib::create( 'database\modifier' );
      $event_mod->where( 'event_type.name', '=', $type );
      $event_mod->order( 'datetime', $last ); // last means order by descending
      $event_mod->limit( 1 );
      $event_list = $db_participant->get_event_list( NULL, $event_mod );
      if( 0 < count( $event_list ) )
      {
        if( array_key_exists( $column, $event_list[0] ) )
          throw lib::create( 'exception\argument', 'column', $column, __METHOD__ );
        $value = $event_list[0][$column];
      }
    }
    else if( 'hin' == $subject )
    {
      $hin_class_name = lib::get_class_name( 'database\hin' );
      $db_hin = $hin_class_name::get_unique_record( 'participant_id', $db_participant->id );

      // note: values from this table are restricted
      $column = $parts[1];
      if( 'code_exists' == $column )
      {
        // participant.hin.code_exists (true/false) or hin.code_exists (true/false)
        $value = !( is_null( $db_hin ) || is_null( $db_hin->code ) ) ? 1 : 0;
      }
      else throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
    }
    else if( 'limesurvey' == $subject )
    {
      $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
      $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

      // participant.limesurvey.<sid>.<question_title>
      $parts = static::parse_key( $key, true );

      if( 3 != count( $parts ) ) throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $sid = $parts[1];
      $q_title = $parts[2];

      // get this participant's survey for the given sid
      $old_survey_sid = $survey_class_name::get_sid();
      $survey_class_name::set_sid( $sid );
      $old_tokens_sid = $tokens_class_name::get_sid();
      $tokens_class_name::set_sid( $sid );

      $survey_mod = lib::create( 'database\modifier' );
      $tokens_class_name::where_token( $survey_mod, $db_participant, false );
      $survey_mod->order_desc( 'datestamp' );
      $survey_list = $survey_class_name::select_objects( $survey_mod );
      if( 0 < count( $survey_list ) )
      {
        $db_survey = current( $survey_list );
        $value = $db_survey->get_response( $q_title );
      }

      $survey_class_name::set_sid( $old_survey_sid );
      $tokens_class_name::set_sid( $old_tokens_sid );
    }
    else if( 'opal' == $subject )
    {
      if( !( 4 == count( $parts ) || 5 == count( $parts ) ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $datasource = $parts[1];
      $table = $parts[2];
      $variable = $parts[3];

      $opal_manager = lib::create( 'business\opal_manager' );

      if( $opal_manager->get_enabled() )
      {
        try
        {
          if( 5 == count( $parts ) )
          {
            if( 'label' == $parts[4] )
            {
              // participant.opal.<datasource>.<table>.<variable>.label (returns label) or
              // opal.<datasource>.<table>.<variable>.label (returns label)
              $value = $opal_manager->get_label(
                $datasource, $table, $variable, $value, $db_participant->get_language() );
            }
            else if( 'cache' == $parts[4] )
            {
              // participant.opal.<datasource>.<table>.<variable>.cache (caches data)
              // opal.<datasource>.<table>.<variable>.cache (caches data)

              $variable_cache_class_name = lib::get_class_name( 'database\variable_cache' );
              $variable_cache_class_name::remove_expired(); // make sure to clean-up before searching

              // get the data from the cache, or if it is missing then cache them
              $variable_cache_sel = lib::create( 'database\select' );
              $variable_cache_sel->add_column( 'value' );
              $variable_cache_sel->from( 'variable_cache' );
              $variable_cache_mod = lib::create( 'database\modifier' );
              $variable_cache_mod->where( 'variable', '=', $variable );
              $rows = $db_participant->get_variable_cache_list( $variable_cache_sel, $variable_cache_mod );
              if( 0 == count( $rows ) )
              {
                $values = $opal_manager->get_values( $datasource, $table, $db_participant );
                $variable_cache_class_name::overwrite_values( $db_participant, $values );
                $value = $values[$variable];
              }
              else
              {
                $value = $rows[0]['value'];
              }
            }
            else throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
          }
          else
          {
            // participant.opal.<datasource>.<table>.<variable> (returns value) or
            // opal.<datasource>.<table>.<variable> (returns value)
            $value = $opal_manager->get_value( $datasource, $table, $db_participant, $variable );
          }
        }
        catch( \cenozo\exception\base_exception $e )
        {
          // ignore argument exceptions (data not found in Opal) and report the rest
          if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
        }
      }
    }
    else if( 'participant' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $column = $parts[1];
      if( 'age()' == $column )
      {
        // participant.participant.age() or participant.age()
        $value = is_null( $db_participant->date_of_birth )
               ? ''
               : $util_class_name::get_interval( $db_participant->date_of_birth )->y;
      }
      else
      {
        // participant.participant.<column> or participant.<column>
        if( !$db_participant->column_exists( $column ) )
          throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
        $value = $db_participant->$column;
      }
    }
    else if( 'phone' == $subject )
    {
      if( 'count()' == $parts[1] )
      {
        // participant.phone.count() or phone.count()
        $value = $db_participant->get_phone_count();
      }
      else
      {
        // participant.phone.<n>.<column> or phone.<n>.<column>
        if( 3 != count( $parts ) )
          throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

        // get the rank and make sure it's a number
        $rank = $parts[1];
        if( !$util_class_name::string_matches_int( $rank ) )
          throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
        $column = $parts[2];

        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'rank', '=', $rank );
        $phone_list = $db_participant->get_phone_list( NULL, $modifier );

        if( 0 < count( $phone_list ) )
        {
          if( array_key_exists( $column, $phone_list[0] ) )
            throw lib::create( 'exception\argument', 'column', $column, __METHOD__ );
          $value = $phone_list[0][$column];
        }
      }
    }
    else if( 'source' == $subject )
    {
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $column = $parts[1];

      // participant.source.<column> or source.<column>
      $db_source = $db_participant->get_source();
      if( !$db_source->column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $value = $db_source->$column;
    }

    return $value;
  }

  /**
   * Parse the key used to identify which data value to return
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $key The key string defining which data to return
   * @return array
   * @access protected
   */
  protected function parse_key( $key, $remove_participant = false )
  {
    // two consecutive periods (..) is an escaped .
    $key = str_replace( '..', chr( 37 ), $key );

    // split the key into table/column parts then replace the escaped char back into a .
    $parts = explode( '.', $key );
    if( 2 > count( $parts ) )
      throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
    foreach( $parts as $index => $part ) $parts[$index] = str_replace( chr( 37 ), '.', $part );

    // All keys used to return participant values may be prepended with "participant."
    // If $remove_participant is true then remove it, but only if there are more than 2 parts
    // to the key
    if( $remove_participant && 'participant' == $parts[0] && 2 < count( $parts ) )
      array_shift( $parts );

    return $parts;
  }
}
