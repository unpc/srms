(function($) {

	o3djs.require('o3djs.util');
	o3djs.require('o3djs.math');
	o3djs.require('o3djs.rendergraph');
	o3djs.require('o3djs.camera');
	o3djs.require('o3djs.pack');
	o3djs.require('o3djs.arcball');
	o3djs.require('o3djs.effect');
	o3djs.require('o3djs.loader');
	

	function TargetCamera(viewInfo) {
	  this.eye = {
		  rotZ: -Math.PI / 3,
		  rotH: Math.PI / 3,
		  distanceFromTarget: 700 };
	  this.target = { x: 0, y: 0, z: 0 };
	  this.viewInfo = viewInfo;
	}
	
	TargetCamera.prototype.update = function() {
	  var target = [this.target.x, this.target.y, this.target.z];
	
	  this.eye.x = this.target.x + Math.cos(this.eye.rotZ) *
		  this.eye.distanceFromTarget * Math.sin(this.eye.rotH);
	  this.eye.y = this.target.y + Math.sin(this.eye.rotZ) *
		  this.eye.distanceFromTarget * Math.sin(this.eye.rotH);
	  this.eye.z = this.target.z + Math.cos(this.eye.rotH) *
		  this.eye.distanceFromTarget;
	
	  var eye = [this.eye.x, this.eye.y, this.eye.z];
	  var up = [0, 0, 1];
	  this.viewInfo.drawContext.view = o3djs.math.matrix4.lookAt(eye, target, up);
	};
	
	function initStep2(clientElements) {
		// Initializes global variables and libraries.
		var o3dElement = clientElements[0];
		var client = o3dElement.client;
		var o3d = o3dElement.o3d;
		
		$(document).unload(function(){
			client.cleanup();
		});
			
		// Creates a pack to manage our resources/assets
		var pack = client.createPack();
		
		// Create a view.
		var viewInfo = o3djs.rendergraph.createBasicView(
		  pack,
		  client.root,
		  client.renderGraphRoot);
		
		// viewInfo.performanceState.getStateParam('FillMode').value = o3d.State.WIREFRAME;

		var camera = new TargetCamera(viewInfo);
			
		function setClientSize() {
		  // Create a perspective projection matrix
		  viewInfo.drawContext.projection = o3djs.math.matrix4.perspective(
			Math.PI * 45 / 180, client.width / client.height, 0.1, 5000);
		}
		
		function scrollMe(e) {
			var raw = e.detail ? e.detail : -e.wheelDelta;
			if (raw < 0) {
				camera.eye.distanceFromTarget *= 11 / 12;
			} 
			else {
				camera.eye.distanceFromTarget *= (1 + 1 / 12);
			}
			camera.update();
			e.preventDefault();
		}

		setClientSize();

		var loader = o3djs.loader.createLoader(function(){
			
			client.setRenderCallback(setClientSize);
	
			var navBall = o3djs.arcball.create(client.width, client.height);
			$(window).resize(function(){
				setClientSize();
				navBall.setAreaSize(client.width, client.height);
			});
			
			var dragging = false;
			var thisRot = o3djs.math.matrix4.identity();
			var lastRot = o3djs.math.matrix4.identity();
			var lastPoint;
			
			o3djs.event.addEventListener(o3dElement, 'mousedown', function(e) {
				lastRot = thisRot;
				navBall.click([e.x, e.y]);
				dragging = true;
			});
	
			o3djs.event.addEventListener(o3dElement, 'mousemove', function(e) {
				if (!dragging) return;
				
				var deltaR = navBall.drag([e.x, e.y]);
				var deltaM = o3djs.quaternions.quaternionToRotation(deltaR);
				thisRot = o3djs.math.matrix4.mul(lastRot, deltaM);
				
				var m = client.root.localMatrix;
				o3djs.math.matrix4.setUpper3x3(m, thisRot);
				client.root.localMatrix = m;
			});
	
			o3djs.event.addEventListener(o3dElement, 'mouseup', function(e) {
				dragging = false;
			});
	
			// for Firefox
			o3dElement.addEventListener('DOMMouseScroll', scrollMe, false);
			// for Safari
			o3dElement.onmousewheel = scrollMe;
			
		});

		// Make a transform for the cube, attach the cube and parent it.
		var building = pack.createObject('Transform');
		building.parent = client.root;
		
		loader.loadScene (
			client, 
			pack, 
			building, 
			"house.o3dtgz", 
			function (pack, transform, exception) {
				if (exception) {
					alert("ERROR");
					return;
				}
				
				var transforms = pack.getObjectsByClassName('o3d.Transform');
				for (i in transforms) {
					var t = transforms[i];
					var d = t.getParam('diffuse');
					if (d) {
						d.value[3] = 0.5;
					}
					else {
						d = t.createParam('diffuse', 'ParamFloat4');
						d.value = [1,1,1,0.5];
					}
					
				}
				
				var materials = pack.getObjectsByClassName('o3d.Material');
				for (i in materials) {
					var m = materials[i];
					m.drawList = viewInfo.zOrderedDrawList;
				}

				camera.update();
			
	  			//viewInfo.performanceState.getStateParam('FillMode').value = o3dElement.o3d.State.WIREFRAME;
				
				// Generate draw elements and setup material draw lists.
				o3djs.pack.preparePack(pack, viewInfo);
								
			}
		);
		
		var computerPack = client.createPack();
		var computer = computerPack.createObject('Transform');
		loader.loadScene (
			client, 
			computerPack, 
			computer, 
			"computer.o3dtgz", 
			function (pack, parent, exception) {
				if (exception) {
					alert("ERROR");
					return;
				}
				
				o3djs.pack.preparePack(pack, viewInfo);
								
				var shapes = pack.getObjectsByClassName('o3d.Shape');
				for (var i=0; i< 10; i++) {
					
					  var transform = pack.createObject('Transform');
					  transform.parent = client.root;
					  
					  var j;
					  for (j in shapes) {
					  	transform.addShape(shapes[j]);
					  }
					  
					  var angles = [
						  0,
						  0,
						  i / 5 * Math.PI];
						  
					  transform.translate(i*5-50, i*5 -50, 30);
					  transform.rotateZYX(angles);

				}

	  			//viewInfo.performanceState.getStateParam('FillMode').value = o3dElement.o3d.State.WIREFRAME;
				
				// Generate draw elements and setup material draw lists.
			}
		);
		
		loader.finish();

	}
	
	$(function(){
		o3djs.util.makeClients(initStep2);
	});

	
	
})(jQuery);
