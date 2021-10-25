cenozoApp.defineModule( { name: 'script', models: ['add', 'list', 'view'], create: module => {

  angular.extend( module, {
    identifier: { column: 'name' },
    name: {
      singular: 'script',
      plural: 'scripts',
      possessive: 'script\'s'
    },
    columnList: {
      name: {
        column: 'script.name',
        title: 'Name'
      },
      application: {
        title: 'Application'
      },
      qnaire_title: {
        title: 'Questionnaire'
      },
      supporting: {
        title: 'Supporting',
        type: 'boolean'
      },
      repeated: {
        title: 'Repeated',
        type: 'boolean'
      },
      total_pages: {
        title: 'Pages'
      },
      access: {
        title: 'In Application',
        type: 'boolean'
      }
    },
    defaultOrder: {
      column: 'name',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    name: {
      title: 'Name',
      type: 'string'
    },
    sid: {
      title: 'Limesurvey Questionnaire',
      type: 'enum'
    },
    pine_qnaire_id: {
      title: 'Pine Questionnaire',
      type: 'enum'
    },
    supporting: {
      title: 'Supporting',
      type: 'boolean',
      help: 'Identifies this as a supporting script (launched in the "Scripts" dropdown when viewing a participant)'
    },
    repeated: {
      title: 'Repeated',
      type: 'boolean'
    },
    total_pages: {
      title: 'Total Number of Pages',
      type: 'string',
      isConstant: true,
      isExcluded: 'add',
      help: 'Updated nightly from Pine.'
    },
    started_event_type_id: {
      title: 'Started Event Type',
      type: 'enum'
    },
    finished_event_type_id: {
      title: 'Finished Event Type',
      type: 'enum'
    },
    description: {
      title: 'Description',
      type: 'text'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnScriptAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) {
        CnBaseAddFactory.construct( this, parentModel );

        this.onAdd = async function( record ) {
          // define the number of pages if this is a pine script
          if( angular.isDefined( record.pine_qnaire_id ) ) {
            var enumList = this.parentModel.metadata.columnList.pine_qnaire_id.enumList;
            record.total_pages = enumList.findByProperty( 'value', record.pine_qnaire_id ).total_pages;
          }
          this.$$onAdd( record );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnScriptModelFactory', [
    'CnBaseModelFactory', 'CnScriptAddFactory', 'CnScriptListFactory', 'CnScriptViewFactory', 'CnHttpFactory',
    function( CnBaseModelFactory, CnScriptAddFactory, CnScriptListFactory, CnScriptViewFactory, CnHttpFactory ) {
      var object = function( root ) {
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnScriptAddFactory.instance( this );
        this.listModel = CnScriptListFactory.instance( this );
        this.viewModel = CnScriptViewFactory.instance( this, root );

        // extend getMetadata
        this.getMetadata = async function() {
          await this.$$getMetadata();

          var [surveyResponse, pineQnaireResponse, eventTypeResponse] = await Promise.all( [
            CnHttpFactory.instance( {
              path: 'survey',
              data: {
                select: { column: [ 'sid', 'title' ] },
                modifier: { order: { title: false }, limit: 1000 }
              }
            } ).query(),

            CnHttpFactory.instance( {
              path: 'pine_qnaire',
              data: {
                select: { column: [ 'id', 'name', 'total_pages' ] },
                modifier: { order: { name: false }, limit: 1000 }
              }
            } ).query(),

            CnHttpFactory.instance( {
              path: 'event_type',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: 'name', limit: 1000 }
              }
            } ).query()
          ] );

          this.metadata.columnList.sid.enumList = surveyResponse.data.reduce( ( list, item ) => {
            list.push( { value: item.sid, name: item.title } );
            return list;
          }, [] );

          this.metadata.columnList.pine_qnaire_id.enumList = pineQnaireResponse.data.reduce( ( list, item ) => {
            list.push( { value: item.id, name: item.name, total_pages: item.total_pages } );
            return list;
          }, [] );

          this.metadata.columnList.started_event_type_id.enumList = eventTypeResponse.data.reduce( ( list, item ) => {
            list.push( { value: item.id, name: item.name } );
            return list;
          }, [] );

          this.metadata.columnList.finished_event_type_id.enumList =
            angular.copy( this.metadata.columnList.started_event_type_id.enumList );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} } );
