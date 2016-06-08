<!doctype html>
<html lang="en" ng-app="cenozoApp">
<head ng-controller="HeadCtrl">
  <meta charset="utf-8">
  <title><?php echo APP_TITLE; ?>{{ getPageTitle() }}</title>
  <link rel="shortcut icon" href="<?php print ROOT_URL; ?>/img/favicon.ico">
  <link rel="stylesheet" href="<?php print LIB_URL; ?>/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?php print LIB_URL; ?>/angular-slider/slider.css">
  <link rel="stylesheet" href="<?php print LIB_URL; ?>/fullcalendar/dist/fullcalendar.min.css">
  <link rel="stylesheet" href="<?php print LIB_URL; ?>/angular-bootstrap-colorpicker/css/colorpicker.min.css">
  <link rel="stylesheet" href="<?php print CSS_URL; ?>/cenozo.css?build=<?php print CENOZO_BUILD; ?>">
  <link rel="stylesheet" href="<?php print ROOT_URL; ?>/css/theme.css?_">

  <script src="<?php print LIB_URL; ?>/jquery/dist/jquery.min.js"></script>
  <script src="<?php print LIB_URL; ?>/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="<?php print LIB_URL; ?>/moment/min/moment.min.js"></script>
  <script src="<?php print LIB_URL; ?>/moment-timezone/builds/moment-timezone-with-data-2010-2020.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular/angular.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular-animate/angular-animate.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular-sanitize/angular-sanitize.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular-ui-router/release/angular-ui-router.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular-slider/slider.js"></script>
  <script src="<?php print LIB_URL; ?>/fullcalendar/dist/fullcalendar.min.js"></script>
  <script src="<?php print LIB_URL; ?>/angular-bootstrap-colorpicker/js/bootstrap-colorpicker-module.min.js"></script>
  <script src="<?php print LIB_URL; ?>/file-saver/FileSaver.min.js"></script>

  <script src="<?php print CENOZO_URL; ?>/cenozo.js?build=<?php print CENOZO_BUILD; ?>" id="cenozo"></script>
  <script src="<?php print ROOT_URL; ?>/app.js?build=<?php print APP_BUILD; ?>" id="app"></script>
  <script src="<?php print LIB_URL; ?>/requirejs/require.js"></script>
</head>
<body class="background">
  <script>
    // define the framework and application build numbers
    window.cenozo.build = "<?php print CENOZO_BUILD; ?>";
    window.cenozoApp.build = "<?php print APP_BUILD; ?>";

    // set the application's base url (the object is created for us in cenozo.js)
    window.cenozoApp.baseUrl = "<?php print ROOT_URL; ?>";

    // define framework modules, set the applications module list then route them all
    window.cenozo.defineFrameworkModules( <?php print $framework_module_string; ?> );
    window.cenozoApp.setModuleList( <?php print $module_string; ?> );
    window.cenozoApp.config( [
      '$stateProvider',
      function( $stateProvider ) {
        for( var module in window.cenozoApp.moduleList )
          window.cenozo.routeModule( $stateProvider, module, window.cenozoApp.moduleList[module] );
      }
    ] );

    window.cenozoApp.controller( 'HeadCtrl', [
      '$scope', 'CnSession',
      function( $scope, CnSession ) {
        $scope.getPageTitle = function() { return CnSession.pageTitle; };
      }
    ] );

    window.cenozoApp.controller( 'MenuCtrl', [
      '$scope', '$state',
      function( $scope, $state ) {
        $scope.isCurrentState = function isCurrentState( state ) { return $state.is( state ); };
        $scope.lists = <?php print $list_item_string; ?>;
        $scope.utilities = <?php print $utility_item_string; ?>;
        $scope.reports = <?php print $report_item_string; ?>;

        var subMenuCount = ( $scope.lists?1:0 ) + ( $scope.utilities?1:0 ) + ( $scope.reports?1:0 );
        $scope.subMenuWidth = 12 / subMenuCount;
        $scope.showSubMenuHeaders = 1 < subMenuCount;

        $scope.getListItemClass = function( first, last ) {
          var className = 'no-rounding';
          if( $scope.showSubMenuHeaders ) {
            if( last ) className = 'rounded-bottom';
          } else {
            if( first ) {
              className = last ? 'rounded' : 'rounded-top';
            } else if( last ) {
              className = 'rounded-bottom';
            }
          }
          return className;
        }
      }
    ] );
  </script>

  <div ng-controller="HeaderCtrl">
    <nav class="navigation-header navbar navbar-default noselect">
      <div class="container-fluid bg-primary no-line-height" ng-class="{'working': session.working}">
        <div class="alert-header"
             ng-if="session.alertHeader"
             ng-click="session.onAlertHeader()"
             tooltip="{{ session.alertHeader }}"
             tooltip-placement="bottom"></div>
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <a class="navbar-brand" data-toggle="dropdown"><?php echo APP_TITLE; ?></a>
          <ul class="dropdown-menu navigation-menu">
            <li ng-controller="MenuCtrl">
              <div class="container-fluid operation-list">
                <div class="btn-group btn-group-justified">
                  <div class="btn-group" ng-repeat="operation in operationList">
                    <button class="btn btn-info"
                            ng-click="operation.execute()"
                            tooltip="{{ operation.help }}">{{ operation.title }}</button>
                  </div>
                </div>
              </div>
              <div class="container-fluid row">
                <ul ng-if="lists" class="navigation-group col-sm-{{ subMenuWidth }}">
                  <li ng-if="showSubMenuHeaders" class="container-fluid bg-primary rounded-top">
                    <h4 class="text-center">Lists</h4>
                  </li>
                  <li ng-repeat="(title,module) in lists">
                    <a class="btn btn-default btn-default btn-menu full-width"
                       ng-class="getListItemClass( $first, $last )"
                       ui-sref-active="btn-warning"
                       ui-sref="{{ module }}.list">{{ title }}</a>
                  </li>
                </ul>
                <ul ng-if="utilities" class="navigation-group col-sm-{{ subMenuWidth }}">
                  <li ng-if="showSubMenuHeaders" class="container-fluid bg-primary rounded-top">
                    <h4 class="text-center">Utilities</h4>
                  </li>
                  <li ng-repeat="(title,module) in utilities">
                    <a class="btn btn-default btn-default btn-menu full-width"
                       ng-class="getListItemClass( $first, $last )"
                       cn-target="module.target"
                       ui-sref-active="btn-warning"
                       ui-sref="{{
                         module.subject + '.' + module.action +
                         ( module.values ? '(' + module.values + ')' : '' )
                       }}">{{ title }}</a>
                  </li>
                </ul>
                <ul ng-if="reports" class="navigation-group col-sm-{{ subMenuWidth }}">
                  <li ng-if="showSubMenuHeaders" class="container-fluid bg-primary rounded-top">
                    <h4 class="text-center">Reports</h4>
                  </li>
                  <li ng-repeat="(title,name) in reports">
                    <a class="btn btn-default btn-default btn-menu full-width"
                       ng-class="getListItemClass( $first, $last )"
                       ui-sref-active="btn-warning"
                       ui-sref="report_type.view({identifier:'name={{name}}'})">{{ title }}</a>
                  </li>
                </ul>
              </div>
            </li>
          </ul>
          <button type="button"
                  class="navbar-toggle collapsed"
                  data-toggle="collapse"
                  data-target="#navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>
        <div class="collapse navbar-collapse" id="navbar-collapse">
          <ul class="nav navbar-nav">
            <ul class="breadcrumb breadcrumb-slash">
              <li class="navbar-item"
                  ng-repeat="breadcrumb in session.breadcrumbTrail"
                  ng-class="{'navbar-link':breadcrumb.go}"
                  ng-click="breadcrumb.go()">{{ breadcrumb.title }}
              </li>
            </ul>
          </ul>
          <ul class="nav navbar-nav navbar-right" ng-if="!isLoading">
            <li class="navbar-item navbar-link siterole" ng-click="session.showSiteRoleModal()">
              {{ session.role.name | cnUCWords }} @ {{ session.site.name }}
            </li>
            <li class="navbar-item navbar-link clock"
                ng-click="operationList.findByProperty( 'title', 'Timezone' ).execute()">
              <i class="glyphicon glyphicon-time"></i> {{ session.time }}
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="container-fluid outer-view-frame fade-transition noselect" ng-if="isLoading">
      <div class="inner-view-frame"><cn-loading></cn-loading></div>
      <div class="gradient-footer"></div>
    </div>
  </div>

  <div id="view" ui-view class="container-fluid outer-view-frame fade-transition noselect"></div>
</body>
</html>
