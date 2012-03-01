(function(window) {

//
function Insect(type) {
	this.initialize(type);
}

Insect.prototype = new Container();

// static properties:

	Insect.types = 4;
	
	Insect.typeImages = {};	
	
	Insect.typeFrames = {	// frames for each insect type
		1: {width:80, height:80, regX:40, regY:40},
		2: {width:80, height:80, regX:40, regY:40},
		3: {width:80, height:80, regX:40, regY:40},
		4: {width:58, height:47, regX:25, regY:24},
	};		
	
	Insect.typeAnimations = {	// animations for every insect type
		1:	{ 
				fly: [0,2, "fly"],	//attack: [20,39,"fly"],
			}, 
		2:	{ 
				fly: [0,2, "fly"],	//attack: [20,39,"fly"],
			}, 
		3:	{ 
				fly: [0,2, "fly"],	//attack: [20,39,"fly"],
			}, 
		4:	{ 
				fly: [0,2, "fly"],	//attack: [20,39,"fly"],
			}, 
	};

// public properties:
	
	Insect.prototype.type = 0;		// keeps the type of insect of the instance
	Insect.prototype.bounds = 0;	// distance to keep from the walls
	
	Insect.prototype.bmpAnimation = null;

	Insect.prototype.hit = 30;		//average radial disparity
	
	Insect.prototype.speed = 0;		//speed ammount
	Insect.prototype.score = 0;	//score value
		
	Insect.prototype.active = false;	//is it active
	
	Insect.prototype.killed = false;	// true when it's trapped by the tongue
	
// constructor:
	Insect.prototype.Container_initialize = Insect.prototype.initialize;	//unique to avoid overiding base class
	
	Insect.prototype.initialize = function (type) {
		this.Container_initialize(); // super call
		if (!Object.keys(Insect.typeImages).length) {
			var i = 1;
			while (i <= Insect.types) {
				Insect.typeImages[i] = new Image();
				Insect.typeImages[i].src = "images/insect"+i+".png";
				i++; 
			}
		}
		this.activate(type);
	}

// public methods:
	
	//handle reinit for poolings sake
	Insect.prototype.activate = function (type) {
		this.type = type;
		
		// Clean previous animation
		this.removeAllChildren();
		var spriteSheet = new SpriteSheet({
			images: [Insect.typeImages[this.type] ],
			frames: Insect.typeFrames[this.type],				
			animations: Insect.typeAnimations[this.type],
		});
			
		// to save file size, the loaded sprite sheet only includes right facing animations
		// we could flip the display objects with scaleX=-1, but this is expensive in most browsers
		// instead, we append flipped versions of the frames to our sprite sheet
		// this adds only horizontally flipped frames:
		//SpriteSheetUtils.addFlippedFrames(spriteSheet, true, false, false);	// Error??		
		
		this.bmpAnimation = new BitmapAnimation(spriteSheet);
		
		this.addChild(this.bmpAnimation);

		// start playing the first sequence:
		this.bmpAnimation.gotoAndPlay("fly");		//animate	
		this.bounds = Insect.typeFrames[type].width / 2;
		this.speed = (Math.random() + 1.8 )* type;
		this.score = Math.round(type * 5);
		this.active = true;
		this.killed = false;
	}
	
	//handle what a Insect does to itself every frame
	Insect.prototype.tick = function () {
		if (!this.killed) {
			this.x += (Math.random()-0.5) * this.speed;
			this.y += (Math.random()-0.5) * this.speed;
		}			
	}
	
	//position the Insect so it floats on screen
	Insect.prototype.floatOnScreen = function(width, height) {
		//base bias on real estate and pick a side or top/bottom
		this.x = width * 0.5 + Math.random() * width * 0.4;
		this.y = height * 0.1 + Math.random() * height * 0.8;
	}
	
	// Sets the insect as killed
	Insect.prototype.die = function () {
		//this.active = false;
		this.killed = true;
		this.bmpAnimation.rotation = Math.random() * 360;
		this.bmpAnimation.gotoAndStop("fly");		
	}	
	
	Insect.prototype.hitRadius = function(tX, tY, tHit) {
		if (this.hit==0 || tHit==0) return ;
		//early returns speed it up
		//console.log(tX, tY, tHit);
		if(tX - tHit > this.x + this.hit) { return; }
		if(tX + tHit < this.x - this.hit) { return; }
		if(tY - tHit > this.y + this.hit) { return; }
		if(tY + tHit < this.y - this.hit) { return; }
		
		//now do the circle distance test
		return this.hit + tHit > Math.sqrt(Math.pow(Math.abs(this.x - tX), 2) + Math.pow(Math.abs(this.y - tY), 2));
	}

window.Insect = Insect;
}(window));