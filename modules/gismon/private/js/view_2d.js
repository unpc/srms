jQuery(function($) {

	var $fullscr = $('#' + fullscr_id);	
	var $container = $('#' + container_id);
	var $dev_info = $('#' + dev_info_id);
	var $button_upstairs = $('#' + button_upstairs_id);
	var $button_downstairs = $('#' + button_downstairs_id);
	var $button_zoomin = $('#' + button_zoomin_id);
	var $button_zoomout = $('#' + button_zoomout_id);
	var plan = new Blueprint.Plan($container, {
		name: building.name,
		tileUrl: tile_url,
		deviceUrl: device_url,
		floors: building.floors,
		zoomLevel: 0,
		maxZoomLevel: 2,
		size: [building.width, building.height],	//meters
		center: [0, 0], //meters
		defaultDeviceIcon: default_device_icon,
		deviceIsDraggable: device_is_draggable,
		pickupDeviceEvent: 'gismon.pickup_device',
		addDeviceEvent: 'gismon.add_device'
	});

	$button_upstairs.click(function(){
		plan.setFloor(plan.floor + 1);
		return false;
	});	

	$button_downstairs.click(function(){
		plan.setFloor(plan.floor - 1);
		return false;
	});	

	$button_zoomin.click(function(){
		plan.zoomTo(plan.zoomLevel + 1);
		return false;
	});	

	$button_zoomout.click(function(){
		plan.zoomTo(plan.zoomLevel - 1);
		return false;
	});

	var $dp = $dev_info.parent();
	var $cp = $container.parent();
	/* BUG #251 点击蓝图任意空白处 jia.huang@2010.12.20 */	
	$container
	.bind('click', function(){
		if (!$dp.is(':visible')) return false;

		$dp.stop().animate({width:0}, 200, function(){
			$dp.hide();
		});

		$cp.stop().animate({'margin-right':0}, 200, function(){
			$dp.hide();
			plan.standardize(false).render();
		});

		return false;
	})
	.bind('viewDevice', function(e, device) {
		if (device.view_url) window.location.href = device.view_url;
		return false;
	})
	.bind('selectDevice', function(e, device) {

		$dp.stop().show().animate({width:250}, 200, function(){
			if ($cp.height() > $dp.height()) $dp.height($cp.height());
		});
		$cp.stop().animate({'margin-right':250}, 200, function(){
			plan.standardize(false).render();
		});

		var equ_id = $dev_info.find('table').attr('device-id');
		if(typeof(equ_id) != 'undefined') {
			plan.devices[equ_id].summary = $dev_info.html();
		}
		$dev_info.empty().append(device.summary);
		return false;
	});

	var resizeTimeout = null;
	function _resize(){
		if (resizeTimeout) clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(function(){
			if ($fullscr.hasClass('fullscreen')) {
				$container.height($fullscr.innerHeight());
			}
			else {
				$container.height(100);
				var $td = $('#center').parent();
				var h = $td.innerHeight() - $container.offset().top + $td.offset().top;
				$container.height(h - 12);
			}
			plan.standardize().render();
		}, 200);
	}


	$(window)
	.resize(_resize);

	_resize();

	if (allow_edit) {
		var $button_add = $('#' + button_add_id);
		var $button_lock_devices = $('#' + button_lock_devices_id);
		var $button_unlock_devices = $('#' + button_unlock_devices_id);

		$button_unlock_devices.click(function() {
			$(this).hide();
			$button_add.addClass('button button_add').show();
			$button_lock_devices.addClass('button buttond_lock').show();
			plan.setEditable(true);
			return false;
		});

		$button_lock_devices.click(function() {
			$button_add.hide();
			$(this).hide();
			$button_unlock_devices.addClass('button button_unlock').show();
			plan.setEditable(false);
			return false;
		});

		if (plan.deviceIsDraggable) {
			$button_unlock_devices.click();
		}
		else {
			$button_lock_devices.click();
		}
	}


});
