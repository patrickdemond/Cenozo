define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'address', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: [ {
        subject: 'participant',
        column: 'participant.uid'
      }, {
        subject: 'alternate',
        column: 'alternate_id'
      } ]
    },
    name: {
      singular: 'address',
      plural: 'addresses',
      possessive: 'address\'',
      pluralPossessive: 'addresses\'',
      friendlyColumn: 'rank'
    },
    columnList: {
      rank: {
        title: 'Rank',
        type: 'rank'
      },
      city: {
        title: 'City'
      },
      region: {
        title: 'Region'
      },
      active: {
        column: 'address.active',
        title: 'Active',
        type: 'boolean'
      },
      available: {
        title: 'Available',
        type: 'boolean',
        help: 'Whether the address is active in the current month.'
      }
    },
    defaultOrder: {
      column: 'rank',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    active: {
      title: 'Active',
      type: 'boolean'
    },
    rank: {
      title: 'Rank',
      type: 'rank'
    },
    international: {
      title: 'International',
      type: 'boolean',
      help: 'Cannot be changed once the address has been created.',
      constant: 'view'
    },
    address1: {
      title: 'Address Line 1',
      type: 'string'
    },
    address2: {
      title: 'Address Line 2',
      type: 'string'
    },
    city: {
      title: 'City',
      type: 'string'
    },
    region_id: {
      title: 'Region',
      type: 'enum',
      exclude: 'add',
      constant: true,
      help: 'The region cannot be changed directly, instead it is automatically updated based on the postcode.'
    },
    international_region: {
      title: 'Region',
      type: 'string',
      exclude: true,
      help: 'International regions are unrestricted and are not automatically set by the postcode.'
    },
    international_country: {
      title: 'Country',
      type: 'string',
      exclude: true
    },
    postcode: {
      title: 'Postcode',
      type: 'string',
      help: 'Non-international postal codes must be in "A1A 1A1" format, zip codes in "01234" format.'
    },
    timezone_offset: {
      title: 'Timezone Offset',
      type: 'string',
      format: 'float',
      exclude: 'add',
      help: 'The number of hours difference between the address\' timezone and UTC.'
    },
    daylight_savings: {
      title: 'Daylight Savings',
      type: 'boolean',
      exclude: 'add',
      help: 'Whether the address observes daylight savings.'
    },
    note: {
      title: 'Note',
      type: 'text'
    },
    months: {
      title: 'Active Months',
      type: 'months'
    }
  } );

  module.addExtraOperation( 'view', {
    title: 'Use Timezone',
    operation: function( $state, model ) {
      model.viewModel.onViewPromise.then( function() { model.viewModel.useTimezone(); } );
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAddressAdd', [
    'CnAddressModelFactory', '$timeout',
    function( CnAddressModelFactory, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAddressModelFactory.root;
        },
        link: function( scope ) {
          // add/remove inputs based on whether international is set to true or false
          $timeout( function() {
            var mainInputGroup = scope.model.module.inputGroupList.findByProperty( 'title', '' );
            var cnRecordAdd = cenozo.findChildDirectiveScope( scope, 'cnRecordAdd' );
            var checkFunction = cnRecordAdd.check;
            cnRecordAdd.check = function( property ) {
              // run the original check function first
              checkFunction( property );

              if( 'international' == property ) {
                mainInputGroup.inputList.international_region.exclude = !cnRecordAdd.record.international;
                mainInputGroup.inputList.international_country.exclude = !cnRecordAdd.record.international;
              }
            };
          }, 500 );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAddressList', [
    'CnAddressModelFactory',
    function( CnAddressModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAddressModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAddressView', [
    'CnAddressModelFactory',
    function( CnAddressModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAddressModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAddressAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) {
        CnBaseAddFactory.construct( this, parentModel );

        // make sure that columns are showing as they should when we first create a new address record
        this.onNew = function( record ) {
          return this.$$onNew( record ).then( function() {
            var mainInputGroup = parentModel.module.inputGroupList.findByProperty( 'title', '' );
            if( mainInputGroup ) {
              mainInputGroup.inputList.international_region.exclude = true;
              mainInputGroup.inputList.international_country.exclude = true;
            }
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAddressListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAddressViewFactory', [
    'CnBaseViewFactory', 'CnSession', '$state', '$window',
    function( CnBaseViewFactory, CnSession, $state, $window ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );
        this.onViewPromise = null;

        this.useTimezone = function() {
          CnSession.setTimezone( { 'address_id': this.record.id } ).then( function() {
            $state.go( 'self.wait' ).then( function() { $window.location.reload(); } );
          } );
        };

        // once we have loaded the record show/hide the international inputs
        this.onView = function() {
          this.onViewPromise = this.$$onView().then( function() {
            var mainInputGroup = parentModel.module.inputGroupList.findByProperty( 'title', '' );
            if( mainInputGroup ) {
              mainInputGroup.inputList.region_id.exclude = self.record.international ? true : 'add';
              mainInputGroup.inputList.international_region.exclude = !self.record.international;
              mainInputGroup.inputList.international_country.exclude = !self.record.international;
            }
          } );

          return this.onViewPromise;
        };

        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() { return self.onView(); } );
        };
      };
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAddressModelFactory', [
    'CnBaseModelFactory', 'CnAddressListFactory', 'CnAddressAddFactory', 'CnAddressViewFactory',
    'CnHttpFactory',
    function( CnBaseModelFactory, CnAddressListFactory, CnAddressAddFactory, CnAddressViewFactory,
              CnHttpFactory ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAddressAddFactory.instance( this );
        this.listModel = CnAddressListFactory.instance( this );
        this.viewModel = CnAddressViewFactory.instance( this, root );

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return CnHttpFactory.instance( {
              path: 'region',
              data: {
                select: {
                  column: [
                    'id',
                    'country',
                    { column: 'CONCAT_WS( ", ", name, country )', alias: 'name', table_prefix: false }
                  ]
                },
                modifier: { order: ['country','name'], limit: 100 }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.region_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.region_id.enumList.push( {
                  value: item.id,
                  country: item.country,
                  name: item.name
                } );
              } );
            } );
          } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
