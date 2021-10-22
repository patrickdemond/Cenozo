cenozoApp.defineModule( 'error', null, ( module ) => {

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnError', [
    'CnErrorModelFactory', '$window', '$state',
    function( CnErrorModelFactory, $window, $state ) {
      var type = angular.isDefined( $state.params['type'] ) ? $state.params['type'] : 500;
      return {
        templateUrl: module.getFileUrl( type + '.tpl.html' ),
        restrict: 'E',
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnErrorModelFactory.root;
          $scope.model.setupBreadcrumbTrail();
          $scope.back = function() { $window.history.back(); };
          $scope.reload = function() { $window.location.reload(); };
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnErrorModelFactory', [
    '$state', 'CnSession',
    function( $state, CnSession ) {
      return {
        root: new function() {
          var self = this;
          self.data = $state.params.data;
          this.setupBreadcrumbTrail = function() {
            CnSession.setBreadcrumbTrail( [ { title: $state.current.name.replace( /\./g, ' ' ).ucWords() } ] );
          };
          self.role = CnSession.role;
        }
      };
    }
  ] );

} );
