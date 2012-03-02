<?php

  error_log('Entrada--------------------------------------------------------------------');
  error_log(var_export($_REQUEST,true));

  $app_id = "226492570779543";
  $app_secret = "ab358907f19ce19e0e15695e2c42b412";
  $canvas_page_url = 'http://charca.herokuapp.com';
  
// The Achievement URL
  $achievement = 'YOUR_ACHIEVEMENT_URL';
  $achievement_display_order = 1;

  // The Score
  $score = '30';

  // Authenticate the user
  session_start();
  if(isset($_REQUEST["code"])) {
     $code = $_REQUEST["code"];
  }

  if(empty($code) && !isset($_REQUEST['error'])) {
    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
    $dialog_url = 'https://www.facebook.com/dialog/oauth?' 
      . 'client_id=' . $app_id
      . '&redirect_uri=' . urlencode($canvas_page_url)
      . '&state=' . $_SESSION['state']
      . '&scope=publish_actions';

    print('<script> top.location.href=\'' . $dialog_url . '\'</script>');
    exit;
  } else if(isset($_REQUEST['error'])) { 
    // The user did not authorize the app
    print($_REQUEST['error_description']);
    exit;
  };

  // Get the User ID
  $signed_request = parse_signed_request($_POST['signed_request'],
    $app_secret);
  $uid = $signed_request['user_id'];

  // Get an App Access Token
  $token_url = 'https://graph.facebook.com/oauth/access_token?'
    . 'client_id=' . $app_id
    . '&client_secret=' . $app_secret
    . '&grant_type=client_credentials';

  $token_response = file_get_contents($token_url);
  $params = null;
  parse_str($token_response, $params);
  $app_access_token = $params['access_token'];
/*
  // Register an Achievement for the app
  print('Register Achievement:<br/>');
  $achievement_registration_URL = 'https://graph.facebook.com/' 
    . $app_id . '/achievements';
  $achievement_registration_result=https_post($achievement_registration_URL,
    'achievement=' . $achievement
      . '&display_order=' . $achievement_display_order
      . '&access_token=' . $app_access_token
  );
  print('<br/><br/>');

  // POST a user achievement
  print('Publish a User Achievement<br/>');
  $achievement_URL = 'https://graph.facebook.com/' . $uid . '/achievements';
  $achievement_result = https_post($achievement_URL,
    'achievement=' . $achievement
    . '&access_token=' . $app_access_token
  );
  print('<br/><br/>');
*/
  // POST a user score
  print('Publish a User Score<br/>');
  $score_URL = 'https://graph.facebook.com/' . $uid . '/scores';
  $score_result = https_post($score_URL,
    'score=' . $score
    . '&access_token=' . $app_access_token
  );
  print('<br/><br/>');

  function https_post($uri, $postdata) {
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_POST, true);
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

/*
if (isset($_REQUEST['func']) && in_array($_REQUEST['func'],array('scores'))) {
  if ($_REQUEST['func']=='scores') {
		ob_start();
	
	  $access_token = $facebook->getAccessToken();
	  error_log( "Access token por facebook?".$access_token.'<br />');	
  	
		error_log("Token 1: $access_token");
		error_log("Token 2: $app_access_token");
		error_log("Token 3: $app_user_access_token");  	
  	
  	 //Get Scores **************
  	 
  	 error_log('PIDE LISTADO DE PUNTOS');
	 $scores_result = $facebook->api('/'. AppInfo::appID() .'/scores?access_token='.$access_token);
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
		 	error_log('------Leida y Compuesta '.var_export($result,true));
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
  			if ($result[$user_id][0] < $new_score) {
  				$result[$user_id][0] = $new_score;
  				if ($_REQUEST['prb'])$result['envio_rq'] = array($new_score, 'puntos','envio_rq');
    			$score_URL = 'https://graph.facebook.com/' . $app_id . '/scores';
    			error_log('Apunto de enviar la puntuacion nueva a '.$score_URL.' de ' .$new_score);
    			error_log("Los parametros del post son ".'score=' . $new_score
     	 		. '&access_token=' . $app_user_access_token);
    			$score_result = https_post($score_URL,
     	 		'score=' . $new_score
     	 		. '&access_token=' . $app_user_access_token);
     	 		if ($score_result) {
     	 			error_log('Listado de puntos real: '.var_export($result,true));
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
	 error_log('------Compuesta con el nuevo record si hubo alguno '.var_export($result,true));
	 $result_str = json_encode(array_values(array_slice($result, 0, 7)));
	 error_log("---------Result encodeado ".var_export($result_str,true));
	 ob_get_clean();
	 echo $result_str;
  }
  
  exit;
}
*/
