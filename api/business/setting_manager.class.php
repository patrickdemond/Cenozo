<?php
/**
 * setting_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace cenozo\business;
use cenozo\lib, cenozo\log;

/**
 * Manages software settings
 */
class setting_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\argument
   * @access protected
   */
  protected function __construct( $arguments )
  {
    $args = $arguments[0];
    $args = is_array( $arguments[0] ) ? $arguments[0] : array();

    // copy the setting one category at a time
    $this->read_settings( 'db', $args );
    $this->read_settings( 'general', $args );
    $this->read_settings( 'ldap', $args );
    $this->read_settings( 'opal', $args );
    $this->read_settings( 'path', $args );
    $this->read_settings( 'survey_db', $args );
    $this->read_settings( 'url', $args );
    $this->read_settings( 'voip', $args );
  }

  /**
   * Read settings into the manager (should be called in the constructor only)
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $category The category the setting belongs to.
   * @param array $arguments The associative array containing all settings
   * @throws exception\argument
   * @access protected
   */
  protected function read_settings( $category, $arguments )
  {
    if( !array_key_exists( $category, $arguments ) )
      throw lib::create( 'exception\argument',
        'arguments['.$category.']', NULL, __METHOD__ );

    $this->setting_list[$category] = $arguments[$category];
  }

  /**
   * Get a setting's value
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $category The category the setting belongs to.
   * @param string $name The name of the setting.
   * @access public
   */
  public function get_setting( $category, $name )
  {
    // first check for the setting in static settings
    if( array_key_exists( $category, $this->setting_list ) &&
        array_key_exists( $name, $this->setting_list[$category] ) )
    {
      return $this->setting_list[$category][$name];
    }

    // if we get here then the setting doesn't exist
    log::err( "Tried getting value for setting [$category][$name] which doesn't exist." );

    return NULL;
  }

  /**
   * An array which holds static (non database) settings
   * @var array( mixed )
   * @access private
   */
  protected $setting_list = array();
}
