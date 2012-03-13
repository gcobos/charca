(function(window) {

//
function ParticleContainer() {
  this.initialize();
}
ParticleContainer.prototype = new Container();

// public properties:
    ParticleContainer.MAX_SIDES = 30;       // how many sides can have the polygon
	ParticleContainer.TOGGLE = 60;
	ParticleContainer.MAX_THRUST = 2;
	ParticleContainer.MAX_VELOCITY = 5;

// public properties:
	ParticleContainer.prototype.magnet = null;
	ParticleContainer.prototype.container = null;
	
	ParticleContainer.prototype.timeout = 0;
	ParticleContainer.prototype.thrust = 0;
	
	ParticleContainer.prototype.vX = 0;
	ParticleContainer.prototype.vY = 0;
	ParticleContainer.prototype.sides = 4;
	ParticleContainer.prototype.radius = 220;
	ParticleContainer.prototype.hit = 0;
	
// constructor:
	ParticleContainer.prototype.Container_initialize = ParticleContainer.prototype.initialize;	//unique to avoid overiding base class
	
	ParticleContainer.prototype.initialize = function() {
		this.Container_initialize();
		
		this.magnet = new Shape();
		this.container = new Shape();
		
		this.addChild(this.magnet);
		this.addChild(this.container);
		
		this.makeShape();
		this.timeout = 0;
		this.thrust = 0;
		this.vX = 0;
		this.vY = 0;
	}
	
// public methods:
	ParticleContainer.prototype.makeShape = function() {
		//draw ship body
		var g = this.container.graphics;
		g.clear();
		g.beginStroke("#22FF22").setStrokeStyle(8);
		
		if (this.sides < 3) {
		    g.drawCircle(0, 0, this.radius);
		} else {
		    g.drawPolyStar(0, 0, this.radius, this.sides, 0, this.rotation);
		}
		
		g = this.container.graphics;
		//g.clear();
		g.beginStroke("#FF2222").setStrokeStyle(16);
		
		var x = Math.cos(this.rotation*Math.PI/180) * this.radius;
		var y = Math.sin(this.rotation*Math.PI/180) * this.radius;
		
		var nextAngle = this.rotation + (360.0 / this.sides);
		
		var nx = Math.cos(nextAngle*Math.PI/180) * this.radius;
		var ny = Math.sin(nextAngle*Math.PI/180) * this.radius;
		//g.moveTo(0,0);
		//g.lineTo(10,20);
		g.moveTo(x,y);
		g.lineTo(nx,ny);
		//g.drawCircle(0, 0, this.radius);
		//console.log(x,y,nx,ny, nextAngle, this.rotation, 360.0 / this.sides);
		
		//furthest visual element
		this.hit = this.radius;
	}
	
	ParticleContainer.prototype.tick = function() {
		//move by velocity
		this.x += this.vX;
		this.y += this.vY;
		this.rotation ++;
		
		if (this.timeout >0) {
		    this.timeout --;
		}
		
		//with thrust flicker a flame every ParticleContainer.TOGGLE frames, attenuate thrust
		/*if(this.thrust > 0) {
			this.timeout++;
			this.magnet.alpha = 1;
			
			if(this.timeout > ParticleContainer.TOGGLE) {
				this.timeout = 0;
				if(this.magnet.scaleX == 1) {
					this.magnet.scaleX = 0.5;
					this.magnet.scaleY = 0.5;
				} else {
					this.magnet.scaleX = 1;
					this.magnet.scaleY = 1;
				}
			}
			this.thrust -= 0.5;
		} else {
			this.magnet.alpha = 0;
			this.thrust = 0;
		}*/
	}
	
	ParticleContainer.prototype.increaseSides = function ()
	{
	    if (this.sides < ParticleContainer.MAX_SIDES && this.timeout == 0) {
	        this.sides++;
	        this.timeout = 5;
	        this.makeShape();
	    }
	}
		
	ParticleContainer.prototype.decreaseSides = function ()
	{
	    if (this.sides > 3 && this.timeout == 0) {
	        this.sides--;
	        this.timeout = 5;
	        this.makeShape();
	    }
	}	
	
	ParticleContainer.prototype.rotateLeft = function ()
	{
	    if (this.timeout == 0) {
	        this.rotation -=  (360.0 / this.sides);
	        this.timeout = Math.round(ParticleContainer.MAX_SIDES / this.sides);
	        //this.makeShape();
	    }
	}
		
	ParticleContainer.prototype.rotateRight = function ()
	{
	    if (this.timeout == 0) {
	        this.rotation +=  (360.0 / this.sides);
	        this.timeout = Math.round(ParticleContainer.MAX_SIDES / this.sides);
	        //this.makeShape();
	    }
	}
	
	
	ParticleContainer.prototype.accelerate = function() {
		//increase push amount for acceleration
		this.thrust += this.thrust + 0.6;
		if(this.thrust >= ParticleContainer.MAX_THRUST) {
			this.thrust = ParticleContainer.MAX_THRUST;
		}
		
		//accelerate
		this.vX += Math.sin(this.rotation*(Math.PI/-180))*this.thrust;
		this.vY += Math.cos(this.rotation*(Math.PI/-180))*this.thrust;
		
		//cap max speeds
		this.vX = Math.min(ParticleContainer.MAX_VELOCITY, Math.max(-ParticleContainer.MAX_VELOCITY, this.vX));
		this.vY = Math.min(ParticleContainer.MAX_VELOCITY, Math.max(-ParticleContainer.MAX_VELOCITY, this.vY));
	}

window.ParticleContainer = ParticleContainer;
}(window));
