<!doctype html>
<html lang="en" ng-app="cenozoApp">
<head>
  <meta charset="utf-8">
  <title><?php echo APP_TITLE; ?></title>
<?php $this->print_libs(); ?>
</head>
<body class="background">
  <div class="container-fluid jumbotron allow-select">
    <h2 class="text-info">
      <i class="glyphicon glyphicon-exclamation-sign"></i>
      <?php echo $title; ?>
    </h2>
    <p class="alert">
      <?php echo $message; ?>
    </p>
<?php if( $code ) { ?>
    <code class="spacer" style="background-color: inherit;">
      Error Code: <?php echo $code; ?>
    </code>
<?php } ?>
  </div>
</body>
</html>
