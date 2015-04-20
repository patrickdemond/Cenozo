define( [
  cnCenozoUrl + '/app/access/module.js'
], function( module ) {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAccessAddFactory', [
    'CnBaseAddFactory', 'CnHttpFactory',
    function( CnBaseAddFactory, CnHttpFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = module.subject;
        params.name = module.name;
        params.inputList = module.inputList;
        return CnBaseAddFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAccessListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = module.subject;
        params.name = module.name;
        params.columnList = module.columnList;
        params.order = module.defaultOrder;
        return CnBaseListFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAccessViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = module.subject;
        params.name = module.name;
        params.inputList = module.inputList;
        return CnBaseViewFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAccessSingleton', [
    'CnBaseSingletonFactory', 'CnAccessListFactory', 'CnAccessAddFactory', 'CnAccessViewFactory',
    function( CnBaseSingletonFactory, CnAccessListFactory, CnAccessAddFactory, CnAccessViewFactory ) {
      return new ( function() {
        this.subject = module.subject;
        CnBaseSingletonFactory.apply( this );
        this.name = module.name;
        this.cnAdd = CnAccessAddFactory.instance( { parentModel: this } );
        this.cnList = CnAccessListFactory.instance( { parentModel: this } );
        this.cnView = CnAccessViewFactory.instance( { parentModel: this } );

        this.cnList.enableAdd( true );
        this.cnList.enableDelete( true );
        this.cnList.enableView( true );

        // populate the foreign-key enumerations
        var thisRef = this;
        /* TODONEXT: need a way to select user/role/site (long lists) and make sure pre-selection works
        this.promise.then( function() {
          CnHttpFactory.instance( {
            path: 'user',
            data: {
              select: { column: [ 'id', 'name' ] },
              modifier: { order: { lower: false } }
            }
          } ).query().then( function success( response ) {
            thisRef.metadata.user_id.enumList = [];
            for( var i = 0; i < response.data.length; i++ ) {
              thisRef.metadata.user_id.enumList.push( {
                value: response.data[i].id,
                name: response.data[i].lower + ' to ' + response.data[i].upper
              } );
            }
          } ).then( function() {
            return CnHttpFactory.instance( {
              path: 'region',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: {
                  where: {
                    column: 'country',
                    operator: '=',
                    value: CnAppSingleton.application.country
                  },
                  order: 'name'
                }
              }
            } ).query().then( function success( response ) {
              thisRef.metadata.region_id.enumList = [];
              for( var i = 0; i < response.data.length; i++ ) {
                thisRef.metadata.region_id.enumList.push( {
                  value: response.data[i].id,
                  name: response.data[i].name
                } );
              }
            } );
          } ).then( function() {
            return CnHttpFactory.instance( {
              path: 'application/' + CnAppSingleton.application.id + '/site',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: 'name' }
              }
            } ).query().then( function success( response ) {
              thisRef.metadata.site_id.enumList = [];
              for( var i = 0; i < response.data.length; i++ ) {
                thisRef.metadata.site_id.enumList.push( {
                  value: response.data[i].id,
                  name: response.data[i].name
                } );
              }
            } );
          } ).catch( function exception() { cnFatalError(); } )
        } );
      */
      } );
    }
  ] );

} );
