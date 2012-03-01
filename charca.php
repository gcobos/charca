<?php 
//print var_export($_SERVER,true);
if (in_array($_SERVER['HTTP_HOST'], array('::1','127.0.0.1'))) {
	$server = 'localhost';
} else {
	$server = $_SERVER['HTTP_HOST'];
}
$base_url = 'https://'.$server.dirname($_SERVER['REQUEST_URI']);
if ($_REQUEST['prb'])print $base_url;
?>
<!DOCTYPE html>
<html lang="en">
  <head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# game: http://ogp.me/ns/game#">
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="shortcut icon" href="images/favicon.ico">
	<meta charset="utf-8">
	<meta name="author" content="Gonzalo Cobos" > 
	<meta name="keywords" content="html5, game, charca, rana, insectos">  
	<meta name="robots" content="index,follow">
	
 	<meta property="og:title" content="Charca!" />
 	<meta property="fb:app_id" content="226492570779543" />
 	<meta property="og:image" content="<?php echo $base_url ?>/logo.png" />
   <meta property="og:type"        content="game" /> 
   <meta property="og:url"         content="<?php echo $base_url ?>" /> 
   <meta property="og:description" content="Ayuda a esta rana a mantener la charca limpia de insectos y a pegarse un atracón padre" /> 
 
	<title>Charca - Juego canvas HTML5</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			border: 0;
		}
		
		#canvasOverlay {
			position: absolute;
			display: none;
			margin: 0 auto;
			text-align: center;
		}
		
		.highscores {
			margin: 205px auto;
			vertical-align: center;
			text-align: center;
		}
		
		.highscores li {
			text-align: right;
			font: 28px "Sans-serif";
			text-shadow: #104030 1px -1px;
			color: #6394bB;
		}
		
		.highscores li div.score {
			width: 100px;
			display: inline-block;
			text-align: right;
		}
		
		.highscores li div.name {
			width: 350px;
			overflow: hidden;
			padding-left: 40px;
			text-align: left;
			display: inline-block;
		}
		
	</style>
	<script src="javascript/easel.js"></script>
	<script src="javascript/buzz.js"></script>
   <script src="javascript/frog.js"></script>
   <script src="javascript/insect.js"></script>
<script>

// Defines 
var MAX_INSECTS = 15;       // how many bugs can be in the stage at the same time

// Configuration for every level  [ number of insects, time, difficulty (max type of insect to generate) ]
var levelConfig = { 
	0: [15, 100, 1], //15 100 1 
	1: [25, 90, 2],
	2: [45, 80, 3],
	3: [65, 75, 3],
	4: [80, 70, 3],
	5: [90, 65, 3],
	6: [100,60, 3],
	7: [120,55, 4],
	8: [130,50, 4],
};

/*
// Testing
var levelConfig = { 
	0: [2, 10, 1], 
	1: [3, 90, 2],
	2: [45, 80, 3],
	3: [65, 75, 3],
	4: [80, 70, 3],
	5: [90, 65, 3],
	6: [100,60, 3],
	7: [120,55, 4],
	8: [130,50, 4],
};
*/
// Variables

var canvas;			      // main canvas
var stage;			      // main display stage
var overlay;				// canvas overlay
var mouse;					// mouse position

var splash;					// splash image 
var background;			// background while playing

var frog;			      // the frog

var insectsCloud;			// array of insects
var insectsKilled;		// Insects killed in a level
var aliveInsects;			// Alive insects in any moment

var playing;				// true when in game mode 
var score = 0;				// actual score
var level = 0; 			// actual level
var time;					// actual time left
var timer;
var baseTime;

var messageField;		   // message display field
var levelField;			// level field
var scoreField;			// score field
var timeField;          // time display field

var sounds;

var scoreList;

// Functions

function init (canvasId, canvasWrapper, overlayBlock) {
	//associate the canvas with the stage
	canvas = document.getElementById(canvasId);
	overlay = document.getElementById(overlayBlock);
	canvas.onselectstart = function () { return false; }
	stage = new Stage(canvas);

	sounds = new buzz.sound( "sounds/punch1", {
   	formats: [ "ogg", "wav", "mp3", "acc" ]
	});

	//ensure stage is blank and add the frog
	stage.clear();

	background = new Image();
	background.src = "images/background.jpg";
	
	// Splash window
	splash = new Image();
	splash.src = "images/title.jpg";
	splash.onload = function () {
		var bitmap = new Bitmap(splash);
   	bitmap.x = 0;
   	bitmap.y = 0;
		stage.addChild(bitmap);

		// Create an insect, just to ensure that everything is loaded for later
		new Insect(1);

		// create the player
		frog = new Frog();
		frog.x = 40;
		frog.y = canvas.height - 120;
	
		levelField = new Text("", "bold 16px Arial", "#FFFFFF");
		levelField.textAlign = "left";
		levelField.x = 20;
		levelField.y = 30;

		scoreField = new Text("", "bold 16px Arial", "#FFFFFF");
		scoreField.textAlign = "center";
		scoreField.x = canvas.width / 2;
		scoreField.y = 30;

		timeField = new Text("", "bold 16px Arial", "#FFFFFF");
		timeField.textAlign = "right";
		timeField.x = canvas.width - 20;
		timeField.y = 30;

		messageField = new Text("Pulsa aquí para jugar", "bold 24px Arial", "#343814");
		messageField.textAlign = "center";
		messageField.x = canvas.width / 2;
		messageField.y = canvas.height / 2.6;

		watchRestart();
	
	}
}

function watchRestart () {
	overlay.onclick = null;
	canvas.onclick = null;	
	canvas.ondblclick = null;
	canvas.onmousemove = null;

	playing = false;
	// watch for clicks
	stage.addChild(messageField);
	stage.update(); 	//update the stage to show text
	
	mouse = {x:0,y:0};
	
	// Get scores
	//console.log(window.location.href+'&func=scores&v='+score)
	if (!frog.alive) {
		scoreList = httpGet('<?php echo $base_url ?>'+'?func=scores&v='+score);
		console.log('Score list',scoreList);
		showHighScores();
	}

	// Wait before giving control
	clearTimeout(timer);
	var wait = 100;
	if (score) {
		wait = 1500;
	}
	timer = setTimeout('overlay.onclick = handleClick; canvas.onclick = handleClick; canvas.ondblclick = null; canvas.onmousemove = handleMouseMove;', 2000);
}

// reset all game logic
function restart() {
	// hide anything on stage
	stage.removeAllChildren();

	if (!frog.alive) {
		level = 0;
		score = 0;
	}
	time = levelConfig[level][1];
	frog.alive = true;
	frog.shooting = false;

	// new arrays to dump old data
	insectsCloud = new Array();
	aliveInsects = 0;
	insectsKilled = 0;
	
	//ensure stage is blank and add the frog
	stage.clear();

	var bitmap = new Bitmap(background);
	bitmap.x = 0;
	bitmap.y = 0;
	stage.addChild(bitmap);

	stage.addChild(frog);
	
	// Remove overlay
	overlay.style.display = 'none';
	
	Ticker.addListener(window);
	
	//start game timer
	window.clearInterval(timer);
	baseTime = time + Math.round(Ticker.getTime() / 1000);
	timer = setInterval("refreshHeader();", 250);	
	//console.log(time,Math.round(Ticker.getTime() / 1000) );
	playing = true;
	refreshHeader();
}

function tick() {

	if (playing) {
		// handle time limits
		if (time <= 0) {
			frog.die();
			messageField.text = "Estás frito. Pulsa aquí para jugar de nuevo";
			watchRestart();
		}

		// handle new insects
		if ( aliveInsects < Math.min(MAX_INSECTS,levelConfig[level][0]) && (insectsKilled + aliveInsects) < levelConfig[level][0]) {
			if (frog.alive) {
				var type = 1+ Math.floor(Math.random() * levelConfig[level][2]);	// Difficulty
				//console.log('new bug type', type);
				var index = getInsect(type);
				insectsCloud[index].floatOnScreen(canvas.width, canvas.height);
			}
		}	

		// handle frog's head movement (follows the mouse)
		if (frog.alive && !frog.shooting) {
			frog.lookAt(mouse);		
		}		
	}
	
	// handle insects (nested in one loop to prevent excess loops)
	aliveInsects = 0;
	for (insect in insectsCloud) {
		var o = insectsCloud[insect];
		if(!o || !o.active) { continue; }
		
		// handle insect movement
		if(outOfBounds(o, o.bounds)) {
			placeInBounds(o, o.bounds);
		}
		if (o.killed && frog.tongueIsBack) {
			o.x = frog.tonguePos.x;
			o.y = frog.tonguePos.y;
		} else if (o.killed) {
			o.active = false;
			stage.removeChild(o);
		}
		o.tick();
	
		if (playing) {
			//	handle frog collisions
			if	(frog.alive && o.hitRadius(frog.x, frog.y, frog.hit)) {
				frog.die();
				messageField.text = "Estás frito. Pulsa aquí para jugar de nuevo";
				watchRestart();
				continue;
			}
				// handle tongue collisions
			if(frog.alive && o.hitRadius(frog.tonguePos.x, frog.tonguePos.y, frog.hit)) {
				this.score += o.score;
				o.die();	// stops animation and follows tongue
				sounds.play();
				insectsKilled++;
				continue;
			}
			aliveInsects++;				
		}

		// handle the end of the level
		//console.log('Alive: '+ aliveInsects, 'Killed: '+insectsKilled, 'In this level: '+levelConfig[level][0]);
		if (insectsKilled >= levelConfig[level][0]) {
			messageField.text = "¡Has pasado al siguiente nivel!\nPulsa aquí para continuar.";
			level += 1;
			score += time * 5;
			watchRestart();
		}
		//console.log(aliveInsects);
	}
	//call sub ticks
	frog.tick();
	
	stage.update();
}

// Helper functions

function getInsect (type) {
	var i = 0;
	var len = insectsCloud.length;
	
	//pooling approach
	while(i <= len){
		if(!insectsCloud[i]) {
			insectsCloud[i] = new Insect(type);
			break;
		} else if(!insectsCloud[i].active) {
			insectsCloud[i].activate(type);
			break;
		} else {
			i++;
		}
	}
	
	if(len == 0) {
		insectsCloud[0] = new Insect(type);
	}
	
	stage.addChild(insectsCloud[i]);
	//console.log('Added insect to stage');
	return i;
}

function httpGet (theUrl)
{
   var xmlHttp = null;

	//if (theUrl.indexOf("localhost")==-1) {
		var result = {};
		xmlHttp = new XMLHttpRequest();
   	xmlHttp.open( "GET", theUrl, true);
   	xmlHttp.onreadystatechange=function() {
  			if (xmlHttp.readyState==4) {
   			try {
   				eval('result = ' + xmlHttp.responseText);
   			} catch (e) {
   				console.log('Failed to set high score!');
   			}
  			}
 		}
   	xmlHttp.send( null );
   	//console.log(xmlHttp.responseText);
   /*} else {
   		result = [
   			[9999,'Gonza Cob', 'id1'],
   			[5999,'JaimeCob', 'id2'],
   			[3999,'Otro jugador', 'id3'],
   			[1999,'Así vamos', 'id4'],
   			[999,'Así vamos2', 'id5'],
   			[599,'Así vamos3', 'id6'],
   			[199,'Así vamos4', 'id7'],
   		]
   }*/
   return result;
}
    
function refreshHeader ()
{
	var now = Math.round(Ticker.getTime() / 1000);
	
	if (playing) {
		time = baseTime - now;
		if (time<0) {
			time = 0;
		}
	}

	levelField.text = "NIVEL: " + (Number(level)).toString();
	stage.addChild(levelField);
	timeField.text = "TIEMPO: " + (Number(time)).toString();
	stage.addChild(timeField);
	scoreField.text = "PUNTOS: " + (Number(score)).toString();
	stage.addChild(scoreField);
	
}

function showHighScores ()
{
	content = '<ul class="highscores">';
	for (i in scoreList) {
		content += '<li><div class="score">'+scoreList[i][0]+'</div><div class="name">'+scoreList[i][1]+'</div></li>';
	}
	content += '</ul>';
	overlay.innerHTML = content;
	overlay.style.display = 'block';
}

function outOfBounds (o, bounds) 
{
	//is it visibly off screen
	return o.x < bounds || o.y < bounds || o.x > canvas.width-bounds || o.y > canvas.height-bounds;
}

function placeInBounds (o, bounds)
{
	//if its visual bounds are entirely off screen place it off screen on the other side
	if(o.x > canvas.width-bounds) {
		o.x = canvas.width-bounds;
	} else if(o.x < bounds) {
		o.x = bounds;
	}
	
	//if its visual bounds are entirely off screen place it off screen on the other side
	if(o.y > canvas.height-bounds) {
		o.y = canvas.height-bounds;
	} else if(o.y < bounds) {
		o.y = bounds;
	}
}
 
function makeVector(p1, p2)
{
	var somePoint = {x:0, y:0};
	somePoint.x = (p2.x - p1.x);
	somePoint.y = (p2.y - p1.y);
	return (somePoint);
}

function modulusOfVector (v)
{
	return (Math.sqrt((v.x * v.x) + (v.y * v.y)));			
}

function angleBetweenPoints (p1, p2)
{
	return 180/Math.PI * Math.atan2((p2.y - p1.y) , (p2.x - p1.x));
}

// Mouse handling functions
// executed in response to a mouse click on the screen
function handleClick (e) {
	// prevent extra clicks and hide text
	if (playing && frog.alive) {
	 	if (!frog.shooting) {
	 		frog.shoot(mouse);
		}
	} else {
		stage.removeChild(messageField);
		restart();
	}
	e.preventDefault();
}

//called when the mouse is moved over the canvas
function handleMouseMove (e)
{
	mouse.x = stage.mouseX;
	mouse.y = stage.mouseY;
}

</script> 
</head>
<body onload="init('stageCanvas', 'canvasWrapper','canvasOverlay')"> <!-- bgcolor="#769083">-->
	<div id="canvasWrapper" align="center" style="width: 640px; height: 480px">
		<div id="canvasOverlay" style="width: 640px; height: 480px"></div>
		<canvas width="640" height="480" id="stageCanvas" class="pantalla"></canvas>		
	</div>
</body>
</html>
