'use strict';

try { var cenozo = angular.module( 'cenozo' ); }
catch( err ) { var cenozo = angular.module( 'cenozo', [] ); }

/* ######################################################################################################## */
cenozo.filter( 'cnComparator', function() {
  return function( input ) {
    if( '<=>' == input ) return '=';
    if( '<>' == input ) return '\u2260';
    if( 'like' == input ) return '\u2248';
    if( 'not like' == input ) return '\u2249';
    if( '>' == input ) return input;
    if( '>=' == input ) return '\u2265';
    if( '<' == input ) return input;
    if( '<=' == input ) return '\u2264';
  };
} );

/* ######################################################################################################## */
cenozo.filter( 'cnCheckmark', function() {
  return function( input ) {
    if( "boolean" != typeof input ) input = 0 != input;
    return input ? '\u2714' : '\u2718';
  };
} );

/* ######################################################################################################## */
cenozo.filter( 'cnCrop', function() {
  return function( string, max ) {
    return max < string.length ? string.substring( 0, max-2 ) + '\u2026' : string;
  };
} );

/* ######################################################################################################## */
cenozo.filter( 'cnMetaFilter', [
  '$filter',
  function( $filter ) {
    return function( value, filterStr ) {
      if( angular.isDefined( filterStr ) && 0 < filterStr.length ) {
        // convert string into array deliminating by : (but not inside double quotes)
        var args = [].concat.apply( [], filterStr.split( '"' ).map(
          function( v, i ) {
            return i%2 ? v : v.split( ':' )
          }
        ) ).filter( Boolean );

        var filter = $filter( args.shift() );
        args.unshift( value );
        return filter.apply( null, args );
      } else return value;
    };
  }
] );

/* ######################################################################################################## */
cenozo.filter( 'cnMomentDate', [
  'CnAppSingleton',
  function( CnAppSingleton ) {
    return function( input, format ) {
      var output;
      if( angular.isUndefined( input ) || null === input ) {
        output = '(none)';
      } else {
        if( 'object' !== typeof input || angular.isUndefined( input.format ) ) input = moment( input );
        output = input.tz( CnAppSingleton.site.timezone ).format( format );
      }
      return output;
    };
  }
] );

/* ######################################################################################################## */
cenozo.filter( 'cnOrdinal', function() {
  return function( number ) {
    var postfixList = [ 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' ];
    var modulo = number % 100;
    if( 11 <= modulo && modulo <= 13 ) return number + 'th';
    return number + postfixList[number % 10];
  }
} );

/* ######################################################################################################## */
cenozo.filter( 'cnPercent', function() {
  return function( input ) {
    return input + "%";
  };
} );

/* ######################################################################################################## */
cenozo.filter( 'cnUCWords', function() {
  return function( input ) {
    if( angular.isDefined( input ) )
      input = input.replace( /(?:^|\s)\S/g, function( a ) { return angular.uppercase( a ); } );
    return input;
  };
} );

/* ######################################################################################################## */
cenozo.filter( 'cnYesNo', function() {
  return function( input ) {
    if( "boolean" != typeof input ) input = 0 != input;
    return input ? 'yes' : 'no';
  };
} );
