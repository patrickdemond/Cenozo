define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'report_schedule', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'report_type',
        column: 'report_type.name'
      }
    },
    name: {
      singular: 'report schedule',
      plural: 'report schedules',
      possessive: 'report schedule\'s'
    },
    columnList: {
      report_type: {
        column: 'report_type.name',
        title: 'Report Type'
      },
      user: {
        column: 'user.name',
        title: 'User'
      },
      site: {
        column: 'site.name',
        title: 'Site'
      },
      role: {
        column: 'role.name',
        title: 'Role'
      },
      schedule: {
        title: 'Schedule',
        type: 'string'
      }
    },
    defaultOrder: {
      column: 'schedule',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    user: {
      column: 'user.name',
      title: 'User',
      type: 'string',
      isExcluded: 'add',
      isConstant: true
    },
    site_id: {
      title: 'Site',
      type: 'enum',
      help: 'Which site to run the report under'
    },
    role_id: {
      title: 'Role',
      type: 'enum',
      help: 'Which role to run the report under'
    },
    schedule: {
      title: 'Schedule',
      type: 'enum',
      help: 'How often to run the report'
    },
    format: {
      title: 'Format',
      type: 'enum',
      isConstant: 'view'
    }
  } );

  module.addInputGroup( 'Parameters', { restrict_placeholder: { type: 'hidden' } }, false );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnReportScheduleAdd', [
    'CnReportScheduleModelFactory', 'CnHttpFactory',
    function( CnReportScheduleModelFactory, CnHttpFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnReportScheduleModelFactory.root;
          $scope.loading = true;

          var cnRecordAddScope = null;
          $scope.$on( 'cnRecordAdd ready', function( event, data ) {
            cnRecordAddScope = data;
            cnRecordAddScope.dataArray = {};
            $scope.model.metadata.getPromise().then( function() {
              cnRecordAddScope.dataArray = $scope.model.getDataArray( [], 'add' );
              cnRecordAddScope.dataArray
                              .findByProperty( 'title', 'Parameters' )
                              .inputArray.forEach( function( input ) {
                if( 'date' != input.type && cenozo.isDatetimeType( input.type ) ) {
                  cnRecordAddScope.formattedRecord[input.key] = '(empty)';
                }
              } );
              $scope.loading = false;
            } );
          } );

          // change the heading to the form's title
          CnHttpFactory.instance( {
            path: 'report_type/' + $scope.model.getParentIdentifier().identifier,
            data: { select: { column: [ 'title' ] } }
          } ).get().then( function( response ) {
            $scope.model.addModel.heading = 'Schedule ' + response.data.title + ' Report';
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnReportScheduleList', [
    'CnReportScheduleModelFactory',
    function( CnReportScheduleModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnReportScheduleModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnReportScheduleView', [
    'CnReportScheduleModelFactory', 'CnHttpFactory',
    function( CnReportScheduleModelFactory, CnHttpFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnReportScheduleModelFactory.root;

          var cnRecordViewScope = null;
          $scope.$on( 'cnRecordView ready', function( event, data ) {
            cnRecordViewScope = data;
            $scope.model.metadata.getPromise().then( function() {
              cnRecordViewScope.dataArray = $scope.model.getDataArray( [], 'view' );
            } );
          } );

          $scope.model.viewModel.afterView( function() {
            // change the heading to the form's title
            CnHttpFactory.instance( {
              path: 'report_type/' + $scope.model.getParentIdentifier().identifier,
              data: { select: { column: [ 'title' ] } }
            } ).get().then( function( response ) {
              $scope.model.viewModel.heading = response.data.title + ' Report Schedule';
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnReportScheduleAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        this.onNew = function( record ) {
          return this.$$onNew( record ).then( function() {
            for( var column in self.parentModel.metadata.columnList ) {
              var meta = self.parentModel.metadata.columnList[column];
              if( angular.isDefined( meta.restriction_type ) ) {
                if( 'date' != meta.restriction_type && cenozo.isDatetimeType( meta.restriction_type ) ) {
                  record[column] = null;
                } else if( 'boolean' == meta.restriction_type && meta.required ) {
                  record[column] = true;
                }
              }
            }
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnReportScheduleListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnReportScheduleViewFactory', [
    'CnBaseViewFactory', 'CnHttpFactory', '$q',
    function( CnBaseViewFactory, CnHttpFactory, $q ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // extend onView
        this.onView = function( updateRestrictions ) {
          if( angular.isUndefined( updateRestrictions ) ) updateRestrictions = true;

          if( !updateRestrictions ) var recordBackup = angular.copy( self.record );
          return this.$$onView().then( function() {
            var promise = $q.all();
            if( updateRestrictions ) {
              // get the report_schedule restriction values
              promise = CnHttpFactory.instance( {
                path: 'report_schedule/' + self.record.getIdentifier() + '/report_restriction',
                data: {
                  select: { column: [ 'name', 'value', 'restriction_type' ] },
                  modifier: { order: { rank: false } }
                }
              } ).query().then( function( response ) {
                response.data.forEach( function( restriction ) {
                  var key = 'restrict_' + restriction.name;
                  if( 'table' == restriction.restriction_type ) {
                    self.record[key] = parseInt( restriction.value );
                  } else if( 'boolean' == restriction.restriction_type ) {
                    self.record[key] = '1' == restriction.value;
                  } else {
                    self.record[key] = restriction.value;
                  }

                  // date types must be treated as enums
                  if( 'date' == restriction.restriction_type )
                    restriction.restriction_type = 'enum';

                  self.updateFormattedRecord( key, cenozo.getTypeFromRestriction( restriction ) );
                } );
              } );
            } else {
              for( var column in recordBackup ) {
                if( 'restrict_' == column.substring( 0, 9 ) ) {
                  self.record[column] = recordBackup[column];
                  self.updateFormattedRecord( column, self.parentModel.module.getInput( column ).type );
                }
              }
            }

            return promise.then( function() {
              var parameterData = self.parentModel.module.inputGroupList.findByProperty( 'title', 'Parameters' );
              Object.keys( parameterData.inputList ).filter( function( column ) {
                return 'restrict_' == column.substring( 0, 9 );
              } ).forEach( function( column ) {
                var type = parameterData.inputList[column].type;
                if( angular.isDefined( self.record[column] ) ) {
                  self.updateFormattedRecord( column, type );
                } else if( 'date' != type && cenozo.isDatetimeType( type ) ) {
                  self.formattedRecord[column] = '(empty)';
                } else if( 'boolean' == type ) {
                  self.record[column] = '';
                }
              } );
            } );
          } );
        };
      };
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnReportScheduleModelFactory', [
    'CnBaseModelFactory',
    'CnReportScheduleAddFactory', 'CnReportScheduleListFactory', 'CnReportScheduleViewFactory',
    'CnHttpFactory', '$q', '$timeout',
    function( CnBaseModelFactory,
              CnReportScheduleAddFactory, CnReportScheduleListFactory, CnReportScheduleViewFactory,
              CnHttpFactory, $q, $timeout ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnReportScheduleAddFactory.instance( this );
        this.listModel = CnReportScheduleListFactory.instance( this );
        this.viewModel = CnReportScheduleViewFactory.instance( this, root );
        var hasBaseMetadata = false;
        var lastReportTypeIdentifier = null;
        var lastAction = null;
        var promiseInProgress = null;

        this.metadata = { getPromise: function() { return self.getMetadata(); } };

        // extend getMetadata
        this.getMetadata = function() {
          var firstPromise = hasBaseMetadata ? $q.all() :
            this.$$getMetadata().then( function() { hasBaseMetadata = true; } );

          return firstPromise.then( function() {
            // don't use the parent identifier when in the view state, it doesn't work
            var reportTypeIdentifier = self.getParentIdentifier().identifier;
            var reportTypePromise = $q.all();

            if( 'view' == self.getActionFromState() ) {
              reportTypePromise = CnHttpFactory.instance( {
                path: self.getServiceResourcePath(),
                data: { select: { column: [ 'report_type_id' ] } }
              } ).get().then( function( response ) {
                reportTypeIdentifier = response.data.report_type_id;
              } );
            }

            return reportTypePromise.then( function() {
              if( null == promiseInProgress ) {
                // remove the parameter group's input list and metadata
                var parameterData = self.module.inputGroupList.findByProperty( 'title', 'Parameters' );
                parameterData.inputList = {};
                for( var column in self.metadata.columnList )
                  if( 'restrict_' == column.substring( 0, 9 ) )
                    delete self.metadata.columnList[column];

                lastReportTypeIdentifier = reportTypeIdentifier;
                lastAction = self.getActionFromState();

                promiseInProgress = $q.all( [

                  CnHttpFactory.instance( {
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
                  } ),

                  CnHttpFactory.instance( {
                    path: 'report_type/' + reportTypeIdentifier + '/role',
                    data: {
                      select: { column: [ 'id', 'name' ] },
                      modifier: { order: 'name' }
                    }
                  } ).query().then( function success( response ) {
                    self.metadata.columnList.role_id.enumList = [];
                    response.data.forEach( function( item ) {
                      self.metadata.columnList.role_id.enumList.push( { value: item.id, name: item.name } );
                    } );
                  } ),

                  CnHttpFactory.instance( {
                    path: 'report_type/' + reportTypeIdentifier + '/report_restriction',
                    data: { modifier: { order: { rank: false } } }
                  } ).get().then( function( response ) {
                    // replace all restrictions from the module and metadata
                    var inputPromiseList = [];
                    response.data.forEach( function( restriction ) {
                      var key = 'restrict_' + restriction.name;

                      var dateType = 'date' == restriction.restriction_type;
                      if( dateType ) {
                        // add before/after values for date
                        restriction.restriction_type = 'enum';
                        restriction.enum_list = [
                          '7 days before',
                          '3 days before',
                          '2 days before',
                          '1 day before',
                          'same day',
                          '1 day after',
                          '2 days after',
                          '3 days after',
                          '7 days after'
                        ].map( name => '"'+name+'"' ).join( ',' );
                      }

                      var result = cenozo.getInputFromRestriction( restriction, CnHttpFactory );

                      if( dateType ) {
                        // convert enum values to integers (with string types)
                        result.input.enumList
                          .filter( e => angular.isString( e.value ) )
                          .forEach( function( e ) {
                            if( 'same day' == e.value ) e.value = '0';
                            else if( e.value.match( /before/ ) ) e.value = String( -parseInt( e.value ) );
                            else if( e.value.match( /after/ ) ) e.value = String( parseInt( e.value ) );
                          } );
                      }

                      parameterData.inputList[key] = result.input;
                      inputPromiseList = inputPromiseList.concat( result.promiseList );
                      self.metadata.columnList[key] = {
                        required: restriction.mandatory,
                        restriction_type: restriction.restriction_type
                      };
                      if( angular.isDefined( result.input.enumList ) )
                        self.metadata.columnList[key].enumList = result.input.enumList;
                    } );
                    return $q.all( inputPromiseList );
                  } )

                ] );
              }

              return promiseInProgress.then( function() {
                // hold on to the last promise for a second to avoid unnecessarily calling it too often
                $timeout( function() { promiseInProgress = null; }, 1000 );
              } );
            } );
          } );
        };

        this.getServiceData = function( type, columnRestrictLists ) {
          // remove restrict_* columns from service data's select.column array
          var data = this.$$getServiceData( type, columnRestrictLists );
          data.select.column = data.select.column.filter( function( column ) {
            return 'restrict_' != column.column.substring( 0, 9 );
          } );
          return data;
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
