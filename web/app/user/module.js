define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'user', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: { column: 'name' },
    name: {
      singular: 'user',
      plural: 'users',
      possessive: 'user\'s',
      pluralPossessive: 'users\''
    },
    columnList: {
      name: {
        column: 'user.name',
        title: 'Name'
      },
      active: {
        column: 'user.active',
        title: 'Active',
        type: 'boolean'
      },
      first_name: {
        column: 'user.first_name',
        title: 'First'
      },
      last_name: {
        column: 'user.last_name',
        title: 'Last'
      },
      role_count: {
        title: 'Roles',
        type: 'number',
        help: 'The number of roles the user has access to for this application.'
      },
      site_count: {
        title: 'Sites',
        type: 'number',
        help: 'The number of sites the user has access to for this application.'
      },
      last_access_datetime: {
        title: 'Last Used',
        type: 'datetime',
        help: 'The last time the user accessed this application.'
      }
    },
    defaultOrder: {
      column: 'name',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    active: {
      title: 'Active',
      type: 'boolean',
      help: 'Inactive users will not be able to log in.  When activating a user their login failures count ' +
            'will automatically be reset back to 0.'
    },
    login_failures: {
      title: 'Login Failures',
      type: 'string',
      constant: true,
      help: 'Every time an invalid password is used to log in as this user this counter will go up.',
      exclude: 'add'
    },
    name: {
      title: 'Username',
      type: 'string',
      format: 'alpha_num',
      help: 'May only contain numbers, letters and underscores. Can only be defined when creating a new user.',
      constant: 'view'
    },
    first_name: {
      title: 'First Name',
      type: 'string'
    },
    last_name: {
      title: 'Last Name',
      type: 'string'
    },
    email: {
      title: 'Email',
      type: 'string',
      format: 'email',
      help: 'Must be in the format "account@domain.name" ' +
            '(if not provided then the user will be prompted for an email address the next time they login)'
    },
    timezone: {
      title: 'Timezone',
      type: 'typeahead',
      typeahead: moment.tz.names(),
      help: 'Which timezone the user displays times in'
    },
    use_12hour_clock: {
      title: 'Use 12-Hour Clock',
      type: 'boolean',
      help: 'Whether to display times using the 12-hour clock (am/pm)'
    },
    in_call: { type: 'hidden' }, // used to determine listen-to-call inclusion and disabling
    site_id: {
      title: 'Initial Site',
      type: 'enum',
      help: 'Which site to assign the user to',
      exclude: 'view'
    },
    role_id: {
      title: 'Initial Role',
      type: 'enum',
      help: 'Which role to assign the user to',
      exclude: 'view'
    },
    language_id: {
      title: 'Restrict to Language',
      type: 'enum',
      help: 'If the user can only speak a single language you can define it here (this can be changed in the ' +
            'user\'s record after they have been created)',
      exclude: 'view'
    }
  } );

  if( angular.isDefined( module.actions.edit ) ) {
    module.addExtraOperation( 'view', {
      title: 'Reset Password',
      operation: function( $state, model ) { model.viewModel.resetPassword(); }
    } );
  }

  module.addExtraOperation( 'view', {
    title: 'Listen to Call',
    classes: 'btn-warning',
    isIncluded: function( $state, model ) { return model.viewModel.listenToCallIncluded; },
    isDisabled: function( $state, model ) { return model.viewModel.listenToCallDisabled; },
    operation: function( $state, model ) {
      var self = this;

      // if the title is "Listen" then start listening in
      if( 'Listen to Call' == this.title ) {
        model.viewModel.listenToCall().then( function() { self.title = 'Stop Listening'; } );
      } else { // 'Stop Listening' == this.title
        model.viewModel.stopListenToCall().finally( function() { self.title = 'Listen to Call'; } );
      }
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnUserAdd', [
    'CnUserModelFactory',
    function( CnUserModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnUserModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnUserList', [
    'CnUserModelFactory',
    function( CnUserModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnUserModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnUserOverview', [
    'CnUserOverviewFactory',
    function( CnUserOverviewFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          $scope.model = CnUserOverviewFactory.instance();
          $scope.model.listModel.heading = "Active User List";
          $scope.model.setupBreadcrumbTrail();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnUserView', [
    'CnUserModelFactory',
    function( CnUserModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnUserModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnUserAddFactory', [
    'CnBaseAddFactory', 'CnSession', 'CnHttpFactory', 'CnModalConfirmFactory', 'CnModalMessageFactory', '$state',
    function( CnBaseAddFactory, CnSession, CnHttpFactory, CnModalConfirmFactory, CnModalMessageFactory, $state ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        // immediately view the user record after it has been created
        this.transitionOnSave = function( record ) { 
          CnSession.workingTransition( function() {
            $state.go( 'user.view', { identifier: 'name=' + record.name } );
          } );
        };

        // keep a local copy of the record when it gets added (used in the error handler below)
        var newRecord = null;
        this.onAdd = function( record ) {
          newRecord = record;
          return this.$$onAdd( record );
        };

        // catch user-already-exists errors and give the option to add access
        this.onAddError = function( response ) {
          if( 409 == response.status ) {
            console.info( 'The "409 (Conflict)" error found above is normal and can be ignored.' );
            CnHttpFactory.instance( {
              path: 'user/name=' + newRecord.name,
              data: { select: { column: [ 'name', 'first_name', 'last_name' ] } }
            } ).get().then( function( response ) {
              var user = response.data;
              CnModalConfirmFactory.instance( {
                title: 'User Already Exists',
                message: 'The username you are trying to create already exists and belongs to ' +
                  user.first_name + ' ' + user.last_name + '. ' +
                  'Would you like to view the user\'s details so that you can grant them access ' +
                  'to the requested site and role?'
              } ).show().then( function( response ) {
                if( response ) $state.go( 'user.view', { identifier: 'name=' + user.name } );
              } );
            } );
          } else { CnModalMessageFactory.httpError( response ); }
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnUserListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnUserViewFactory', [
    'CnBaseViewFactory', 'CnModalConfirmFactory', 'CnModalMessageFactory', 'CnSession', 'CnHttpFactory',
    function( CnBaseViewFactory, CnModalConfirmFactory, CnModalMessageFactory, CnSession, CnHttpFactory ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );
        this.listenToCallIncluded = false;
        this.listenToCallDisabled = true;

        // functions to handle listening to calls (voip spy)
        this.listenToCall = function() {
          return CnHttpFactory.instance( {
            path: 'voip/' + self.record.id,
            data: { operation: 'spy' }
          } ).patch();
        };

        this.stopListenToCall = function() {
          return CnHttpFactory.instance( {
            path: 'voip/0',
            onError: function( response ) {
              if( 404 == response.status ) {
                // ignore 404 errors, it just means there was no phone call found to hang up
              } else { CnModalMessageFactory.httpError( response ); }
            }
          } ).delete();
        };

        this.afterView( function() {
          CnSession.promise.then( function() {
            self.listenToCallIncluded = 1 < CnSession.role.tier && CnSession.voip.enabled && self.record.in_call;
            self.listenToCallDisabled =
              !CnSession.voip.info ||
              !CnSession.voip.info.status ||
              'OK' != CnSession.voip.info.status.substr( 0, 2 ) ||
              !self.record.in_call;
          } );

          try {
            cenozoApp.module( 'assignment' ); // make sure the assignment module is available

            CnHttpFactory.instance( {
              path: 'user/' + self.record.id + '/assignment',
              data: {
                modifier: { where: { column: 'assignment.end_datetime', operator: '=', value: null } },
                select: { column: [ 'id' ] }
              }
            } ).get().then( function( response ) {
              if( 0 < response.data.length ) {
                // add the view assignment button
                module.addExtraOperation( 'view', {
                  title: 'View Active Assignment',
                  operation: function( $state, model ) {
                    $state.go( 'assignment.view', { identifier: response.data[0].id } );
                  }
                } );
              } else {
                // remove the view assignment button, if found
                module.removeExtraOperation( 'view', 'View Active Assignment' );
              }
            } );
          } catch( err ) {}
        } );

        this.deferred.promise.then( function() {
          if( angular.isDefined( self.languageModel ) )
            self.languageModel.listModel.heading = 'Spoken Language List (if empty then all languages are spoken)';
        } );

        // extend the onPatch function
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            // update the login failures when active is set to true
            if( true === data.active ) {
              CnHttpFactory.instance( {
                path: self.parentModel.getServiceResourcePath(),
                data: { select: { column: [ 'login_failures' ] } }
              } ).get().then( function( response ) {
                self.record.login_failures = response.data.login_failures;
              } );
            }
          } );
        };

        // custom operation
        this.resetPassword = function() {
          CnModalConfirmFactory.instance( {
            title: 'Reset Password',
            message: 'Are you sure you wish to reset the password for user "' + self.record.name + '"'
          } ).show().then( function( response ) {
            if( response ) {
              CnHttpFactory.instance( {
                path: 'user/' + self.record.getIdentifier(),
                data: { password: true },
                onError: function( response ) {
                  if( 403 == response.status ) {
                    CnModalMessageFactory.instance( {
                      title: 'Unable To Change Password',
                      message: 'Sorry, you do not have access to resetting the password for user "' +
                               self.record.name+ '".',
                      error: true
                    } ).show();
                  } else { CnModalMessageFactory.httpError( response ); }
                }
              } ).patch().then( function() {
                CnModalMessageFactory.instance( {
                  title: 'Password Reset',
                  message: 'The password for user "' + self.record.name + '" has been successfully reset.'
                } ).show();
              } );
            }
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnUserModelFactory', [
    'CnBaseModelFactory', 'CnUserListFactory', 'CnUserAddFactory', 'CnUserViewFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnUserListFactory, CnUserAddFactory, CnUserViewFactory,
              CnSession, CnHttpFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnUserAddFactory.instance( this );
        this.listModel = CnUserListFactory.instance( this );
        this.viewModel = CnUserViewFactory.instance( this, root );

        // add additional details to some of the help text
        module.inputGroupList.findByProperty( 'title', '' ).inputList.login_failures.help +=
          ' Once it reaches ' + CnSession.application.loginFailureLimit +
          ' the user will automatically be deactivated.  Reactivating the user will reset the counter to 0.';

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return $q.all( [
              CnHttpFactory.instance( {
                path: 'application_type/name=' + CnSession.application.type + '/role',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: { name: false } },
                  granting: true // only return roles which we can grant access to
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.role_id = {
                  required: true,
                  enumList: []
                };
                response.data.forEach( function( item ) {
                  self.metadata.columnList.role_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'site',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: { name: false } },
                  granting: true // only return sites which we can grant access to
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.site_id = {
                  required: true,
                  enumList: []
                };
                response.data.forEach( function( item ) {
                  self.metadata.columnList.site_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'language',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: {
                    where: [ { column: 'active', operator: '=', value: true } ],
                    order: { name: false }
                  }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.language_id = {
                  required: false,
                  enumList: []
                };
                response.data.forEach( function( item ) {
                  self.metadata.columnList.language_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } )
            ] );
          } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnUserOverviewFactory', [
    'CnBaseModelFactory', 'CnUserListFactory', 'CnUserAddFactory', 'CnUserViewFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnUserListFactory, CnUserAddFactory, CnUserViewFactory,
              CnSession, CnHttpFactory, $q ) {
      var overviewModule = angular.copy( module );
      delete overviewModule.columnList.active;
      delete overviewModule.columnList.role_count;
      delete overviewModule.columnList.site_count;
      delete overviewModule.columnList.last_access_datetime;

      var columnList = {
        site: {
          column: 'site.name',
          title: 'Site',
        },
        role: {
          column: 'role.name',
          title: 'Role',
        },
        webphone: {
          title: 'Webphone',
          type: 'boolean'
        },
        in_call: {
          title: 'In Call',
          type: 'boolean',
          help: 'This will show as empty if there is a problem connecting to the VoIP service'
        },
        last_datetime: {
          column: 'access.datetime',
          title: 'Last Activity',
          type: 'time'
        }
      };

      // add the user's assignment uid (if the interview module is turned on)
      if( 0 <= CnSession.moduleList.indexOf( 'interview' ) )
        cenozo.insertPropertyAfter( columnList, 'role', 'assignment_uid', { title: 'Assignment' } );

      angular.extend( overviewModule.columnList, columnList );

      // remove some columns based on the voip and role details
      CnSession.promise.then( function() {
        if( !CnSession.application.voipEnabled ) {
          delete overviewModule.columnList.webphone;
          delete overviewModule.columnList.in_call;
        }
        if( !CnSession.role.allSites ) delete overviewModule.columnList.site;
      } );

      var object = function() {
        var self = this;

        CnBaseModelFactory.construct( this, overviewModule );
        angular.extend( this, {
          listModel: CnUserListFactory.instance( this ),
          setupBreadcrumbTrail: function() {
            CnSession.setBreadcrumbTrail( [ { title: 'User Overview' } ] );
          },
          getServiceData: function( type, columnRestrictLists ) {
            var data = this.$$getServiceData( type, columnRestrictLists );
            if( angular.isUndefined( data.modifier.where ) ) data.modifier.where = [];
            data.modifier.where.push( {
              column: 'activity.id',
              operator: '!=',
              value: null
            } );
            return data;
          }
        } );
        this.getAddEnabled = function() { return false; };
        this.getDeleteEnabled = function() { return false; };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} );
