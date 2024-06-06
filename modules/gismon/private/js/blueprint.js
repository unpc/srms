/*
url: http://location/path/to/map.%id?floor=%floor&x=%x&y=%y&zoom=%zoom
*/

var Blueprint = {

	PPM: 10,					//zoom = 0时, 每米像素值
	TILE_WIDTH: 256,			//zoom = 0时, 每块拼图宽度多少像素
	TILE_HEIGHT: 256,			//zoom = 0时, 每块拼图宽度多少像素
	MAX_ZOOM_LEVEL: 4,

	foo: 0
};

//平面图
Blueprint.Plan = function($container, opt) {
	
	var $ = jQuery;

	var plan = this;
	
	
	plan.addDeviceEvent = 'blueprint.add_device';
	plan.floors = 1;
	plan.zoomLevel = 0;
	plan.minZoomLevel = 0;
	plan.maxZoomLevel = Blueprint.MAX_ZOOM_LEVEL;
	plan.currentTiles = {};
	plan.floor = 0;
	plan.center = [0, 0];
	plan.zoomLevel = 0;
	plan.deviceIsDraggable = false;
	plan.animationDuration = opt.animationDuration || 400;
	
	$.extend(plan, opt||{});
	
	plan.minZoomLevel = Math.max(parseInt(plan.minZoomLevel, 10), 0);
	plan.maxZoomLevel = Math.min(parseInt(plan.maxZoomLevel, 10), Blueprint.MAX_ZOOM_LEVEL);

	$container.empty();
	$container.css({overflow:'hidden', position:'relative'});

	plan.$container = $container;	
	plan.devices = {};
	
	// 绑定事件
	plan.bindEvents();

	plan.standardize();
	plan.zoomTo(0);
	plan.setFloor(0);
	plan.render();
};

Blueprint.zoomPow = [1,2,4,8,16,32,64];

Blueprint.Plan.prototype.P2M = function(locP, zoomLevel) {
	if (arguments.length == 1) {
		zoomLevel = this.zoomLevel;
	}
	var ratio = Blueprint.zoomPow[zoomLevel] * Blueprint.PPM;
	return [parseFloat(locP[0]) / ratio, parseFloat(locP[1]) / ratio ];
};

Blueprint.Plan.prototype.M2P = function(locM, zoomLevel) {
	if (arguments.length == 1) {
		zoomLevel = this.zoomLevel;
	}
	var ratio = Blueprint.zoomPow[zoomLevel] * Blueprint.PPM;
	return [Math.round(locM[0] * ratio), Math.round(locM[1] * ratio)];
};

Blueprint.Plan.prototype.rectP2M = function(rectP, zoomLevel) {
	if (arguments.length == 1) {
		zoomLevel = this.zoomLevel;
	}
	var ratio = Blueprint.zoomPow[zoomLevel] * Blueprint.PPM;
	return {
			left:parseFloat(rectP.left) / ratio, 
			top: parseFloat(rectP.top) / ratio,
			width: parseFloat(rectP.width) /ratio,
			height: parseFloat(rectP.height) /ratio
	};
};

Blueprint.Plan.prototype.rectM2P = function(rectM, zoomLevel) {
	if (arguments.length == 1) {
		zoomLevel = this.zoomLevel;
	}
	var ratio = Blueprint.zoomPow[zoomLevel] * Blueprint.PPM;
	return {
			left: Math.round(rectM.left * ratio), 
			top: Math.round(rectM.top * ratio),
			width: Math.round(rectM.width * ratio),
			height: Math.round(rectM.height * ratio)
	};
};

Blueprint.Plan.prototype.tileId = function(locP, zoomLevel) {
	if (arguments.length == 1) {
		zoomLevel = this.zoomLevel;
	}
	return [this.floor, zoomLevel, locP[0], locP[1]].join('_');
};

Blueprint.Plan.prototype.makeTileUrl = function(locP) {
	return this.tileUrl
			.replace('%floor', this.floor)
			.replace('%zoom', this.zoomLevel)
			.replace('%x', locP[0])
			.replace('%y', locP[1]);
};

Blueprint.Plan.prototype.makeDeviceUrl = function(rectM) {
	return this.deviceUrl
			.replace('%floor', this.floor)
			.replace('%left', rectM.left)
			.replace('%top', rectM.top)
			.replace('%width', rectM.width)
			.replace('%height', rectM.height);
};

Blueprint.Plan.prototype.tileRect = function() {
	var rect = {};
	var tw, th;
	
	var left = this.rectP.left;
	var top = this.rectP.top;
	var width = this.rectP.width;
	var height = this.rectP.height;
	
	tw = Blueprint.TILE_WIDTH;
	th = Blueprint.TILE_HEIGHT;
	
	rect.left = Math.floor(left / tw);
	rect.top = Math.floor(top / th);
	rect.width = Math.ceil((left + width) / tw) - rect.left;
	rect.height = Math.ceil((top + height) / th) - rect.top;

	return rect;
};

Blueprint.Plan.prototype.standardize = function(accordingCenter) {
	var plan = this;
	
	var width = plan.$container.width();
	var height = plan.$container.height();

	var halfWidth = Math.round(width / 2);
	var halfHeight = Math.round(height / 2);

	var centerP, adjusted = false;
	
	if (accordingCenter === false && plan.rectP) {
		centerP = [plan.rectP.left + halfWidth, plan.rectP.top + halfHeight];
		adjusted = true;
	}
	else {
		var center = plan.center;
		centerP = plan.M2P(center);
		
		if (plan.size) {

			var sizeP = plan.M2P(plan.size);

			if (centerP[0] > sizeP[0] - halfWidth) {
				centerP[0] = sizeP[0] - halfWidth;
				adjusted = true;
			}
			
			if (centerP[1] > sizeP[1] - halfHeight) {
				centerP[1] = sizeP[1] - halfHeight;
				adjusted = true;
			}
			
		}

	}
		
	var left = centerP[0] - halfWidth;
		
	if (left < 0) {
		left = 0;
		centerP[0] = halfWidth;
		adjusted = true;
	}
		
	var top = centerP[1] - halfHeight;
	if (top <  0) {
		top = 0;
		centerP[1] = halfHeight;
		adjusted = true;
	}

	if (adjusted) {
		plan.center = plan.P2M(centerP);
	}
	
	plan.centerP = centerP;

	plan.rectP = {
		left: left, top: top,
		width: width, height: height
	};
	
	plan.rect = plan.rectP2M(plan.rectP);

	return plan;
};

Blueprint.Plan.prototype.setCenter = function(locM) {
	this.center = locM;
	this.standardize();
	return this;
};

function makeArray(object) {
	var arr = [];
	for(var i in object) {
		if (object.hasOwnProperty(i)) {
			arr.push(object[i]);
		}
	}
	return arr;
}

Blueprint.Plan.prototype.zoomTo = function(level) {
	var oldLevel = this.zoomLevel;
	level = Math.min(Math.max(parseInt(level,10), this.minZoomLevel), this.maxZoomLevel);
	this.zoomLevel = level;
	if (this.zoomLevel != oldLevel && this.zoomChange) {
		this.zoomChange.call(this, oldLevel, this.zoomLevel);
	}
	if (oldLevel != this.zoomLevel) {
		var plan = this;
		var currentTiles = makeArray(this.currentTiles);
		var max = currentTiles.length;
		delete this.currentTiles;

		plan.standardize();

		$.each(currentTiles, function(i, tile) {
			tile.zoomTo(level, function(){
				this.$img.remove();
				max --; 
				if (max == 0) {
					plan.renderTiles();
				}
			});
		});

		plan.renderDevices(true);

	}
	return this;
};

Blueprint.Plan.prototype.setFloor = function(floor) {
	var oldFloor = this.floor;
	this.floor = Math.max(Math.min(floor, this.floors - 1), 0);

	var $floor = $('<div class="floor">'+ (this.floor+1)+ 'F</div>');
	this.$container.find('.floor').remove();
	this.$container.append($floor);
	
	if (oldFloor != this.floor) {

		var plan = this;

		var currentTiles = makeArray(this.currentTiles);
		var direction = (this.floor < oldFloor);
		delete this.currentTiles;

		plan.standardize();

		$.each(currentTiles, function(i, tile) {
			tile.fadeOut(direction);
		});

		plan.renderTiles(function(){
			this.fadeIn(direction);
		});

		plan.removeAllDevices();
		plan.renderDevices();

		if (this.floorChange) {
			this.floorChange.call(this, oldFloor, this.floor);
		}

	}

	return this;
};

Blueprint.Tile = function(plan, x, y) {
	this.plan = plan;

	//加载拼图
	var $img = $('<img class="tile loading" />');

	$img[0].src = plan.makeTileUrl([x, y]);
	
	plan.$container.append($img);

	this.$img = $img;
	this.floor = plan.floor;
	this.zoomLevel = plan.zoomLevel;

	this.x = x;
	this.y = y;

	this.left = parseInt(x * Blueprint.TILE_WIDTH, 10);
	this.top = parseInt(y * Blueprint.TILE_HEIGHT, 10);

	var left = plan.rectP.left;
	var top = plan.rectP.top;

	this.$img
	.css({
		position: 'absolute', 
		display: 'block',
		opacity: 0
	});

};

Blueprint.Tile.prototype.zoomTo = function(level, callback) {
	var plan = this.plan;
	var tile = this;
	var left = plan.rectP.left;
	var top = plan.rectP.top;

	var zoomRatio = Math.pow(2,  plan.zoomLevel - tile.zoomLevel);
		
	tile.$img
	.stop(true)
	.animate({
		left: tile.left * zoomRatio - left, top: tile.top * zoomRatio - top  ,
		width: Blueprint.TILE_WIDTH * zoomRatio, height: Blueprint.TILE_HEIGHT * zoomRatio
	}, 
	plan.animationDuration, 
	function(){
		callback.apply(tile);
	});

};

Blueprint.Tile.prototype.fadeIn = function(direction) {
	var tile = this;
	var plan = this.plan;
	var centerP = plan.centerP;
	var left = plan.rectP.left;
	var top = plan.rectP.top;

	if (direction) {
		tile.$img
		.css({
			left: (tile.left - centerP[0]) *4 + centerP[0] - left, top: (tile.top - centerP[1]) * 4  + centerP[1] - top,
			width: Blueprint.TILE_WIDTH * 4, height: Blueprint.TILE_HEIGHT * 4,
			opacity: 0
		});
	}
	else {
		tile.$img
		.css({
			left: (tile.left - centerP[0]) / 4 + centerP[0] - left, top: (tile.top - centerP[1]) / 4  + centerP[1] - top,
			width: Blueprint.TILE_WIDTH / 4, height: Blueprint.TILE_HEIGHT / 4,
			opacity: 0
		});
	}

	tile.$img
	.animate({
			left: tile.left - left, top: tile.top - top,
			width: Blueprint.TILE_WIDTH , height: Blueprint.TILE_HEIGHT,
			opacity: 1
	}, plan.animationDuration);
};

Blueprint.Tile.prototype.fadeOut = function(direction) {
	var tile = this;
	var plan = this.plan;
	var centerP = plan.centerP;
	var left = plan.rectP.left;
	var top = plan.rectP.top;

	function _img_remove() {
		$(this).remove();
	}

	if (direction) {
		tile.$img
		.stop(true)
		.animate({
			left: (tile.left - centerP[0]) / 4 + centerP[0] - left, top: (tile.top - centerP[1]) / 4  + centerP[1] - top,
			width: Blueprint.TILE_WIDTH / 4, height: Blueprint.TILE_HEIGHT / 4,
			opacity: 0
		}, plan.animationDuration, _img_remove);
	}
	else {
		tile.$img
		.stop(true)
		.animate({
			left: (tile.left - centerP[0]) *4 + centerP[0] - left, top: (tile.top - centerP[1]) * 4  + centerP[1] - top,
			width: Blueprint.TILE_WIDTH * 4, height: Blueprint.TILE_HEIGHT * 4,
			opacity: 0
		}, plan.animationDuration, _img_remove);
	}

};

Blueprint.Plan.prototype.renderTiles = function(callback) {

	var plan = this;
	
	if (!plan.tileUrl) return;
	
	plan.currentTiles = plan.currentTiles || {};

	var currentTiles = plan.currentTiles;

	var left = plan.rectP.left;
	var top = plan.rectP.top;
	var width = plan.rectP.width;
	var height = plan.rectP.height;
	var centerP = plan.centerP;
	
	var tileRect = plan.tileRect();
	var tileId, tile, $img;
	var inRect = {};
	
	for (var dy = 0; dy < tileRect.height; dy++) {
		var y = tileRect.top + dy;
		
		for (var dx = 0; dx < tileRect.width; dx++) {
			var x = tileRect.left + dx;

			tileId = plan.tileId([x, y]);
			inRect[tileId] = true;
			
			tile = currentTiles[tileId];
			if (!tile) {
				tile = currentTiles[tileId] = new Blueprint.Tile(plan, x, y);
			}
		
			if (callback) {
				callback.apply(tile);
			}
			else {
				tile.$img
				.css({
					opacity: 1,
					left: tile.left - left, top: tile.top - top,
					width: Blueprint.TILE_WIDTH, height: Blueprint.TILE_HEIGHT
				});
			}
		}			

	}

	for (var i in currentTiles) {
		if (!inRect[i]) {
			tile = currentTiles[i];
			tile.$img.remove();
			delete currentTiles[i];
		}
	}
	
};

Blueprint.Plan.prototype.renderDevices = function(transition) {

	var plan = this;
	
	//加载范围内所有的devices
	if (!plan.deviceUrl) return;

	// 静止500ms才能出发AJAX请求
	if (plan.deviceTimeout) { window.clearTimeout(plan.deviceTimeout); }


	plan.deviceTimeout = window.setTimeout(function(){

		function _contains(r1, r2) {
			if (r2.left >= r1.left
				&& r2.top >= r1.top
				&& r2.left + r2.width <= r1.left + r1.width
				&& r2.top + r2.height <= r1.top + r1.height) {
				return true;
			}
			return false;
		}

		var deviceUrl = plan.makeDeviceUrl(plan.rect);

		if (plan.floor == plan.lastFloor && plan.lastRect && _contains(plan.lastRect, plan.rect)) return;

		plan.lastRect = plan.rect;
		plan.lastFloor = plan.floor;
		
		$.getJSON(deviceUrl, function(data){
			var devices = data.devices;
			var i, id, device;
			
			var removable_devices = {};
			for (id in plan.devices) {
				removable_devices[id] = true;
			}
			
			for (i=0; i<devices.length; i++) {
				device = devices[i];
				plan.addDevice(device.id, device);
				if (removable_devices[device.id]) {
					delete removable_devices[device.id];
				}
			}
			
			for (id in removable_devices) {
				plan.removeDevice(id);
			}
			
		});
		
	}, 500);
	
	for (id in plan.devices) {
		plan.renderDevice(plan.devices[id], transition);
	}

};

//render函数
Blueprint.Plan.prototype.render = function() {
	
	var plan = this;
	
	plan.renderTiles();
	
	plan.renderDevices();
	
	return plan;
};

Blueprint.Plan.prototype.moveTo = function(locP) {
	var plan = this;
		
	var $container = plan.$container;

	var offset = $container.offset();
	var width = $container.width();
	var height = $container.height();
		
	var halfWidth = parseFloat(width / 2);
	var halfHeight = parseFloat(height / 2);
	
	var centerP = plan.M2P(plan.center);

	var dLocP = [locP[0] - offset.left, locP[1] - offset.top];
	
	var nCenterP = [centerP[0] - halfWidth + dLocP[0], centerP[1] -halfHeight + dLocP[1]];
	
	plan.setCenter(plan.P2M(nCenterP));
	plan.render();

	return this;
};

Blueprint.Plan.prototype.bindEvents = function(){
		
	var plan = this;
		
	var $container = plan.$container;
	
	var MAX_FINGER_DISTANCE = 50;
	
	var _setCenter = function(e) {
		var offset = $container.offset();
		var width = $container.width();
		var height = $container.height();
		
		var halfWidth = parseFloat(width / 2);
		var halfHeight = parseFloat(height / 2);
	
		var centerP = plan.M2P(plan.center);
	
		var dLocP = [e.pageX - offset.left, e.pageY - offset.top];
		
		var nCenterP = [centerP[0] - halfWidth + dLocP[0], centerP[1] -halfHeight + dLocP[1]];
		
		plan.setCenter(plan.P2M(nCenterP));
	};

	$container
	.bind("dblclick.blueprint", function(e) {
		_setCenter(e);
		plan.zoomTo(plan.zoomLevel+1);
		e.preventDefault();
		return false;
	})
	.bind("gesturestart.blueprint", function(e){
		var zoomLevel = plan.zoomLevel;
	
		var _scale_change = function(e){
			var eScale = e.originalEvent.scale;
			var dur = plan.animationDuration;
			plan.animationDuration = 0;
			plan.zoomTo(zoomLevel + Math.round(Math.log(eScale)));
			plan.animationDuration = dur;
		};

		var _scale_end = function(e) {
			$container.unbind("gesturechange.blueprint", _scale_change);
			$container.unbind("gestureend.blueprint", _scale_end);
		};
	
		$container.bind("gesturechange.blueprint", _scale_change);
		$container.bind("gestureend.blueprint", _scale_end);

		return false;
	})
	.bind("mousedown.blueprint touchstart.blueprint", function(e){
		e = Q.event(e);
			
		var isTouch = e.isTouch;
		var moved = false;
	
		if (isTouch && e.touches.length != 1) { return; }
	
		var sLocP = [e.pageX, e.pageY];
		var dLocP = [0, 0]; 
		var sCenterP = plan.M2P(plan.center);
		
		var _dragmove = function(e){
			moved = true;
			if (!isTouch) e.preventDefault();

			e = Q.event(e);
			
			var dLocP = [e.pageX - sLocP[0], e.pageY - sLocP[1]];
			sLocP = [e.pageX, e.pageY];
			
			var sCenterP = plan.M2P(plan.center);	
			var cLocP = [sCenterP[0] - dLocP[0], sCenterP[1] - dLocP[1]];
			plan.setCenter(plan.P2M(cLocP)).render();	

			return false;
		};
		
		var _dragend = function(e){
			
			if (isTouch) {
				$container.unbind("touchmove.blueprint", _dragmove);
			}
			else {
				$(document).unbind("mousemove.blueprint", _dragmove);
			}
			
			if (moved) {
				e.preventDefault();
				return false;
			}
			
		};
	
		if (isTouch) {
			$container
			.bind("touchmove.blueprint", _dragmove)
			.one("touchend.blueprint", _dragend);
		}
		else {
			$(document)
			.bind("mousemove.blueprint", _dragmove)
			.one("mouseup.blueprint", _dragend);
			return false;
		}

	});
	
	$(window).resize(function(){
		window.setTimeout(function(){
			plan.render();
		}, 50);
		
	});
	
	Q.on_broadcasting(plan.pickupDeviceEvent, function(message, params) {
		console.log(params)
		if(params.url){
			Q.trigger({
				object: 'pickup_device',
				event: 'click',
				url: params.url,
				data: {
					model: params.model, id: params.id,
					floor: plan.floor, rect: plan.rect
				},
				complete: function() {
					Q.trigger({
						object: 'pickup_device',
						event: 'click',
						url: params.submit_url,
						data: {
							model: params.model, id: params.id,
							floor: plan.floor, rect: plan.rect
						}
					})
				}
			});
		} else {
			Q.trigger({
				object: 'pickup_device',
				event: 'click',
				data: {
					model: params.model, id: params.id,
					floor: plan.floor, rect: plan.rect
				}
			});
		}
	});
	
	Q.on_broadcasting(plan.addDeviceEvent, function(message, params) {
		var id = params.id;
		plan.addDevice(id, params);
	});
	
};
		
Blueprint.Plan.prototype.removeDevice = function (id) {
	var plan = this;
	var device = plan.devices[id];
	if (device) {
		device.$div.remove();
		delete plan.devices[id];
	}
};

Blueprint.Plan.prototype.removeAllDevices = function() {
	for (var id in this.devices) {
		this.devices[id].$div.remove();
	}
	this.devices = [];
	this.lastFloor = null;
	this.lastRect = null;
};

Blueprint.Plan.prototype.setEditable = function(editable) {
	this.deviceIsDraggable = editable;
}

Blueprint.Plan.prototype.makeDeviceDraggable = function(device) {
	var plan = this;
	var $div = device.$div;
	var round = Math.ceil;
	
	$div
	.bind("mousedown.blueprint touchstart.blueprint", function(e){
		if (!plan.deviceIsDraggable) return;

		e = Q.event(e);
	
		var $document = $(document);
	
		var isTouch = e.isTouch;
		var moved = false;
	
		if (isTouch && e.touches.length != 1) { return; }
	
		var sLocP = [e.pageX, e.pageY];
		var oLocP = plan.M2P([device.x, device.y]);
		var left = plan.rectP.left;
		var top = plan.rectP.top;

		var o_x = device.x;
		var o_y = device.y;
		
		var _dragmove = function(e){
			moved = true;
			e = Q.event(e);
			
			var nLocP = [oLocP[0] + e.pageX - sLocP[0], oLocP[1] + e.pageY - sLocP[1]];
			var nLocM = plan.P2M(nLocP);
			
			if (nLocM[0] > plan.size[0] || nLocM[1] > plan.size[1] || nLocM[0] < 0 || nLocM[1] < 0) {
				moved = false;
				device.x = o_x;
				device.y = o_y;
				plan.renderDevice(device);
			}
			else {
				device.x = nLocM[0];
				device.y = nLocM[1];
				plan.renderDevice(device);
			}
			
			e.preventDefault();
			return false;
		};
		
		var _dragend = function(e){
			
			if (isTouch) {
				$div.unbind('touchmove.blueprint', _dragmove);
			}
			else {
				$document.unbind('mousemove.blueprint', _dragmove);
			}
			
			if (moved) {
				if(device.url){
					Q.trigger({
						object: 'device_position',
						event: 'update',
						url: device.url,
						data: {
							id: device.id,
							x: device.x,
							y: device.y
						},
						complete: function() {
							Q.trigger({
								object: 'device_position',
								event: 'update',
								url: device.submit_url,
								data: {
									id: device.id,
									x: device.x,
									y: device.y
								}
							});
						}
					});
				} else {
					Q.trigger({
						object: 'device_position',
						event: 'update',
						data: {
							id: device.id,
							x: device.x,
							y: device.y
						}
					});
				}

		
				e.preventDefault();
				return false;
			}
			
		};
	
		if (isTouch) {
			$div
			.bind('touchmove.blueprint', _dragmove)
			.one('touchend.blueprint', _dragend);
		}
		else {
			$document
			.bind('mousemove.blueprint', _dragmove)
			.one('mouseup.blueprint', _dragend);
		}
		
		e.preventDefault();
		return false;
	});

};

Blueprint.Plan.prototype.renderDevice = function (device, transition) {
	if (!device) return;
	var plan = this;
	var $container = plan.$container;

	var locP = plan.M2P([device.x, device.y]);
	var left = plan.rectP.left;
	var top = plan.rectP.top;
	
	var $div = device.$div;
	var just_created = false;
	var tooltip = device.tooltip;
	if (!$div) {
		$div = $('<div class="blueprint_device" onclick="return false;"><div class="blueprint_device_label"/><img class="blueprint_device_icon" /></div>');
		$div.appendTo(plan.$container);
		device.$div = $div;
		
		plan.makeDeviceDraggable(device);
		
		var $label = $div.find('.blueprint_device_label');
		$label.text(device.name);

		$div.attr('q-tooltip', tooltip);

		// $div.setClassAttr('tooltip', device.summary);
		// $div.setClassAttr('tooltip_class', "blueprint_device_tooltip");
		$div
		.bind('click.blueprint', function() {
			// var locP = [$div.offset().left + round($div.width() / 2), $div.offset().top + round($div.height() / 2)];
			// plan.moveTo(locP);
			$container.trigger('selectDevice', [device]);
			return false;
		})
		.bind('dblclick.blueprint', function() {
			$container.trigger('viewDevice', [device]);
			return false;
		})
		.bind('mouseenter.blueprint', function(){
			$container.trigger('hoverDevice', [device]);
		})
		.bind('mouseleave.blueprint', function(){
			$container.trigger('leaveDevice', [device]);
		});

		just_created = true;

	}

	var $icon = $div.find('img.blueprint_device_icon:first');

	if (device.icon_url && $div[0].src != device.icon_url) {
		$icon[0].src = device.icon_url;
	}
	else if (!$icon[0].src) {
		$icon[0].src = plan.defaultDeviceIcon;
	}

	var round = Math.ceil;
	
	function _relocate(no_animation) {
		if (no_animation) {
			$div.css({
				left: locP[0] - left - round($icon.width() / 2) - ($icon.offset().left - $div.offset().left), top: locP[1] - top - round($icon.height() / 2) - ($icon.offset().top - $div.offset().top)
			}, plan.animationDuration);
		}
		else {
			$div.stop(true);
			$div.animate({
				left: locP[0] - left - round($icon.width() / 2) - ($icon.offset().left - $div.offset().left), top: locP[1] - top - round($icon.height() / 2) - ($icon.offset().top - $div.offset().top)
			}, plan.animationDuration);
		}
	}

	_relocate(just_created ? true : !transition);
	
};

Blueprint.Plan.prototype.addDevice = function (id, devInfo) {
	var plan = this;
	if (devInfo) {
		
		var device = plan.devices[id];
		if (!device) {
			plan.devices[id] = device = new Blueprint.Device(plan);
		}

		$.extend(device, devInfo || {});

		plan.renderDevice(device);
	}
};

Blueprint.Device = function(plan) {
	this.plan = plan;
};

