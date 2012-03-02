<?php

/**
 * This sample app is provided to kickstart your experience using Facebook's
 * resources for developers.  This sample app provides examples of several
 * key concepts, including authentication, the Graph API, and FQL (Facebook
 * Query Language). Please visit the docs at 'developers.facebook.com/docs'
 * to learn more about the resources available to you
 */

error_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
error_log("Llamada con".var_export($_REQUEST, true));
$app_id = "226492570779543"; //AppInfo::appID();
$app_secret = "ab358907f19ce19e0e15695e2c42b412"; //AppInfo::appSecret();


if ($_REQUEST['signed_request']) {
  error_log('Antes del parseo!!!!!!!!!!!!!!!!!');	
  $signed_request = parse_signed_request($_POST['signed_request'],$app_secret);
  error_log("Esto!".var_export($signed_request,true));
  error_log('JUASSSSSSSS');
//  exit;
}
$_SESSION['fc_vars'] = $signed_request; 
$app_user_access_token = $signed_request['oauth_token'];

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');


// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://') {
  header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
  error_log('Redireccion y  a la mierda?\n\nPos vaya\n');
  exit();
}


// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');


/*****************************************************************************
 *
 * The content below provides examples of how to fetch Facebook data using the
 * Graph API and FQL.  It uses the helper functions defined in 'utils.php' to
 * do so.  You should change this section so that it prepares all of the
 * information that you want to display to the user.
 *
 ****************************************************************************/

require_once('sdk/src/facebook.php');

$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
));


$user_id = $facebook->getUser();
$canvas_page_url = AppInfo::getUrl();


  error_log('Haciendo la peticion de access token');
  // Get an App Access Token
  $token_url = 'https://graph.facebook.com/oauth/access_token?'
    . 'client_id=' . $app_id
    . '&client_secret=' . $app_secret
    . '&grant_type=client_credentials';

  $token_response = file_get_contents($token_url);
  error_log("<br />resp raw: ".$token_response."<br />");
  
  $params = null;
  parse_str($token_response, $params);

  $app_access_token = $params['access_token'];

	error_log( "User token?". $app_access_token."<bt />");


error_log('Antes de las llamadas de marras');

if ($user_id) {
  try {
    // Fetch the viewer's basic information
    $basic = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    // If the call fails we check if we still have a user. The user will be
    // cleared if the error is because of an invalid accesstoken
    if (!$facebook->getUser()) {
    	//if (!isset($_REQUEST['reload'])) {
      	header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']."?reload=1"));
      } else {
      	if (isset($_REQUEST['func'])) {
      		//print var_export($e,true);
      		error_log("{something:'went wrong.'}");
      	} else {
      		error_log( "Please, search this game on Facebook and play from there.");
      	}
      }
      exit();
  }  
}

error_log("Tenemos user? ".$user_id);
error_log("Tenemos basic? ".var_export($basic,true));

  // This fetches some things that you like . 'limit=*" only returns * values.
  // To see the format of the data you are retrieving, use the "Graph API
  // Explorer" which is at https://developers.facebook.com/tools/explorer/
  //$likes = idx($facebook->api('/me/likes?limit=*'), 'data', array());

  // This fetches 4 of your friends.
  //$friends = idx($facebook->api('/me/friends?limit=4'), 'data', array());

  // And this returns 16 of your photos.
  //$photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

  // Here is an example of a FQL call that fetches all of your friends that are
  // using this app
  //$app_using_friends = $facebook->api(array(
  //  'method' => 'fql.query',
  //  'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
  //));


error_log('Despues de las llamadas de marras');

$hs_path_file = 'hscores.txt';

if (isset($_REQUEST['func']) && in_array($_REQUEST['func'],array('scores'))) {
  if ($_REQUEST['func']=='scores') {

	
	  $access_token = $facebook->getAccessToken();
	  error_log( "Access token por facebook?".$access_token.'<br />');	
  	
		error_log("Tokens:\nToken 1: $access_token\n\nToken 2: $app_access_token\n\n");  	
  	
  	 //Get Scores **************
  	 
  	 error_log('PIDE LISTADO DE PUNTOS');
	 $scores_result = $facebook->api('/'. AppInfo::appID() .'/scores');
	 error_log("puntos para la aplicacion". var_export($scores_result,true));
	 
	 $result = array();
	 if (isset($scores_result['data'])) {		// true ||
	 	 if (isset($scores_result['data'])) {	// false &&
		 	//print '<pre>TOTAL'.var_export($scores_result,true).'</pre><br/>';
		 	//$result['pet_rq'] = array('hay', 'datos!!');
		 	foreach ($scores_result['data'] as $row) {
				//print '<pre>'.var_export($row,true).'</pre><br/>';
				//printf('<h3>User: %s, puntos: %d</h3><br />',$row['user']['name'],$row['score']);
				$result[$row['user']['id']] = array($row['score'], $row['user']['name']);
		 	}
		 } else {
		 	error_log("PUES LEO DEL FICHERO");
		 	$result = unserialize(file_get_contents($hs_path_file));
		 	error_log("LEIDO DEL FICHERO".var_export($result,true));
		 }

		 // If param 'v', post the score from user
	    if (isset($_REQUEST['v'])) {
	    	$new_score = $_REQUEST['v'];
	    
	    	if (!isset($result[$user_id])) {
	    		$result[$user_id] = array(0, he(idx($basic, 'name')));
	    	}
			// POST the user score only if is bigger
  			if (true || $result[$user_id][0] < $new_score) {
  				$result[$user_id][0] = $new_score;
				/*error_log("ESCRIBO LOS RECORDS EN FICHERO");
				file_put_contents($hs_path_file, serialize($result));  				
  				error_log("ESCRITOS LOS RECORDS EN FICHERO".var_export($result,true));
  				*/
  				if ($_REQUEST['prb'])$result['envio_rq'] = array($new_score, 'puntos','envio_rq');
    			$score_URL = 'https://graph.facebook.com/' . $app_id . '/scores';
    			error_log('Apunto de enviar la puntuacion nueva a '.$score_URL.' de ' .$new_score);
    			$score_result = https_post($score_URL,
     	 		'score=' . $new_score
     	 		. '&access_token=' . $app_user_access_token);
     	 		if ($score_result) {
     	 			error_log('Listado de puntos real: '.var_export($score_result,true));
     	 			error_log("Bien, sobreescribe el record en el array y devuelve toda la lista");
     	 			
     	 		} else {
     	 			error_log("Mal! puntos no enviados");
     	 			error_log("Mal! puntos no enviados");
     	 			if ($_REQUEST['prb'])$result['envio_rs'] = array($new_score, 'puntos no result!','envio_rs');
     	 		}
    		} else {
    			if ($_REQUEST['prb'])$result['noenvio_rs'] = array($new_score, 'puntuacion mas baja JAAJAJ','noenvio_rs');
    		}
     		//printf('<br/>Resultado %s<br/>',$score_result);
	 	 }
	 } else {
	 	error_log("Algo fue mal intentando coger la lista de records de la aplicacion".var_export($scores_result,true));
	 	if ($_REQUEST['prb'])$result['something'] = array('went','wrong?','something');
	 }
	 if ($_REQUEST['prb']) {
	 	error_log("Puntuacion despues de añadir el nuevo record:" . var_export($result,true)."<br />");
	 }
	 rsort($result);
	 print(json_encode(array_values(array_slice($result, 0, 7))));
  }
  exit;
}

// HELPERS
  function https_post($uri, $postdata) {
    $ch = curl_init($uri);
   // curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }
  function parse_signed_request($signed_request, $secret) {
  	 error_log('Que se supone que puedo hacer?');
    list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

    // decode the data
    $sig = base64_url_decode($encoded_sig);
    $data = json_decode(base64_url_decode($payload), true);

    if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
      error_log('Unknown algorithm. Expected HMAC-SHA256');
      return null;
    }

    // check sig
    $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
    if ($sig !== $expected_sig) {
      error_log('Bad Signed JSON signature!');
      return null;
    }
	  error_log('Joder');
    return $data;
  }

  function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));

  } 


// Fetch the basic info of the app that they are using
//$app_info = $facebook->api('/'. AppInfo::appID());

//$app_name = idx($app_info, 'name', '');

?>
<?php /* ?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title><?php echo he($app_name); ?></title>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" type="text/css" />

    <!--[if IEMobile]>
    <link rel="stylesheet" href="mobile.css" media="screen" type="text/css"  />
    <![endif]-->

    <!-- These are Open Graph tags.  They add meta data to your  -->
    <!-- site that facebook uses when your content is shared     -->
    <!-- over facebook.  You should fill these tags in with      -->
    <!-- your data.  To learn more about Open Graph, visit       -->
    <!-- 'https://developers.facebook.com/docs/opengraph/'       -->
    <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="Juego de la charca" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>

    <script type="text/javascript">
      function logResponse(response) {
        if (console && console.log) {
          console.log('The response was', response);
        }
      }

      $(function(){
        // Set up so we handle click on the buttons
        $('#postToWall').click(function() {
          FB.ui(
            {
              method : 'feed',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendToFriends').click(function() {
          FB.ui(
            {
              method : 'send',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendRequest').click(function() {
          FB.ui(
            {
              method  : 'apprequests',
              message : $(this).attr('data-message')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });
      });
    </script>

    <!--[if IE]>
      <script type="text/javascript">
        var tags = ['header', 'section'];
        while(tags.length)
          document.createElement(tags.pop());
      </script>
    <![endif]-->
  </head>
  <body>
    <div id="fb-root"></div>
    <?php */ ?>
    <?php if (!isset($_REQUEST['func'])) include 'charca.php'; ?>
	<!--<fb:like send="false" width="640" show_faces="false" />-->

	<?php exit; ?>

    IGNORED FROM HERE

    <script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '<?php echo AppInfo::appID(); ?>', // App ID
          channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
          status     : true, // check login status
          cookie     : true, // enable cookies to allow the server to access the session
          xfbml      : true // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
          // We want to reload the page now so PHP can read the cookie that the
          // Javascript SDK sat. But we don't want to use
          // window.location.reload() because if this is in a canvas there was a
          // post made to this page and a reload will trigger a message to the
          // user asking if they want to send data again.
          window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
      };

      // Load the SDK Asynchronously
      (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    </script>
	
	<div class="fb-login-button" data-scope="user_likes,user_photos"></div>
	<?php ?>
    <header class="clearfix"> 
      
   	   
      <?php if (isset($basic)) { ?>
      <p id="picture" style="background-image: url(https://graph.facebook.com/<?php echo he($user_id); ?>/picture?type=normal)"></p>

      <div>
        <h1>Welcome, <strong><?php echo he(idx($basic, 'name')); ?></strong></h1>
        <p class="tagline">
          This is your app
          <a href="<?php echo he(idx($app_info, 'link'));?>" target="_top"><?php echo he($app_name); ?></a>
        </p>

        <div id="share-app">
          <p>Share your app:</p>
          <ul>
            <li>
              <a href="#" class="facebook-button" id="postToWall" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="plus">Post to Wall</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button speech-bubble" id="sendToFriends" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="speech-bubble">Send Message</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button apprequests" id="sendRequest" data-message="Test this awesome app">
                <span class="apprequests">Send Requests</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <?php } else { ?>
      <div>
        <h1>Welcome</h1>
       <div class="fb-login-button" data-scope="user_likes,user_photos"></div>
      </div>
      <?php } ?>
    </header>
    <?php
      if ($user_id) {
    ?>
    <section id="samples" class="clearfix">
      <h1>Examples of the Facebook Graph API</h1>

      <div class="list">
        <h3>A few of your friends</h3>
        <ul class="friends">
          <?php
            foreach ($friends as $friend) {
              // Extract the pieces of info we need from the requests above
              $id = idx($friend, 'id');
              $name = idx($friend, 'name');
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
              <?php echo he($name); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>

      <div class="list inline">
        <h3>Recent photos</h3>
        <ul class="photos">
          <?php
            $i = 0;
            foreach ($photos as $photo) {
              // Extract the pieces of info we need from the requests above
              $id = idx($photo, 'id');
              $picture = idx($photo, 'picture');
              $link = idx($photo, 'link');

              $class = ($i++ % 4 === 0) ? 'first-column' : '';
          ?>
          <li style="background-image: url(<?php echo he($picture); ?>);" class="<?php echo $class; ?>">
            <a href="<?php echo he($link); ?>" target="_top"></a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>

      <div class="list">
        <h3>Things you like</h3>
        <ul class="things">
          <?php
            foreach ($likes as $like) {
              // Extract the pieces of info we need from the requests above
              $id = idx($like, 'id');
              $item = idx($like, 'name');

              // This display's the object that the user liked as a link to
              // that object's page.
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($item); ?>">
              <?php echo he($item); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>
      <?php ?>
      <div class="list">
        <h3>Friends using this app</h3>
        <ul class="friends">
          <?php
            foreach ($app_using_friends as $auf) {
              // Extract the pieces of info we need from the requests above
              $id = idx($auf, 'uid');
              $name = idx($auf, 'name');
          ?>
          <li>
            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
              <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
              <?php echo he($name); ?>
            </a>
          </li>
          <?php
            }
          ?>
        </ul>
      </div>
      <?php ?>
    </section>

    <?php
      }
    ?>
    <?php ?>
  </body>
</html>
