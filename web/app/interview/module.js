define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'interview', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'participant',
        column: 'participant.uid'
      }
    },
    name: {
      singular: 'interview',
      plural: 'interviews',
      possessive: 'interview\'s'
    },
    columnList: {
      uid: {
        column: 'participant.uid',
        title: 'UID'
      },
      site: {
        column: 'site.name',
        title: 'Credited Site'
      },
      start_datetime: {
        title: 'Start',
        type: 'datetimesecond'
      },
      end_datetime: {
        title: 'End',
        type: 'datetimesecond'
      }
    },
    defaultOrder: {
      column: 'start_datetime',
      reverse: true
    }
  } );

  module.addInputGroup( '', {
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      isConstant: true
    },
    site_id: {
      title: 'Credited Site',
      type: 'enum',
      help: 'This determines which site is credited with the completed interview.',
      isConstant: function( $state, model ) { return !model.isAdministrator(); }
    },
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      max: 'end_datetime',
      isConstant: function( $state, model ) { return !model.isAdministrator(); },
      help: 'When the first call from the first assignment was made for this interview.'
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      min: 'start_datetime',
      max: 'now',
      isConstant: function( $state, model ) { return !model.isAdministrator(); },
      help: 'Will remain blank until the questionnaire is finished.'
    },
    note: {
      column: 'interview.note',
      title: 'Note',
      type: 'text'
    }
  } );

  if( angular.isDefined( cenozoApp.module( 'participant' ).actions.notes ) ) {
    module.addExtraOperation( 'view', {
      title: 'Notes',
      operation: function( $state, model ) {
        $state.go( 'participant.notes', { identifier: 'uid=' + model.viewModel.record.participant } );
      }
    } );
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewList', [
    'CnInterviewModelFactory',
    function( CnInterviewModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewView', [
    'CnInterviewModelFactory',
    function( CnInterviewModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel, root ) { CnBaseViewFactory.construct( this, parentModel, root, 'assignment' ); }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    'CnSession', 'CnHttpFactory', 'CnModalMessageFactory',
    function( CnBaseModelFactory, CnInterviewListFactory, CnInterviewViewFactory,
             CnSession,  CnHttpFactory, CnModalMessageFactory ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this, root );

        this.isAdministrator = function() { return 'administrator' == CnSession.role.name; }

        // Adding an interview is special, instead of transitioning to an add dialog a command can be
        // sent to the server to directly add a new interview
        this.transitionToAddState = function() {
          return CnHttpFactory.instance( {
            path: self.getServiceCollectionPath(),
            data: {}, // no record required, the server will fill in all necessary values
            onError: function( response ) {
              if( 409 == response.status ) {
                // 409 when we can't add a new interview (explanation will be provided
                CnModalMessageFactory.instance( {
                  title: 'Unable To Add Interview',
                  message: response.data +
                           ' This is likely caused by the list being out of date so it will now be refreshed.',
                  error: true
                } ).show().then( function() {
                  self.listModel.onList( true );
                } );
              } else CnModalMessageFactory.httpError( response );
            }
          } ).post().then( function() {
            self.listModel.onList( true );
          } );
        };

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return CnHttpFactory.instance( {
              path: 'site',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: 'name' }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.site_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.site_id.enumList.push( { value: item.id, name: item.name } );
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
