<?php

  //error_reporting(0);
  ini_set('display_errors',0);
  error_log('Entrada--------------------------------------------------------------------');
  error_log(var_export($_REQUEST,true));

  $app_id = "226492570779543";
  $app_secret = "ab358907f19ce19e0e15695e2c42b412";
  $canvas_page_url = 'https://apps.facebook.com/htmlgame_charca';
  
  $func = $_REQUEST['func'];

  // The Score
  $score = $_REQUEST['v'];

  // Authenticate the user
  session_start();
  if(isset($_REQUEST["code"])) {
     $code = $_REQUEST["code"];
  }

  if(empty($code) && !isset($_REQUEST['error'])) {
    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
    // Enforce https on production

    $dialog_url = 'https://www.facebook.com/dialog/oauth?' 
      . 'client_id=' . $app_id
      //. '&redirect_uri=' . urlencode($canvas_page_url)
      . '&state=' . $_SESSION['state']
      . '&scope=publish_actions';

	 error_log('Redireccion y fuera?');
    //print('<script> top.location.href=\'' . $dialog_url . '\'</script>');
    //exit;
  } else if(isset($_REQUEST['error'])) { 
    error_log('Error (dicen) y fuera');
    // The user did not authorize the app
    print($_REQUEST['error_description']);
    exit;
  };

  if (isset($_POST['signed_request'])) {
    // Get the User ID
    $signed_request = parse_signed_request($_POST['signed_request'],
      $app_secret);
    $uid = $signed_request['user_id'];
  }

  // Get an App Access Token
  $token_url = 'https://graph.facebook.com/oauth/access_token?'
    . 'client_id=' . $app_id
    . '&client_secret=' . $app_secret
    . '&grant_type=client_credentials';

  $token_response = file_get_contents($token_url);
  $params = null;
  parse_str($token_response, $params);
  $app_access_token = $params['access_token'];
  if ($app_access_token) {
  	 $_SESSION['fb_app_access_token'] = $app_access_token;
  	 error_log('Tengo app access token! '.$app_access_token);
  }
  $app_user_access_token = $signed_request['oauth_token']; 
  if ($app_user_access_token) {
  	 $_SESSION['fb_app_user_access_token'] = $app_user_access_token;
  	 error_log('Tengo user access token! '.$app_user_access_token);
  } 
  $user_id = $signed_request['user_id']; 
  if ($user_id) {
  	 $_SESSION['fb_user_id'] = $user_id;
  	 error_log('Tengo user id! '.$user_id);
  } 

require_once('utils.php');

if (isset($_REQUEST['func']) && in_array($_REQUEST['func'],array('scores'))) {
  if ($_REQUEST['func']=='scores') {

  	 //Get Scores **************
  	 require_once('AppInfo.php');
	 require_once('sdk/src/facebook.php');

	 $facebook = new Facebook(array(
  		'appId'  => AppInfo::appID(),
  		'secret' => AppInfo::appSecret(),
	 ));
	 
	 // Establece access
	 $facebook->setAccessToken($_SESSION['fb_app_user_access_token']); 	 
	 
	 error_log('PIDE EL USUSARIO');
	 $user_id=$_SESSION['fb_user_id'];
	 if ($user_id) {
	   try {
        // Fetch the viewer's basic information
        $basic = $facebook->api('/me');
        error_log('Tenemos about me! '.var_export($basic,true));
        
      } catch (FacebookApiException $e) {
        // If the call fails we check if we still have a user. The user will be
        // cleared if the error is because of an invalid accesstoken
        if (!$facebook->getUser()) {
          //header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
          exit();
        }
      }  
    }
    
	 $access_token = $facebook->getAccessToken();
	 error_log('El access token que obtengo de Facebook '.$user_access_token);
	 
	 error_log('Lo que tengo antes de la primera peticion');
	 error_log('Session'.var_export($_SESSION,true));
	 error_log('Request'.var_export($_REQUEST,true));
	 
  	 error_log('PIDE LISTADO DE PUNTOS');
	 $scores_URL = 'https://graph.facebook.com/' . $app_id . '/scores?access_token=' . $_SESSION['fb_app_user_access_token'];
	 $scores_result = json_decode(file_get_contents($scores_URL),true);
	 error_log("puntos para la aplicacion". var_export($scores_result,true));
	 
	 $result = array();
	 if (isset($scores_result['data'])) {		// true ||
		 //$result['pet_rq'] = array('hay', 'datos!!');
		 foreach ($scores_result['data'] as $row) {
			$result[$row['user']['id']] = array($row['score'], $row['user']['name']);
		 }
		 error_log('----------Leida y Compuesta '.var_export($result,true));

		 // If param 'v', post the score from user
	    if (isset($_REQUEST['v'])) {
	    	$new_score = $_REQUEST['v'];
	    
	    	if (!isset($result[$user_id])) {
	    		$result[$user_id] = array(0, he(idx($basic, 'name')));
	    	}
			// POST the user score only if is bigger
  			if ($result[$user_id][0] < $new_score) {
  				$result[$user_id][0] = (int)$new_score;
  				if ($_REQUEST['prb'])$result['envio_rq'] = array($new_score, 'puntos','envio_rq');
  				 error_log('Publica los puntos del usuario!!!');
				$score_URL = 'https://graph.facebook.com/' . $user_id . '/scores';
  				$score_result = https_post($score_URL,'score=' . $score . '&access_token=' . $_SESSION['fb_app_access_token']);  				
     	 		if ($score_result) {
     	 			error_log('Resultado del posteo de puntos?: '.var_export($score_result,true));
     	 		} else {
     	 			error_log("Mal! puntos no enviados");
     	 			if ($_REQUEST['prb'])$result['envio_rs'] = array($new_score, 'puntos no result!','envio_rs');
     	 		}
    		} else {
    			error_log('Puntuación más baja. Puntos no reenviados');
    			if ($_REQUEST['prb'])$result['noenvio_rs'] = array($new_score, 'puntuacion mas baja JAAJAJ','noenvio_rs');
    		}
     		//printf('<br/>Resultado %s<br/>',$score_result);
	 	 }
	 } else {
	 	error_log("Algo fue mal intentando coger la lista de records de la aplicacion".var_export($scores_result,true));
	 	if ($_REQUEST['prb'])$result['something'] = array('went','wrong?','something');
	 }
	 error_log('Listado de puntos a devolver!: '.var_export($result,true));
	 
	 rsort($result);
	 error_log('------Compuesta con el nuevo record si hubo alguno '.var_export($result,true));
	 $result_str = json_encode(array_values(array_slice($result, 0, 7)));
	 error_log("---------Result encodeado ".$result_str);
	 //ob_get_clean();
	 echo $result_str;
  }
  
  exit;
} else {
	include 'charca.php';
	error_log('Voy a salir pero tengo esto en el request: '.var_export($_REQUEST,true));
	exit;
}


  function https_post($uri, $postdata) {
    $ch = curl_init($uri);
    error_log('Enviando a '.$uri);
    error_log('Params: '.$postdata);
    curl_setopt($ch, CURLOPT_POST, true);
    //curl_setopt($ch, CURLOPT_FAILONERROR,1);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }

  function parse_signed_request($signed_request, $secret) {
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

    return $data;
  }

  function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
  }
