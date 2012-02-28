(function(window) {

//
function Frog() {
  this.initialize();
}
	Frog.prototype = new Container();

// public static properties:
	Frog.TONGUE_HIT = 15;			// tongue hits radius
	Frog.TONGUE_SPEED = 28;		// how fast the tongue moves
	Frog.TONGUE_INIT = 0;		// how many ticks after the origin begins to draw the tongue

	Frog.head_img = "images/frog_head.png";
	Frog.head2_img = "images/frog_head2.png";
	Frog.body_img = "images/frog_body.png";
	Frog.dead_img = "images/frog_dead.png";

// public properties:
	Frog.prototype.head = null;
	Frog.prototype.head2 = null;
	Frog.prototype.body = null;
	Frog.prototype.dead = null;
	
	Frog.prototype.addedToStage = false;	
	Frog.prototype.alive = false;
	Frog.prototype.shooting = false;

	// Tongue variables	
	Frog.prototype.tongue = null;
	Frog.prototype.tongueIsBack = false;
	Frog.prototype.angle = null;
	Frog.prototype.hit = 0;
	Frog.prototype.tongueMaxLength = 0;
	Frog.prototype.tongueLength = null;
	Frog.prototype.tonguePos = {x: 0, y: 0};
	Frog.prototype.tongueDelta = {x: 0, y: 0};
	Frog.prototype.tongueOrig = {x: 0, y: 0};
	Frog.prototype.tongueTarget = {x: 0, y: 0};
	
// constructor:
	Frog.prototype.Container_initialize = Frog.prototype.initialize;	//unique to avoid overiding base class
	
	Frog.prototype.initialize = function() {
		this.Container_initialize();

		img = new Image();
		img.src = Frog.head_img;
		var head = new Bitmap(img);
		img = new Image();
		img.src = Frog.head2_img;
		var head2 = new Bitmap(img);
		img = new Image();
		img.src = Frog.body_img;
		var body = new Bitmap(img);
		img = new Image();
		img.src = Frog.dead_img;
		var dead = new Bitmap(img);
		
		this.body = this.addChild(body);
		this.dead = this.addChild(dead);
		this.head = this.addChild(head);
		this.head2 = this.addChild(head2);		

		var tongue = new Shape();
		this.tongue = this.addChild(tongue);		

		this.head2.visible = false;
		this.dead.visible = false;
		
		this.alive = false;
		this.shooting = false;
		this.tongueIsBack = false;
		this.tonguePos = {x: 0, y: 0};
		this.tongueDelta = {x: 0, y: 0};
		this.tongueTarget = {x: 0, y: 0};

		// Relative positions for head and its rotation center
		this.head.x = 36;
		this.head.y = -15;
		this.head.regX = 41;		// rotation center
		this.head.regY = 45;
		this.dead.x = -30;
		this.dead.y = -30;
		
		this.head2.x = this.head.x;
		this.head2.y = this.head.y;
		this.head2.regX = this.head.regX;	// rotation center
		this.head2.regY = this.head.regY;
		this.tongue.x = this.head.x;
		this.tongue.y = this.head.y;
	}
		
	Frog.prototype.tick = function() {
		// place frog and tongue
		if (this.alive) {
			this.dead.visible = false;
			this.body.visible = true;
			if (this.shooting) {
				this.head.visible = false;
				this.head2.visible = true;
				this.head2.rotation = this.angle;
			} else {
				this.head.visible = true;
				this.head2.visible = false;
				this.head.rotation = this.angle;
			}
			
			//move tongue if shooting
			if (this.shooting) {
				if (!this.tongueIsBack) {
					// tongue hits only when is almost at the target point
					if (this.tongueLength > this.tongueMaxLength - 2) {
						this.hit = Frog.TONGUE_HIT;
					}
					if (this.tongueLength  >= this.tongueMaxLength ) {
						this.tongueIsBack = true;
						this.hit = 0;
					}
					this.tonguePos.x += this.tongueDelta.x;
					this.tonguePos.y += this.tongueDelta.y;
					this.tongueLength++;
				} else {
					if (this.tongueLength <= Frog.TONGUE_INIT+1) {
						this.shooting = false;
						this.tongueIsBack = false;
					}			
					this.tonguePos.x -= this.tongueDelta.x;
					this.tonguePos.y -= this.tongueDelta.y;
					this.tongueLength--;					
				}
				
				// draws tongue
				var g = this.tongue.graphics;
				g.clear();
				if (this.shooting && this.alive) {
					g.setStrokeStyle(8);										// Tongue thickness
					g.beginStroke(Graphics.getRGB(210,128,128, 1.0));	// Tongue color
					g.moveTo(this.tongueOrig.x - this.x - this.tongue.x, this.tongueOrig.y - this.y -this.tongue.y);					
					g.drawCircle(this.tonguePos.x - this.x -this.tongue.x,this.tonguePos.y - this.y -this.tongue.y,2);
					//g.lineTo(this.tonguePos.x - this.x -this.tongue.x,this.tonguePos.y - this.y -this.tongue.y);
					
				}							
			}			
		}			
	}
	
	Frog.prototype.lookAt = function ( target ) {
		this.tongueOrig.x = this.x + this.tongue.x;
		this.tongueOrig.y = this.y + this.tongue.y;
		this.tongueTarget.x = target.x;
		this.tongueTarget.y = target.y;
		var angle = angleBetweenPoints(this.tongueOrig, this.tongueTarget);
		if (angle < -60) angle = -60;
		if (angle > 45) angle = 45;
		//console.log('angle',angle);
		this.angle = angle;
	}	
	
	Frog.prototype.shoot = function ( target ) {
		this.lookAt(target);
		this.tongueDelta.x = Math.cos(this.angle*(Math.PI/180)) * Frog.TONGUE_SPEED;
		this.tongueDelta.y = Math.sin(this.angle*(Math.PI/180)) * Frog.TONGUE_SPEED;
		this.tongueMaxLength = modulusOfVector(makeVector(this.tongueOrig, this.tongueTarget)) / Frog.TONGUE_SPEED;
		this.tongueLength = Frog.TONGUE_INIT;
		this.tongueOrig.x = this.tongueOrig.x + this.tongueDelta.x * Frog.TONGUE_INIT;
		this.tongueOrig.y = this.tongueOrig.y + this.tongueDelta.y * Frog.TONGUE_INIT;
		this.tonguePos.x = this.tongueOrig.x;
		this.tonguePos.y = this.tongueOrig.y;
		this.tongueIsBack = false;
		this.shooting = true;
		this.hit = 0;
	}

	Frog.prototype.die = function () {
		this.alive = false;
		this.head.visible = false;
		this.head2.visible = false;
		this.body.visible = false;
		this.dead.visible = true;
		this.shooting = false;
		this.tongueIsBack = false;
		this.tonguePos = {x: 0, y: 0};
		this.tongueDelta = {x: 0, y: 0};
		this.tongueTarget = {x: 0, y: 0}; 
		var g = this.tongue.graphics;
		g.clear();
	}

window.Frog = Frog;
}(window));