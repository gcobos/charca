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
<!--	<link rel="shortcut icon" href="images/favicon.ico">
	<meta charset="utf-8">
	<meta name="author" content="Gonzalo Cobos, Jaime Cobos" >
	<meta name="keywords" content="html5, game, charca, rana, insectos, swamp, bugs">  
	<meta name="robots" content="index,follow">
 	<meta property="game:title" content="Charca" />
 	<meta property="og:type" content="game" />
 	<meta property="fb:app_id" content="226492570779543" />
 	<meta property="og:image" content="https://charca.herokuapp.com/images/title.jpg" /> 
   <meta property="og:description" content="Ayuda a esta rana a mantener la charca limpia de insectos y a pegarse un atracón padre" />  
--> 
	<title>Prueba laser</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			border: 0;
			background: black;
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
   <script src="javascript/particle_container.js"></script>
   <script src="javascript/particle.js"></script>
<script>

// Defines 


var KEYCODE_SPACE = 32;		//usefull keycode
var KEYCODE_UP = 38;		//usefull keycode
var KEYCODE_LEFT = 37;		//usefull keycode
var KEYCODE_RIGHT = 39;		//usefull keycode
var KEYCODE_DOWN = 40;		//usefull keycode
var KEYCODE_W = 87;			//usefull keycode
var KEYCODE_A = 65;			//usefull keycode
var KEYCODE_D = 68;			//usefull keycode
var KEYCODE_S = 83;			//usefull keycode

// Configuration for every level  [ number of particles, time ]
var levelConfig = { 
	0: [1, 100],
	1: [2, 100],
	2: [3, 100],
	3: [4, 100],
};

// Variables

var canvas;			      // main canvas
var stage;			      // main display stage
var overlay;				// canvas overlay
var mouse;					// mouse position

var splash;					// splash image 

var particles;			// array of particles
var particleContainer;

var playing;				// true when in game mode 
var score = 0;				// actual score
var level = 0; 			// actual level
var time = 0;				// actual time left
var timer;
var baseTime;

//reset key presses
var shootHeld =	false;
var lfHeld =	false;
var rtHeld =	false;
var upHeld =	false;
var dwHeld =	false;


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

	//ensure stage is blank and add the particleContainer
	stage.clear();
	
	// Splash window
	splash = new Image();
	splash.src = "images/title.jpg";
	splash.onload = function () {
		var bitmap = new Bitmap(splash);
       	bitmap.x = 0;
       	bitmap.y = 0;
		stage.addChild(bitmap);

//reset key presses
	shootHeld =	false;
	lfHeld =	false;
	rtHeld =	false;
	upHeld =	false;
	dwHeld =	false;
	
		// create the container
		particleContainer = new ParticleContainer();
		particleContainer.x = canvas.width * 0.5;
		particleContainer.x = canvas.height * 0.5;
	
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

		messageField = new Text("Pulsa aquí para jugar", "bold 24px Arial", "#ffffff");
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
	/*if (!particleContainer.alive) {
		httpGet('<?php echo $base_url ?>'+'?func=scores&v='+score, function (scores) {
			scoreList = scores;
			//console.log('Score list',scores);
			showHighScores();	
		});
	}*/

	// Wait before giving control
	clearTimeout(timer);
	var wait = 100;
	if (score) {
		wait = 500;
	}
	
	
	//register key functions
    document.onkeydown = handleKeyDown;
    document.onkeyup = handleKeyUp;

	
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
	if (!particleContainer.alive || level == 0) {
		level = wlevel;
		score = 0;
	}
	time = levelConfig[level][1];
	particleContainer.alive = true;
	particleContainer.shooting = false;
	particleContainer.x = canvas.width * 0.5;
	particleContainer.y = canvas.height * 0.5;

	// new arrays to dump old data
	particles = new Array();
	

	//ensure stage is blank and add the particleContainer
	stage.clear();

    stage.addChild(particleContainer);

    //d = new Shape();
    //stage.addChild(d);

    if (level == 10) {
	    var index = getParticle(6, 3);
    	particles[index].floatOnScreen(canvas.width, canvas.height);
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

function tick() {

	if (playing) {
		// handle time limits
		if (time <= 0) {
			particleContainer.die();
			messageField.text = "Estás frito.\nPulsa aquí para jugar de nuevo";
			watchRestart();
		}

	//handle turning
	if(lfHeld){
		particleContainer.rotation += (360.0 / particleContainer.sides);
	} else if(rtHeld) {
		particleContainer.rotation -=  (360.0 / particleContainer.sides);
	}

	if(upHeld){
	    
		particleContainer.increaseSides();
	} else if(dwHeld) {
		particleContainer.decreaseSides();
	}

/*
		// handle new insects
		if ( aliveParticles < Math.min(MAX_SIDES,levelConfig[level][0]) && (insectsKilled + aliveParticles) < levelConfig[level][0]) {
			if (particleContainer.alive) {
				var type = 1 + Math.floor(Math.random() * levelConfig[level][2]);	// Difficulty
				//console.log('new bug type', type);
				var power = (level - (type+1) + Math.round((Math.random()-0.5)*2)); 
				if (type != 5 || fireflies < levelConfig[level][3]) {
    				if (type == 5) {
    				    if (aliveFireflies==0 && insectsKilled > levelConfig[level][0]/2 ) {
    				        fireflies++;
    				        var index = getParticle(type, power);
				            particles[index].floatOnScreen(canvas.width, canvas.height);
				        }
				    } else {
				        var index = getParticle(type, power);
				        particles[index].floatOnScreen(canvas.width, canvas.height);
				    }
				}
			}
		}	

		// handle particleContainer's head movement (follows the mouse)
		if (particleContainer.alive && !particleContainer.shooting) {
			particleContainer.lookAt(mouse);		
		}
*/
	}
	
/*
	// handle insects
	aliveParticles = 0;
	aliveFireflies = 0;
	for (insect in particles) {
		var o = particles[insect];
		if(!o || !o.active) { continue; }
		
		// handle insect movement
		if(!o.cinema && outOfBounds(o, o.bounds)) {
			placeInBounds(o, o.bounds);
		}
		// handle insect's actions
		if (!o.action && !o.killed) {
		    if (o.type == 4) {  // Mosquito
		        if (particleContainer.shooting && o.hitRadius(particleContainer.tongueTarget.x, particleContainer.tongueTarget.y, 50)) {
		            o.perform(1);
		        }
		    } else {
			    var nextAction = Math.round(((Math.random()-0.77)*5) * o.power);	// 23% probability
			    if (nextAction > 0) {
				    o.perform(nextAction);
				    //console.log('Particle '+insect+' performing action '+nextAction );
				}
			}
		}	
		
		if (o.killed && particleContainer.tongueIsBack) {
			o.x = particleContainer.tonguePos.x;
			o.y = particleContainer.tonguePos.y;
		} else if (o.killed) {
			o.active = false;
			stage.removeChild(o);
		}
		o.tick();
	
		if (playing) {
			//	handle particleContainer collisions (not used now)
			if	(false && particleContainer.alive && o.hitRadius(particleContainer.x, particleContainer.y, particleContainer.hit)) {
				particleContainer.die();
				messageField.text = "Estás frito.\nPulsa aquí para jugar de nuevo";
				watchRestart();
				continue;
			}
			
			// handle tongue collisions
			if (particleContainer.alive && o.hitRadius(particleContainer.tonguePos.x, particleContainer.tonguePos.y, particleContainer.hit)) {
				this.score += o.score;
			    o.life -= 1;
			    if (o.life <= 0) {
			        o.die();	// stops animation and follows tongue
				    insectsKilled++;
				    aliveParticles--;
				    //console.log('Killed!');
			    }
			    if (o.type == 5) {  // firefly
				    baseTime+=10;
				}
				//SoundJS.play("punch");
			}
			if (o.type == 5) aliveFireflies++;
			aliveParticles++;				
		}

		// handle the end of the level
		if (insectsKilled >= levelConfig[level][0]) {
		    if (level < 10) {
			    messageField.text = "¡Has pasado al siguiente nivel!\nPulsa aquí para continuar.";
			    level += 1;
			    score += time * 5;
			    insectsKilled = 0;
			    watchRestart();
			} else if (aliveParticles <= 0) {
			    messageField.text = "¡Enhorabuena!\n\nHas derrotado al bicho gordo de la charca!!\nAhora el mundo es un poquito más feliz,\nexcepto para la familia del bicho gordo!!!";
			    score += 1000;
			    particleContainer.alive = false;
			    insectsKilled = 0;
			    level = 0;
			    watchRestart();
			}
		}
		//console.log(aliveParticles);
	}
*/
	//call sub ticks
	particleContainer.tick();
	
	stage.update();
}

// Helper functions

function getParticle (type, power) {
	var i = 0;
	var len = particles.length;
	
	//console.log('Genera bicho tipo '+type+' con poder '+power);
	//pooling approach
	while(i <= len){
		if(!particles[i]) {
			particles[i] = new Particle(type, power);
			break;
		} else if(!particles[i].active) {
			particles[i].activate(type, power);
			break;
		} else {
			i++;
		}
	}
	
	if(len == 0) {
		particles[0] = new Particle(type, power);
	}
	
	stage.addChild(particles[i]);
	//console.log('Added insect type '+type+' to stage');
	return i;
}

function httpGet (theUrl, callback)
{
   var xmlHttp = null;

	if (theUrl.indexOf("localhost")==-1) {
		xmlHttp = new XMLHttpRequest();
       	xmlHttp.open( "GET", theUrl, true);
       	xmlHttp.onreadystatechange = function() {
      			if (xmlHttp.readyState==4 && xmlHttp.status==200) {
       			try {
       				var result = []				
       				eval('result=' + xmlHttp.responseText);
       				callback(result);
       			} catch (e) {
       				alert('Failed to set High-Score! ');
       			}
      			}
     		}
       	xmlHttp.send( null );
   	}

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
	if (playing && particleContainer.alive) {
	 	if (!particleContainer.shooting) {
	 		particleContainer.shoot(mouse);
		}
	} else {
		stage.removeChild(messageField);
		restart();
	}
	e.preventDefault();
}

//allow for WASD and arrow control scheme
function handleKeyDown(e) {
	//cross browser issues exist
	if(!e){ var e = window.event; }
	
	switch(e.keyCode) {
		case KEYCODE_SPACE:	shootHeld = true; break;
		case KEYCODE_A:
		case KEYCODE_LEFT:	lfHeld = true; break;
		case KEYCODE_D:
		case KEYCODE_RIGHT: rtHeld = true; break;
		case KEYCODE_W:
		case KEYCODE_UP:	upHeld = true; break;
		case KEYCODE_S:
		case KEYCODE_DOWN:	dwHeld = true; break;
	}
}

function handleKeyUp(e) {
	//cross browser issues exist
	if(!e){ var e = window.event; }
	switch(e.keyCode) {
		case KEYCODE_SPACE:	shootHeld = false; break;
		case KEYCODE_A:
		case KEYCODE_LEFT:	lfHeld = false; break;
		case KEYCODE_D:
		case KEYCODE_RIGHT: rtHeld = false; break;
		case KEYCODE_W:
		case KEYCODE_UP:	upHeld = false; break;
		case KEYCODE_S:
		case KEYCODE_DOWN:	dwHeld = false; break;
	}
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

