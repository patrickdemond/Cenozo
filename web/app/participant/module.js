define( [ 'consent', 'event' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'participant', true ); } catch( err ) { console.warn( err ); return; }

  angular.extend( module, {
    identifier: { column: 'uid' },
    name: {
      singular: 'participant',
      plural: 'participants',
      possessive: 'participant\'s',
      pluralPossessive: 'participants\''
    },
    columnList: {
      uid: {
        column: 'participant.uid',
        title: 'UID'
      },
      first: {
        column: 'participant.first_name',
        title: 'First'
      },
      last: {
        column: 'participant.last_name',
        title: 'Last'
      },
      active: {
        column: 'participant.active',
        title: 'Active',
        type: 'boolean'
      },
      source: {
        column: 'source.name',
        title: 'Source'
      },
      cohort: {
        column: 'cohort.name',
        title: 'Cohort'
      },
      site: {
        column: 'site.name',
        title: 'Site'
      }
    },
    defaultOrder: {
      column: 'uid',
      reverse: false
    }
  } );

  // define inputs
  module.addInputGroup( '', {
    active: {
      title: 'Active',
      type: 'boolean',
      help: 'Participants can be deactivated so that they are not included in reports, interviews, etc. ' +
            'Deactivating a participant should only ever be used on a temporary basis. If a participant ' +
            'is to be permanently discontinued from the interview process then select a condition (below) ' +
            'instead.'
    },
    uid: {
      title: 'Unique ID',
      type: 'string',
      constant: true
    },
    source: {
      column: 'source.name',
      title: 'Source',
      type: 'string',
      constant: true
    },
    cohort: {
      column: 'cohort.name',
      title: 'Cohort',
      type: 'string',
      constant: true
    },
    note: {
      column: 'participant.note',
      title: 'Special Notes',
      type: 'text'
    },
  } );

  module.addInputGroup( 'Naming Details', {
    honorific: {
      title: 'Honorific',
      type: 'string',
      help: 'English examples: Mr. Mrs. Miss Ms. Dr. Prof. Br. Sr. Fr. Rev. Pr.  ' +
            'French examples: M. Mme Dr Dre Prof. F. Sr P. Révérend Pasteur Pasteure Me'
    },
    first_name: {
      title: 'First Name',
      type: 'string'
    },
    other_name: {
      title: 'Other/Nickname',
      type: 'string'
    },
    last_name: {
      title: 'Last Name',
      type: 'string'
    }
  } );

  module.addInputGroup( 'Defining Details', {
    sex: {
      title: 'Sex',
      type: 'enum'
    },
    date_of_birth: {
      title: 'Date of Birth',
      type: 'date',
      max: 'now'
    },
    age_group_id: {
      title: 'Age Group',
      type: 'enum',
      help: 'The age group that the participant belonged to when first recruited into the study. ' +
            'Note that this won\'t necessarily reflect the participant\'s current age.'
    },
    state_id: {
      title: 'Condition',
      type: 'enum',
      help: 'A condition defines the reason that a participant should no longer be contacted. ' +
            'If this value is not empty then the participant will no longer be contacted for interviews. ' +
            'Note that some roles do not have access to all conditions.'
    },
    language_id: {
      title: 'Preferred Language',
      type: 'enum'
    },
    withdraw_option: {
      title: 'Withdraw Option',
      type: 'string',
      constant: true
    }
  } );

  module.addInputGroup( 'Site & Contact Details', {
    default_site: {
      column: 'default_site.name',
      title: 'Default Site',
      type: 'string',
      constant: true,
      help: 'The site the participant belongs to if a preferred site is not set.'
    },
    preferred_site_id: {
      column: 'preferred_site.id',
      title: 'Preferred Site',
      type: 'enum',
      help: 'If set then the participant will be assigned to this site instead of the default site.'
    },
    out_of_area: {
      title: 'Out of Area',
      type: 'boolean',
      help: 'Whether the participant lives outside of the study\'s serviceable area'
    },
    email: {
      title: 'Email',
      type: 'string',
      format: 'email',
      help: 'Must be in the format "account@domain.name".'
    },
    mass_email: {
      title: 'Mass Emails',
      type: 'boolean',
      help: 'Whether the participant wishes to be included in mass emails such as newsletters, ' +
            'holiday greetings, etc.'
    }
  } );

  module.addExtraOperation( 'view', {
    title: 'Notes',
    operation: function( $state, model ) {
      $state.go( 'participant.notes', { identifier: model.viewModel.record.getIdentifier() } );
    }
  } );

  module.addExtraOperation( 'view', {
    title: 'History',
    operation: function( $state, model ) {
      $state.go( 'participant.history', { identifier: model.viewModel.record.getIdentifier() } );
    }
  } );

  module.addExtraOperation( 'list', {
    title: 'Search',
    isIncluded: function( $state, model ) { return 'participant' == model.getSubjectFromState(); },
    operation: function( $state, model ) { $state.go( 'search_result.list' ); }
  } );

  module.addExtraOperation( 'list', {
    title: 'Multiedit',
    isIncluded: function( $state, model ) { return 'participant' == model.getSubjectFromState(); },
    operation: function( $state, model ) { $state.go( 'participant.multiedit' ); }
  } );

  /**
   * The historyCategoryList object stores the following information
   *   category:
   *     active: whether or not to show the category in the history list by default
   *     promise: a function which gets all history items for that category and which must return a promise
   * 
   * This can be extended by applications by adding new history categories or changing existing ones.
   * Note: make sure the category name (the object's property) matches the property set in the historyList
   */
  module.historyCategoryList = {

    Address: {
      active: true,
      framework: true,
      promise: function( historyList, $state, CnHttpFactory ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/address',
          data: {
            modifier: {
              join: {
                table: 'region',
                onleft: 'address.region_id',
                onright: 'region.id'
              }
            },
            select: {
              column: [ 'create_timestamp', 'rank', 'address1', 'address2',
                        'city', 'postcode', 'international', {
                table: 'region',
                column: 'name',
                alias: 'region'
              }, {
                table: 'region',
                column: 'country'
              } ]
            }
          }
        } ).query().then( function( response ) {
          response.data.forEach( function( item ) {
            var description = item.address1;
            if( item.address2 ) description += '\n' + item.address2;
            description += '\n' + item.city + ', ' + item.region + ', ' + item.country + "\n" + item.postcode;
            if( item.international ) description += "\n(international)";
            historyList.push( {
              datetime: item.create_timestamp,
              category: 'Address',
              title: 'added rank ' + item.rank,
              description: description
            } );
          } );
        } );
      }
    },

    Alternate: {
      active: true,
      framework: true,
      promise: function( historyList, $state, CnHttpFactory ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/alternate',
          data: {
            select: { column: [ 'create_timestamp', 'association', 'alternate', 'informant', 'proxy',
                                'first_name', 'last_name' ] }
          }
        } ).query().then( function( response ) {
          response.data.forEach( function( item ) {
            var description = ' (' + ( item.association ? item.association : 'unknown association' ) + ')\n';
            var list = [];
            if( item.alternate ) list.push( 'alternate contact' );
            if( item.informant ) list.push( 'information provider' );
            if( item.proxy ) list.push( 'proxy decision maker' );
            if( 0 == list.length ) {
              description = '(not registiered for any role)';
            } else {
              list.forEach( function( name, index, array ) {
                if( 0 < index ) description += index == array.length - 1 ? ' and ' : ', ';
                description += name;
              } );
            }
            historyList.push( {
              datetime: item.create_timestamp,
              category: 'Alternate',
              title: 'added ' + item.first_name + ' ' + item.last_name,
              description: item.first_name + ' ' + item.last_name + description
            } );
          } );
        } );
      }
    },

    Consent: {
      active: true,
      framework: true,
      promise: function( historyList, $state, CnHttpFactory ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/consent',
          data: {
            modifier: {
              join: {
                table: 'consent_type',
                onleft: 'consent.consent_type_id',
                onright: 'consent_type.id'
              },
              order: { date: true }
            },
            select: {
              column: [ 'date', 'accept', 'written', 'note', {
                table: 'consent_type',
                column: 'name'
              }, {
                table: 'consent_type',
                column: 'description'
              } ]
            }
          }
        } ).query().then( function( response ) {
          response.data.forEach( function( item ) {
            historyList.push( {
              datetime: item.date,
              category: 'Consent',
              title: ( item.written ? 'Written' : 'Verbal' ) + ' "' + item.name + '" ' +
                     ( item.accept ? 'accepted' : 'rejected' ),
              description: item.description + '\n' + item.note
            } );
          } );
        } );
      }
    },

    Event: {
      active: true,
      framework: true,
      promise: function( historyList, $state, CnHttpFactory ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/event',
          data: {
            modifier: {
              join: {
                table: 'event_type',
                onleft: 'event.event_type_id',
                onright: 'event_type.id'
              },
              order: { datetime: true }
            },
            select: {
              column: [ 'datetime', {
                table: 'event_type',
                column: 'name'
              }, {
                table: 'event_type',
                column: 'description'
              } ]
            }
          }
        } ).query().then( function( response ) {
          response.data.forEach( function( item ) {
            historyList.push( {
              datetime: item.datetime,
              category: 'Event',
              title: 'added "' + item.name + '"',
              description: item.description
            } );
          } );
        } );
      }
    },

    Note: {
      active: true,
      framework: true,
      promise: function( historyList, $state, CnHttpFactory ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/note',
          data: {
            modifier: {
              join: {
                table: 'user',
                onleft: 'note.user_id',
                onright: 'user.id'
              },
              order: { datetime: true }
            },
            select: {
              column: [ 'datetime', 'note', {
                table: 'user',
                column: 'first_name',
                alias: 'user_first'
              }, {
                table: 'user',
                column: 'last_name',
                alias: 'user_last'
              } ]
            }
          }
        } ).query().then( function( response ) {
          response.data.forEach( function( item ) {
            historyList.push( {
              datetime: item.datetime,
              category: 'Note',
              title: 'added by ' + item.user_first + ' ' + item.user_last,
              description: item.note
            } );
          } );
        } );
      }
    },

    Phone: {
      active: true,
      framework: true,
      promise: function( historyList, $state, CnHttpFactory ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/phone',
          data: {
            select: { column: [ 'create_timestamp', 'rank', 'type', 'number', 'international' ] }
          }
        } ).query().then( function( response ) {
          response.data.forEach( function( item ) {
            historyList.push( {
              datetime: item.create_timestamp,
              category: 'Phone',
              title: 'added rank ' + item.rank,
              description: item.type + ': ' + item.number + ( item.international ? ' (international)' : '' )
            } );
          } );
        } );
      }
    }

  };

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnParticipantHistory', [
    'CnParticipantHistoryFactory', 'CnSession', '$state',
    function( CnParticipantHistoryFactory, CnSession, $state ) {
      return {
        templateUrl: module.getFileUrl( 'history.tpl.html' ),
        restrict: 'E',
        controller: function( $scope ) {
          $scope.isLoading = false;
          $scope.model = CnParticipantHistoryFactory.instance();
          $scope.uid = String( $state.params.identifier ).split( '=' ).pop();

          // create an array from the history categories object
          $scope.historyCategoryArray = [];
          for( var name in $scope.model.module.historyCategoryList ) {
            if( angular.isUndefined( $scope.model.module.historyCategoryList[name].framework ) )
              $scope.model.module.historyCategoryList[name].framework = false;
            if( angular.isUndefined( $scope.model.module.historyCategoryList[name].name ) )
              $scope.model.module.historyCategoryList[name].name = name;
            $scope.historyCategoryArray.push( $scope.model.module.historyCategoryList[name] );
          }

          $scope.viewNotes = function() {
            $state.go( 'participant.notes', { identifier: $state.params.identifier } );
          };

          $scope.viewParticipant = function() {
            $state.go( 'participant.view', { identifier: $state.params.identifier } );
          };

          $scope.refresh = function() {
            $scope.isLoading = true;
            $scope.model.onView().then( function() {
              CnSession.setBreadcrumbTrail(
                [ {
                  title: 'Participants',
                  go: function() { $state.go( 'participant.list' ); }
                }, {
                  title: $scope.uid,
                  go: function() { $state.go( 'participant.view', { identifier: $state.params.identifier } ); }
                }, {
                  title: 'History'
                } ]
              );
            } ).finally( function finished() { $scope.isLoading = false; } );
          };
          $scope.refresh();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnParticipantList', [
    'CnParticipantModelFactory',
    function( CnParticipantModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnParticipantModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnParticipantMultiedit', [
    'CnParticipantMultieditFactory', 'CnSession', '$state', '$timeout',
    function( CnParticipantMultieditFactory, CnSession, $state, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'multiedit.tpl.html' ),
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnParticipantMultieditFactory.instance();
          $scope.tab = 'participant';
          CnSession.setBreadcrumbTrail(
            [ {
              title: 'Participants',
              go: function() { $state.go( 'participant.list' ); }
            }, {
              title: 'Multi-Edit'
            } ]
          );

          // trigger the elastic directive when confirming the participant selection
          $scope.confirm = function() {
            $scope.model.confirm()
            $timeout( function() { angular.element( '#uidListString' ).trigger( 'change' ) }, 100 );
          };
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnParticipantNotes', [
    'CnParticipantNotesFactory', 'CnSession', '$state', '$timeout',
    function( CnParticipantNotesFactory, CnSession, $state, $timeout) {
      return {
        templateUrl: module.getFileUrl( 'notes.tpl.html' ),
        restrict: 'E',
        controller: function( $scope ) {
          $scope.isLoading = false;
          $scope.model = CnParticipantNotesFactory.instance();
          $scope.uid = String( $state.params.identifier ).split( '=' ).pop();

          // note actions are stored in the participant module in cenozo.js
          $scope.allowDelete = $scope.model.module.allowNoteDelete;
          $scope.allowEdit = $scope.model.module.allowNoteEdit;

          // trigger the elastic directive when adding a note or undoing
          $scope.addNote = function() {
            $scope.model.addNote();
            $timeout( function() { angular.element( '#newNote' ).trigger( 'change' ) }, 100 );
          };

          $scope.undo = function( id ) {
            $scope.model.undo( id );
            $timeout( function() { angular.element( '#note' + id ).trigger( 'change' ) }, 100 );
          };

          $scope.viewHistory = function() {
            $state.go( 'participant.history', { identifier: $state.params.identifier } );
          };

          $scope.viewParticipant = function() {
            $state.go( 'participant.view', { identifier: $state.params.identifier } );
          };

          $scope.refresh = function() {
            $scope.isLoading = true;
            $scope.model.onView().then( function() {
              CnSession.setBreadcrumbTrail(
                [ {
                  title: 'Participants',
                  go: function() { $state.go( 'participant.list' ); }
                }, {
                  title: $scope.uid,
                  go: function() { $state.go( 'participant.view', { identifier: $state.params.identifier } ); }
                }, {
                  title: 'Notes'
                } ]
              );
            } ).finally( function finish() { $scope.isLoading = false; } );
          };
          $scope.refresh();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnParticipantView', [
    'CnParticipantModelFactory',
    function( CnParticipantModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnParticipantModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnParticipantListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnParticipantViewFactory', [
    'CnBaseViewFactory', '$state',
    function( CnBaseViewFactory, $state ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        if( root ) {
          // override the collection model's getServiceData function (list active collections only)
          this.deferred.promise.then( function() {
            self.collectionModel.getServiceData = function( type, columnRestrictLists ) {
              var data = this.$$getServiceData( type, columnRestrictLists );
              if( angular.isUndefined( data.modifier ) ) data.modifier = { where: [] };
              else if( angular.isUndefined( data.modifier.where ) ) data.modifier.where = [];
              data.modifier.where.push( { column: 'collection.active', operator: '=', value: true } );
              return data;
            };

            if( angular.isDefined( self.applicationModel ) ) {
              self.applicationModel.enableView( false );
              self.applicationModel.addColumn(
                'default_site',
                { title: 'Default Site', column: 'default_site.name' }
              );
              self.applicationModel.addColumn(
                'preferred_site',
                { title: 'Preferred Site', column: 'preferred_site.name' }
              );
              self.applicationModel.addColumn(
                'datetime',
                { title: 'Release Date & Time', column: 'datetime', type: 'datetime' }
              );
              self.applicationModel.listModel.heading = 'Release List';
            }
          } );
        }
      };
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnParticipantModelFactory', [
    'CnBaseModelFactory', 'CnParticipantListFactory', 'CnParticipantViewFactory',
    'CnHttpFactory', 'CnSession', '$q',
    function( CnBaseModelFactory, CnParticipantListFactory, CnParticipantViewFactory,
              CnHttpFactory, CnSession, $q ) {
      var object = function( root ) {
        var self = this;

        // before constructing the model change some input types depending on the role's tier
        if( 3 > CnSession.role.tier ) {
          var definingInputGroup = module.inputGroupList.findByProperty( 'title', 'Defining Details' );
          if( definingInputGroup ) {
            definingInputGroup.inputList.sex.constant = true;
            definingInputGroup.inputList.age_group_id.constant = true;
          }
          if( 2 > CnSession.role.tier )
            module.inputGroupList.findByProperty( 'title', '' ).inputList.active.constant = true;
        }

        CnBaseModelFactory.construct( this, module );
        this.listModel = CnParticipantListFactory.instance( this );
        this.viewModel = CnParticipantViewFactory.instance( this, root );

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return $q.all( [
              CnHttpFactory.instance( {
                path: 'age_group',
                data: {
                  select: { column: [ 'id', 'lower', 'upper' ] },
                  modifier: { order: { lower: false } }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.age_group_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.age_group_id.enumList.push( {
                    value: item.id,
                    name: item.lower + ' to ' + item.upper
                  } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'language',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: {
                    where: { column: 'active', operator: '=', value: true },
                    order: 'name'
                  }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.language_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.language_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'site',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: 'name' }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.preferred_site_id = { enumList: [] };
                response.data.forEach( function( item ) {
                  self.metadata.columnList.preferred_site_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'state',
                data: {
                  select: { column: [ 'id', 'name', 'access' ] },
                  modifier: { order: 'rank' }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.state_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.state_id.enumList.push( {
                    value: item.id,
                    name: item.name,
                    disabled: !item.access
                  } );
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
  cenozo.providers.factory( 'CnParticipantHistoryFactory', [
    'CnSession', 'CnHttpFactory', '$state', '$q',
    function( CnSession, CnHttpFactory, $state, $q ) {
      var object = function() {
        var self = this;
        this.module = module;

        this.onView = function() {
          this.historyList = [];

          // get all history category promises, run them and then sort the resulting history list
          var promiseList = [];
          for( var name in this.module.historyCategoryList ) {
            if( 'function' == cenozo.getType( this.module.historyCategoryList[name].promise ) ) {
              promiseList.push(
                this.module.historyCategoryList[name].promise( this.historyList, $state, CnHttpFactory, $q )
              );
            }
          };

          return $q.all( promiseList ).then( function() {
            // convert invalid dates to null
            self.historyList.forEach( function( item ) {
              if( '0000-00-00' == item.datetime.substring( 0, 10 ) ) item.datetime = null;
            } );
            // sort the history list by datetime
            self.historyList = self.historyList.sort( function( a, b ) {
              return moment( new Date( a.datetime ) ).isBefore( new Date( b.datetime ) ) ? 1 : -1;
            } );
          } );
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnParticipantMultieditFactory', [
    'CnSession', 'CnHttpFactory',
    'CnModalDatetimeFactory', 'CnModalMessageFactory',
    'CnConsentModelFactory', 'CnEventModelFactory', 'CnParticipantModelFactory',
    function( CnSession, CnHttpFactory,
              CnModalDatetimeFactory, CnModalMessageFactory,
              CnConsentModelFactory, CnEventModelFactory, CnParticipantModelFactory ) {
      var object = function() {
        var self = this;
        this.module = module;
        this.confirmInProgress = false;
        this.confirmedCount = null;
        this.uidListString = '';
        this.activeInput = '';
        this.hasActiveInputs = false;
        this.participantInputList = null;
        this.consentInputList = null;
        this.collectionList = null;
        this.collectionOperation = 'add';
        this.collectionId = undefined;
        this.eventInputList = null;
        this.note = { sticky: 0, note: '' };

        // given a module and metadata this function will build an input list
        function processInputList( list, module, metadata ) {
          list.forEach( function( column, index, array ) {
            // find this column's input details in the module's input group list
            var input = null
            module.inputGroupList.some( function( group ) {
              for( var groupListColumn in group.inputList ) {
                if( column == groupListColumn ) {
                  input = group.inputList[groupListColumn];
                  return true; // stop looping over inputGroupList
                }
              }
            } );

            if( null != input ) {
              // convert the column name into an object
              array[index] = {
                column: column,
                title: input.title,
                type: input.type,
                min: input.min,
                max: input.max,
                active: false,
                value: metadata[column].default,
                required: metadata[column].required,
                max_length: metadata[column].max_length,
                enumList: angular.copy( metadata[column].enumList )
              };

              // Inputs with enum types need to do a bit of extra work with the enumList and default value
              if( 'boolean' == array[index].type ) {
                // set not as the default value
                if( null == array[index].value ) array[index].value = 0;
              } else if( 'enum' == array[index].type ) {
                if( !array[index].required ) {
                  // enums which are not required should have an empty value
                  array[index].enumList.unshift( {
                    value: '',
                    name: '(empty)'
                  } );
                }

                // always select the first value, whatever it is
                array[index].value = array[index].enumList[0].value;
              } else if( 'date' == array[index].type ) {
                array[index].formattedValue = '(empty)';
              }
            }
          } );

          return list;
        };

        // populate the participant input list once the participant's metadata has been loaded
        CnParticipantModelFactory.root.metadata.getPromise().then( function() {
          self.participantInputList = processInputList( [
              'active', 'honorific', 'first_name', 'other_name', 'last_name', 'sex', 'date_of_birth',
              'age_group_id', 'state_id', 'language_id', 'preferred_site_id', 'out_of_area', 'email',
              'mass_email'
            ],
            self.module,
            CnParticipantModelFactory.root.metadata.columnList
          );

          // add the placeholder to the column list
          self.participantInputList.unshift( {
            active: false,
            column: '',
            title: 'Select which column to edit'
          } );
        } );

        // populate the consent input list once the consent's metadata has been loaded
        CnConsentModelFactory.root.metadata.getPromise().then( function() {
          self.consentInputList = processInputList(
            [ 'consent_type_id', 'accept', 'written', 'date', 'note' ],
            cenozoApp.module( 'consent' ),
            CnConsentModelFactory.root.metadata.columnList
          );
        } );

        // populate the collection input list right away
        CnHttpFactory.instance( {
          path: 'collection',
          data: {
            select: { column: [ 'id', 'name' ] },
            modifier: {
              where: [
                { column: 'collection.active', operator: '=', value: true },
                { column: 'collection.locked', operator: '=', value: false }
              ]
            }
          }
        } ).query().then( function( response ) {
          self.collectionList = response.data;
          self.collectionList.unshift( { id: undefined, name: '(Select Collection)' } );
        } );

        // populate the event input list once the event's metadata has been loaded
        CnEventModelFactory.root.metadata.getPromise().then( function() {
          self.eventInputList = processInputList(
            [ 'event_type_id', 'datetime' ],
            cenozoApp.module( 'event' ),
            CnEventModelFactory.root.metadata.columnList
          );
        } );

        this.uidListStringChanged = function() {
          this.confirmedCount = null;
        };

        this.confirm = function() {
          this.confirmInProgress = true;
          this.confirmedCount = null;

          // clean up the uid list
          var fixedList =
            this.uidListString.toUpperCase() // convert to uppercase
                        .replace( /[\s,;|\/]/g, ' ' ) // replace whitespace and separation chars with a space
                        .replace( /[^a-zA-Z0-9 ]/g, '' ) // remove anything that isn't a letter, number of space
                        .split( ' ' ) // delimite string by spaces and create array from result
                        .filter( function( uid ) { // match UIDs (eg: A123456)
                          return null != uid.match( /^[A-Z][0-9]{6}$/ );
                        } )
                        .filter( function( uid, index, array ) { // make array unique
                          return index <= array.indexOf( uid );
                        } )
                        .sort(); // sort the array

          // now confirm UID list with server
          if( 0 == fixedList.length ) {
            self.uidListString = '';
            self.confirmInProgress = false;
          } else {
            CnHttpFactory.instance( {
              path: 'participant',
              data: { uid_list: fixedList }
            } ).post().then( function( response ) {
              self.confirmedCount = response.data.length;
              self.uidListString = response.data.join( ' ' );
              self.confirmInProgress = false;
            } );
          }
        };

        this.selectDatetime = function( input ) {
          CnModalDatetimeFactory.instance( {
            title: input.title,
            date: input.value,
            minDate: angular.isDefined( input.min ) ? input.min : input.min,
            maxDate: angular.isDefined( input.max ) ? input.max : input.max,
            pickerType: input.type,
            emptyAllowed: !input.required
          } ).show().then( function( response ) {
            if( false !== response ) {
              input.value = response;
              input.formattedValue = CnSession.formatValue( response, input.type, true );
            }
          } );
        };

        this.activateInput = function( column ) {
          if( column ) {
            this.participantInputList.findByProperty( 'column', column ).active = true;
            this.hasActiveInputs = true;
            if( column == this.activeInput ) this.activeInput = '';
          }
        };

        this.deactivateInput = function( column ) {
          this.participantInputList.findByProperty( 'column', column ).active = false;
          this.hasActiveInputs = 0 < this.participantInputList
            .filter( function( input ) { return input.active; }).length;
        };

        this.applyMultiedit = function( type ) {
          // test the formats of all columns
          var error = false;
          var uidList = this.uidListString.split( ' ' );
          if( 'consent' == type ) {
            var inputList = this.consentInputList;
            var model = CnConsentModelFactory.root;
            var messageModal = CnModalMessageFactory.instance( {
              title: 'Consent Records Added',
              message: 'The consent record has been successfully added to ' + uidList.length + ' participants.'
            } );
          } else if( 'collection' == type ) {
            // handle the collection id specially
            var element = cenozo.getScopeByQuerySelector( '#collectionId' ).innerForm.name;
            element.$error.format = false;
            cenozo.updateFormElement( element, true );
            error = error || element.$invalid;
            var messageModal = CnModalMessageFactory.instance( {
              title: 'Collection Updated',
              message: 'The participant list has been ' +
                       ( 'add' == this.collectionOperation ? 'added to ' : 'removed from ' ) +
                       'the "' + this.collectionList.findByProperty( 'id', this.collectionId ).name + '" ' +
                       'collection'
            } );
          } else if( 'event' == type ) {
            var inputList = this.eventInputList;
            var model = CnEventModelFactory.root;
            var messageModal = CnModalMessageFactory.instance( {
              title: 'Event Records Added',
              message: 'The event record has been successfully added to ' + uidList.length + ' participants.'
            } );
          } else if( 'note' == type ) {
            var inputList = this.noteInputList;
            var model = null;
            var messageModal = CnModalMessageFactory.instance( {
              title: 'Note Records Added',
              message: 'The note record has been successfully added to ' + uidList.length + ' participants.'
            } );
          } else if( 'participant' == type ) {
            var inputList = this.participantInputList.filter( function( input ) { return input.active; } );
            var model = CnParticipantModelFactory.root;
            var messageModal = CnModalMessageFactory.instance( {
              title: 'Participant Details Updated',
              message: 'The listed details have been successfully updated on ' + uidList.length +
                       ' participant records.'
            } );
          } else throw new Error( 'Called addRecords() with invalid type "' + type + '".' );

          if( inputList ) {
            inputList.forEach( function( input ) {
              var element = cenozo.getFormElement( input.column );
              if( element ) {
                var valid = model.testFormat( input.column, input.value );
                element.$error.format = !valid;
                cenozo.updateFormElement( element, true );
                error = error || element.$invalid;
              }
            } );
          }

          if( !error ) {
            var data = { uid_list: uidList };
            if( 'collection' == type ) {
              data.collection = { id: this.collectionId, operation: this.collectionOperation };
            } else if( 'note' == type ) {
              data.note = this.note;
            } else if( 'participant' == type ) {
              data.input_list = {};
              inputList.forEach( function( input ) { data.input_list[input.column] = input.value; } );
            } else {
              data[type] = inputList.reduce( function( record, input ) {
                record[input.column] = input.value;
                return record;
              }, {} );
            }

            CnHttpFactory.instance( {
              path: 'participant',
              data: data,
              onError: CnModalMessageFactory.httpError
            } ).post().then( function() { messageModal.show(); } );
          }
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnParticipantNotesFactory', [
    'CnSession', 'CnHttpFactory', '$state',
    function( CnSession, CnHttpFactory, $state ) {
      var object = function() {
        var self = this;
        this.module = module;
        this.newNote = '';

        this.addNote = function() {
          var note = {
            user_id: CnSession.user.id,
            datetime: moment().format(),
            note: self.newNote
          };

          CnHttpFactory.instance( {
            path: 'participant/' + $state.params.identifier + '/note',
            data: note
          } ).post().then( function( response ) {
            note.id = response.data;
            note.sticky = false;
            note.noteBackup = note.note;
            note.userFirst = CnSession.user.firstName;
            note.userLast = CnSession.user.lastName;
            return note;
          } ).then( function( note ) {
            self.noteList.push( note );
          } );

          this.newNote = '';
        };

        this.deleteNote = function( id ) {
          var index = this.noteList.findIndexByProperty( 'id', id );
          if( null !== index ) {
            CnHttpFactory.instance( {
              path: 'participant/' + $state.params.identifier + '/note/' + this.noteList[index].id
            } ).delete().then( function() {
              self.noteList.splice( index, 1 );
            } );
          }
        };

        this.noteChanged = function( id ) {
          var note = this.noteList.findByProperty( 'id', id );
          if( note ) {
            CnHttpFactory.instance( {
              path: 'participant/' + $state.params.identifier + '/note/' + note.id,
              data: { note: note.note }
            } );
          }
        };

        this.stickyChanged = function( id ) {
          var note = this.noteList.findByProperty( 'id', id );
          if( note ) {
            note.sticky = !note.sticky;
            CnHttpFactory.instance( {
              path: 'participant/' + $state.params.identifier + '/note/' + note.id,
              data: { sticky: note.sticky }
            } );
          }
        };

        this.undo = function( id ) {
          var note = this.noteList.findByProperty( 'id', id );
          if( note && note.note != note.noteBackup ) {
            note.note = note.noteBackup;
            CnHttpFactory.instance( {
              path: 'participant/' + $state.params.identifier + '/note/' + note.id,
              data: { note: note.note }
            } );
          }
        };

        this.onView = function() {
          this.noteList = [];

          return CnHttpFactory.instance( {
            path: 'participant/' + $state.params.identifier + '/note',
            data: {
              modifier: {
                join: {
                  table: 'user',
                  onleft: 'note.user_id',
                  onright: 'user.id'
                },
                order: { 'datetime': true }
              },
              select: {
                column: [ 'sticky', 'datetime', 'note', {
                  table: 'user',
                  column: 'first_name',
                  alias: 'user_first'
                } , {
                  table: 'user',
                  column: 'last_name',
                  alias: 'user_last'
                } ]
              }
            },
            redirectOnError: true
          } ).query().then( function( response ) {
            response.data.forEach( function( item ) {
              self.noteList.push( {
                id: item.id,
                datetime: '0000-00-00' == item.datetime.substring( 0, 10 ) ? null : item.datetime,
                sticky: item.sticky,
                userFirst: item.user_first,
                userLast: item.user_last,
                note: item.note,
                noteBackup: item.note
              } );
            } );
          } );
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} );
