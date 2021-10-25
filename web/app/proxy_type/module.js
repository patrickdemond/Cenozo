cenozoApp.defineModule( { name: 'proxy_type', models: ['list', 'view'], create: module => {

  angular.extend( module, {
    identifier: { column: 'name' },
    name: {
      singular: 'proxy type',
      plural: 'proxy types',
      possessive: 'proxy type\'s'
    },
    columnList: {
      name: { title: 'Name' },
      participant_count: {
        title: 'Participants',
        type: 'number'
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
      type: 'string',
      isConstant: true
    },
    description: {
      title: 'Description',
      type: 'text'
    },
    prompt: {
      title: 'Prompt',
      type: 'text',
      help: 'This message will appear to any user adding this proxy type asking to confirm whether they wish to proceed.'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnProxyTypeViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var object = function( parentModel, root ) {
        CnBaseViewFactory.construct( this, parentModel, root, 'participant' );

        var self = this;
        async function init() {
          // allow administrators add/delete of roles and participants
          await self.deferred.promise;
          if( angular.isDefined( self.roleModel ) )
            self.roleModel.getChooseEnabled = function() { return 2 < CnSession.role.tier; };
        }

        init();
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

} } );
