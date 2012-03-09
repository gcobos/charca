<?php 
//print var_export($_SERVER,true);
if (in_array($_SERVER['HTTP_HOST'], array('localhost','::1','127.0.0.1'))) {
	$server = 'localhost';
	$proto = 'http://';
} else {
	$server = $_SERVER['HTTP_HOST'];
	$proto = 'https://';
}

$base_url = $proto.$server.'/'.dirname($_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="en">
  <head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# game: http://ogp.me/ns/game#">
   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="shortcut icon" href="images/favicon.ico">
	<meta charset="utf-8">
	<meta name="author" content="Gonzalo Cobos, Jaime Cobos" >
	<meta name="keywords" content="html5, game, charca, rana, insectos, swamp, bugs">  
	<meta name="robots" content="index,follow">
 	<meta property="game:title" content="Charca" />
 	<meta property="og:type" content="game" />
 	<meta property="fb:app_id" content="226492570779543" />
 	<meta property="og:image" content="https://charca.herokuapp.com/images/title.jpg" /> 
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
	<script src="javascript/sound.js"></script>
   <script src="javascript/frog.js"></script>
   <script src="javascript/insect.js"></script>
<script>

// Defines 
var MAX_INSECTS = 15;       // how many bugs can be in the stage at the same time

// Configuration for every level  [ number of insects, time, difficulty (max type of insect to generate), number of fireflies ]
var levelConfig = { 
	0: [15, 100, 1,0],   // 15
	1: [25, 95, 2,0],	// 25
	2: [45, 90, 3,0],	// 45
	3: [65, 85, 3,0],	// 65
	4: [75, 80, 3,0],	// 75
	5: [85, 75, 4,0],	// 85
	6: [90, 70, 4,0],	// 90
	7: [95, 70, 4,1],	// 95
	8: [100,65, 5,1],	// 100
	9: [110,60, 5,2],	// 110
	10: [405,5, 5,2],  // 405
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
var time = 0;				// actual time left
var timer;
var baseTime;
var fireflies = 0;          // How many fireflies have appeared already in this level
var aliveFireflies = 0;

var messageField;		   // message display field
var levelField;			// level field
var scoreField;			// score field
var timeField;          // time display field

var sounds;

var scoreList;

var d;						// debug shapes

// Functions

function init (canvasId, canvasWrapper, overlayBlock) {
	//associate the canvas with the stage
	canvas = document.getElementById(canvasId);
	overlay = document.getElementById(overlayBlock);
	canvas.onselectstart = function () { return false; }
	overlay.onselectstart = function () { return false; }
	stage = new Stage(canvas);

	// List of samples
	var list = [
		{name:"punch", src:["sounds/punch1.ogg"], instances:1},
	];
	SoundJS.addBatch(list);

	// TODO: Maybe do something with iOS for the sound	

	//ensure stage is blank and add the frog
	stage.clear();
	
	// Splash window
	splash = new Image();
	splash.src = "images/title.jpg";
	splash.onload = function () {
		var bitmap = new Bitmap(splash);
       	bitmap.x = 0;
       	bitmap.y = 0;
		stage.addChild(bitmap);

		// Create an insect, just to ensure that everything is loaded for later
		new Insect(1, 0);

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
	overlay.onmousemove = null;
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
		httpGet('<?php echo $base_url ?>'+'?func=scores&v='+score, function (scores) {
			scoreList = scores;
			//console.log('Score list',scores);
			showHighScores();	
		});
	}

	// Wait before giving control
	clearTimeout(timer);
	var wait = 100;
	if (score) {
		wait = 1500;
	}
	timer = setTimeout('overlay.onclick = handleClick; canvas.onmousemove = handleMouseMove; overlay.onmousemove = handleMouseMove; canvas.onclick = handleClick; canvas.ondblclick = null', 2000);
}

// reset all game logic
function restart() {
	// hide anything on stage
	stage.removeAllChildren();
	
	var wlevel = 0
	if (document.getElementById('wlevel')) {
    	wlevel = parseInt(document.getElementById('wlevel').value);
    }
	if (!frog.alive) {
		level = wlevel;
		score = 0;
	}
	time = levelConfig[level][1];
	frog.alive = true;
	frog.shooting = false;

	// new arrays to dump old data
	insectsCloud = new Array();
	aliveInsects = 0;
	insectsKilled = 0;
    fireflies = 0;

	background = new Image();
	if (level< 4) {
	    background.src = "images/background.jpg";
	} else if (level < 8) {
	    background.src = "images/background2.jpg";
	} else {
	    background.src = "images/background3.jpg";
	}
    background.onload = function () {

    	//ensure stage is blank and add the frog
    	stage.clear();

	    var bitmap = new Bitmap(background);
	    bitmap.x = 0;
	    bitmap.y = 0;
	    stage.addChild(bitmap);

	    stage.addChild(frog);

	    //d = new Shape();
	    //stage.addChild(d);
	
	    if (level == 10) {
    	    var index = getInsect(6, 1);
	    	insectsCloud[index].floatOnScreen(canvas.width, canvas.height);
	    }
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
				var type = 1 + Math.floor(Math.random() * levelConfig[level][2]);	// Difficulty
				//console.log('new bug type', type);
				var power = (level - (type+1) + Math.round((Math.random()-0.5)*2)); 
				if (type != 5 || fireflies < levelConfig[level][3]) {
    				if (type == 5) {
    				    if (aliveFireflies==0 && insectsKilled > levelConfig[level][0]/2 ) {
    				    //if ((baseTime - time) > levelConfig[level][1]/2) {
    				        fireflies++;
    				        var index = getInsect(type, power);
				            insectsCloud[index].floatOnScreen(canvas.width, canvas.height);
				        }
				    } else {
				        var index = getInsect(type, power);
				        insectsCloud[index].floatOnScreen(canvas.width, canvas.height);
				    }
				}
			}
		}	

		// handle frog's head movement (follows the mouse)
		if (frog.alive && !frog.shooting) {
			frog.lookAt(mouse);		
		}		
	}
	
	// handle insects
	aliveInsects = 0;
	aliveFireflies = 0;
	for (insect in insectsCloud) {
		var o = insectsCloud[insect];
		if(!o || !o.active) { continue; }
		
		// handle insect movement
		if(!o.cinema && outOfBounds(o, o.bounds)) {
			placeInBounds(o, o.bounds);
		}
		// handle insect's actions
		if (!o.action && !o.killed) {
		    if (o.type == 4) {  // Mosquito
		        if (frog.shooting && o.hitRadius(frog.tongueTarget.x, frog.tongueTarget.y, 50)) {
		            o.perform(1);
		        }
		    } else {
			    var nextAction = Math.round(((Math.random()-0.77)*5) * o.power);	// 23% probability
			    if (nextAction > 0) {
				    o.perform(nextAction);
				    //console.log('Insect '+insect+' performing action '+nextAction );
				}
			}
		} else if (o.action && o.type==1) {
			/*var g = d.graphics;
			g.beginStroke("#ff0000");
			g.setStrokeStyle(1)
			g.drawCircle(o.x,o.y, 1);*/
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
			//	handle frog collisions (not used now)
			if	(false && frog.alive && o.hitRadius(frog.x, frog.y, frog.hit)) {
				frog.die();
				messageField.text = "Estás frito. Pulsa aquí para jugar de nuevo";
				watchRestart();
				continue;
			}
				// handle tongue collisions
			if(frog.alive && o.hitRadius(frog.tonguePos.x, frog.tonguePos.y, frog.hit)) {
				this.score += o.score;
				if (o.type == 5) {  // firefly
				    baseTime+=10;
				}
				o.die();	// stops animation and follows tongue
				//SoundJS.play("punch");
				insectsKilled++;
				continue;
			}
			if (o.type == 5) aliveFireflies++;
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

function getInsect (type, power) {
	var i = 0;
	var len = insectsCloud.length;
	
	//console.log('Genera bicho tipo '+type+' con poder '+power);
	//pooling approach
	while(i <= len){
		if(!insectsCloud[i]) {
			insectsCloud[i] = new Insect(type, power);
			break;
		} else if(!insectsCloud[i].active) {
			insectsCloud[i].activate(type, power);
			break;
		} else {
			i++;
		}
	}
	
	if(len == 0) {
		insectsCloud[0] = new Insect(type, power);
	}
	
	stage.addChild(insectsCloud[i]);
	//console.log('Added insect type '+type+' to stage');
	return i;
}

function httpGet (theUrl, callback)
{
   var xmlHttp = null;

	//if (theUrl.indexOf("localhost")==-1) {
		xmlHttp = new XMLHttpRequest();
   	xmlHttp.open( "GET", theUrl, true);
   	xmlHttp.onreadystatechange = function() {
  			if (xmlHttp.readyState==4 && xmlHttp.status==200) {
   			try {
   				var result = []				
   				eval('result=' + xmlHttp.responseText);
   				callback(result);
   			} catch (e) {
   				alert('Failed to set High-Score!');
   			}
  			}
 		}
   	xmlHttp.send( null );

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
	//console.log('printing high scores',scoreList);
	content = '<ul class="highscores">';
	for (i in scoreList) {
		content += '<li><div class="score">'+scoreList[i][0]+'</div><div class="name">'+scoreList[i][1]+'</div></li>';
	}
	content += '</ul>';
	overlay.innerHTML = content;
	overlay.style.display = 'block';
	//console.log('Content',content);
	//console.log(overlay)
}

function outOfBounds (o, bounds) 
{
	//return false;
	//is it visibly off screen
	return o.x < bounds + canvas.width*0.4 || o.y < bounds || o.x > canvas.width-bounds || o.y > canvas.height-bounds;
}

function placeInBounds (o, bounds)
{
	//if its visual bounds are entirely off screen place it off screen on the other side
	if(o.x > canvas.width-bounds) {
		o.x = canvas.width-bounds;
	} else if (o.x < bounds + canvas.width * 0.4) {
		o.x = bounds + canvas.width * 0.4;
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
	<div id="fb-root"></div>
	<script>(function(d, s, id) {
   var js, fjs = d.getElementsByTagName(s)[0];
   if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/es_LA/all.js#xfbml=1&appId=226492570779543";
    fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
   </script>
	Sin sonidos, a ver qué tal va ahora...<?php if (in_array($_SERVER['HTTP_HOST']=='localhost' || $_SESSION['fb_user_id'],array('1236628420','752565913'))): ?><input id="wlevel" type="text" value="0" /><?php endif; ?>
	<div id="canvasWrapper" align="center" style="width: 640px; height: 480px">
		<div id="canvasOverlay" style="width: 640px; height: 480px"></div>
		<canvas width="640" height="480" id="stageCanvas" class="pantalla"></canvas>
		<fb:like href="https://apps.facebook.com/htmlgame_charca" send="false" width="640" show_faces="true" font="trebuchet ms"></fb:like>		
	</div>
</body>
</html>

