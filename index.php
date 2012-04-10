<?php

/**
 * Demonstration of the various OAuth flows. You would typically do this
 * when an unknown user is first using your application. Instead of storing
 * the token and secret in the session you would probably store them in a
 * secure database with their logon details for your website.
 *
 * When the user next visits the site, or you wish to act on their behalf,
 * you would use those tokens and skip this entire process.
 *
 * The Sign in with Twitter flow directs users to the oauth/authenticate
 * endpoint which does not support the direct message permission. To obtain
 * direct message permissions you must use the "Authorize Application" flows.
 *
 * Instructions:
 * 1) If you don't have one already, create a Twitter application on
 *      https://dev.twitter.com/apps
 * 2) From the application details page copy the consumer key and consumer
 *      secret into the place in this code marked with (YOUR_CONSUMER_KEY
 *      and YOUR_CONSUMER_SECRET)
 * 3) Visit this page using your web browser.
 *
 * @author themattharris
 */

require 'lib/tmhOAuth.php';
require 'lib/tmhUtilities.php';
$tmhOAuth = new tmhOAuth(array(
  'consumer_key'    => 'Fq2XgU0Rje9IXtNxsjQCQ',
  'consumer_secret' => 'HYRIDleqQyizKE2vmwEQcECYIj0jnQY4rS0bSxbQol8',
));

$here = tmhUtilities::php_self();
session_start();

function outputError($tmhOAuth) {
  echo 'Error: ' . $tmhOAuth->response['response'] . PHP_EOL;
  tmhUtilities::pr($tmhOAuth);
}

// reset request?
if ( isset($_REQUEST['wipe'])) {
  session_destroy();
  header("Location: {$here}");

// already got some credentials stored?
} elseif ( isset($_SESSION['access_token']) ) {
  $tmhOAuth->config['user_token']  = $_SESSION['access_token']['oauth_token'];
  $tmhOAuth->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];

  $code = $tmhOAuth->request('GET', $tmhOAuth->url('1/account/verify_credentials'));
  if ($code == 200) {
    $resp = json_decode($tmhOAuth->response['response']);

	//echo "<pre>";
	//print_r($resp);
	//echo "</pre>";
  } else {
    outputError($tmhOAuth);
  }
// we're being called back by Twitter
} elseif (isset($_REQUEST['oauth_verifier'])) {
  $tmhOAuth->config['user_token']  = $_SESSION['oauth']['oauth_token'];
  $tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];

  $code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/access_token', ''), array(
    'oauth_verifier' => $_REQUEST['oauth_verifier']
  ));

  if ($code == 200) {
    $_SESSION['access_token'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
    unset($_SESSION['oauth']);
    header("Location: {$here}");
  } else {
    outputError($tmhOAuth);
  }
// start the OAuth dance
} elseif ( isset($_REQUEST['authenticate']) || isset($_REQUEST['authorize']) ) {
  $callback = isset($_REQUEST['oob']) ? 'oob' : $here;

  $params = array(
    'oauth_callback'     => $callback
  );

  if (isset($_REQUEST['force_write'])) :
    $params['x_auth_access_type'] = 'write';
  elseif (isset($_REQUEST['force_read'])) :
    $params['x_auth_access_type'] = 'read';
  endif;

  $code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/request_token', ''), $params);

  if ($code == 200) {
    $_SESSION['oauth'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
    $method = isset($_REQUEST['authenticate']) ? 'authenticate' : 'authorize';
    $force  = isset($_REQUEST['force']) ? '&force_login=1' : '';
    $authurl = $tmhOAuth->url("oauth/{$method}", '') .  "?oauth_token={$_SESSION['oauth']['oauth_token']}{$force}";
   // echo '<p>To complete the OAuth flow follow this URL: <a href="'. $authurl . '">' . $authurl . '</a></p>';
    header("Location: $authurl"); 
 } else {
    outputError($tmhOAuth);
  }
}

$user_tweets = file_get_contents("http://api.twitter.com/1/statuses/user_timeline.json?screen_name=".$resp->screen_name);
$user_tweets = json_decode($user_tweets);

?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Bootstrap, from Twitter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- styles -->
    <link href="css/bootstrap.css" rel="stylesheet">
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
      #user-table td{
        padding: 5px;
      }
    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/base/jquery-ui.css" rel="stylesheet">

    <!--  HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- fav and touch icons -->
    <link rel="shortcut icon" href="../assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
  </head>

  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">TweetSweep</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
              <li><a href="#about">About</a></li>
              <li><a href="#contact">Contact</a></li>
             
            </ul>
            
            <ul class="nav pull-right">
              <form id="search-form" class="navbar-search" action="">
              <input type="text" class="search-query span2" placeholder="Search">
            </form>
            <!-- <li><a href="#">Link</a></li> -->
            <li class="divider-vertical"></li>
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Account <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li><a href="?authenticate=1">Log in with twitter</a></li>
                <li><a href="?authorize=1">Authorize application</a></li>
                <li><a href="#">View Profile</a></li>
                <li class="divider"></li>
                <li><a href="?wipe=1">Log out</a></li>
              </ul>
            </li>
          </ul>
          </div><!--/.nav-collapse -->

        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row-fluid">

        <div class="span3">
      <div class="span3 well" style="padding:0">
        <table id="user-table" style="width:100%;">
          <tr>
            <td><?php echo "<img src='$resp->profile_image_url' />"; ?></td>
            <td><?php echo '<strong>'.$resp->name.'</strong>'; echo '<br/>'; echo $resp->location; ?></td>
          </tr>
          <tr style="border-top:1px solid #e8e8e8; border-bottom:1px solid #e8e8e8;">
            <td><?php echo '<strong>'.$resp->statuses_count."</strong><br/>Tweets";?></td>
            <td style="border-left: 1px solid #e8e8e8;border-right: 1px solid #e8e8e8;">
              <?php echo '<strong>'.$resp->friends_count."</strong><br/>Following"; ?></td>
            <td><?php echo '<strong>'.$resp->followers_count."</strong><br/>Followers"; ?></td>
          </tr>
          <tr>
            <td colspan="3">
              <textarea class="" id="textarea" rows="3" style="width:96%" placeholder="Compose new tweet..."></textarea>
            </td>
          </tr>
        </table>

      </div> <!-- span3 -->

      <div class="span3 well" style="padding:0;height:350px;overflow:auto;">
        <table id="user-table" style="width:100%;">
          <tr>
            <td><h4>Tweets</h4></td>
            
          </tr>
          <?php foreach ($user_tweets as $tweet): ?>
          <tr style="border-top:1px solid #e8e8e8; border-bottom:1px solid #e8e8e8;">
            <td><?php echo "<img src='$resp->profile_image_url' />"; ?></td>
            <td><div><?php echo '<strong>'.$resp->name.'</strong>';?></div><?php echo $tweet->text; ?></td>
          </tr>
          <?php endforeach; ?>

        </table>

      </div> <!-- span3 -->
    </div>

      <div id="result" class="span6 well" style="height:525px"> 

      </div>
      <div class="span2 well">
        <div class="demo">

        <p>
          <label for="amount">Twitter Results:</label>
          <input type="text" id="amount" style="border:0; color:#f6931f; font-weight:bold; width:50px" />
        </p>

        <div id="slider"></div>

        </div><!-- End demo -->
      </div>
      <!-- <a id="testButton" class="btn" data-toggle="modal" data-contents="#masters" href="#myModal" >Launch Modal</a> -->

      <!-- <ul>
        <li><a href="?authenticate=1">Sign in with Twitter</a></li>
        <li><a href="?authenticate=1&amp;force=1">Sign in with Twitter (force login)</a></li>
        <li><a href="?authorize=1">Authorize Application (with callback)</a></li>
        <li><a href="?authorize=1&amp;oob=1">Authorize Application (oob - pincode flow)</a></li>
        <li><a href="?authorize=1&amp;force_read=1">Authorize Application (with callback) (force read-only permissions)</a></li>
        <li><a href="?authorize=1&amp;force_write=1">Authorize Application (with callback) (force read-write permissions)</a></li>
        <li><a href="?authorize=1&amp;force=1">Authorize Application (with callback) (force login)</a></li>
        <li><a href="?wipe=1">Start Over and delete stored tokens</a></li>
      </ul> -->
    </div> <!-- /row -->

    </div> <!-- /container -->



    <!-- javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script>
      $.ajaxSetup ({
        cache: false
      });
      var ajax_load = "<img src='img/bird-loader.gif' alt='loading...' style='margin-top:220px;margin-left:320px;' />";

    //  load() functions
      var loadUrl = "search2.php";
      $("#search-form").submit(function(e){
        var query = $('#search-form input').val();
        var pages = $('#amount').val();
        e.preventDefault();
        $("#result").html(ajax_load).load(loadUrl, "q="+query+"&pages="+pages);

      });

      $('#myModal').modal({
        keyboard: false
      }).modal('hide');

      $('#testButton').click(function(e){
        $(".modal-body").html(ajax_load).load("https://twitter.com/#!/search/%23masters");
      });
      

    </script>
    <script>
  $(function() {
    $( "#slider" ).slider({
      value:500,
      min: 100,
      max: 2000,
      step: 100,
      slide: function( event, ui ) {
        $( "#amount" ).val( ui.value );
      }
    });
    $( "#amount" ).val(  $( "#slider" ).slider( "value" ) );
  });
  </script>


  </body>
</html>

