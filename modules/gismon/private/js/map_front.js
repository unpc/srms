jQuery(function($){

	// 该 js 提供前台网站中嵌入地图所用的效果(xiaopei.li@2013-06-01)
	// 朕改过了(jipeng.huang@2016-08-15)
    var $map = $('#' + map_id);
    var m = new BMap.Map(map_id);

    var top_left_control = new BMap.ScaleControl({anchor: BMAP_ANCHOR_TOP_LEFT});
    var top_left_navigation = new BMap.NavigationControl();
    var top_right_navigation = new BMap.NavigationControl({anchor: BMAP_ANCHOR_TOP_RIGHT, type: BMAP_NAVIGATION_CONTROL_SMALL});

    m.addControl(top_left_control);        
    m.addControl(top_left_navigation);     
    m.addControl(top_right_navigation);

    var fetch_url = fetch_url || null;
    var markers = {};
    var isMarkersDraggable = false; 
    var redrawTimeout = null;

	function viewChanged() {
        var bounds;
        if (redrawTimeout) clearTimeout(redrawTimeout);

        redrawTimeout = setTimeout(function() {
            redrawTimeout = null;

            try {
                bounds = m.getBounds();
            }
            catch (err) {
                return;
            }

            var ne = bounds.getNorthEast();
            var sw = bounds.getSouthWest();

			Q.trigger({
				object: 'building',
				event: 'fetch',
				url: fetch_url,
				global: false,
				data: {swlat: sw.lat, swlon: sw.lng, nelat: ne.lat, nelon: ne.lng},
				success: function(data) {
					if (!data) return;
					var newMarkers = {};
					$.each(data.buildings || [], function(i, building) {

                        var marker = markers[building.id];
                        if (marker) { // 保留已在此视图中的markers
                            newMarkers[building.id] = marker;
                            delete markers[building.id];
                            return;
                        }

                        var latlon = new BMap.Point(building.longitude, building.latitude);
						
                        marker = new BMap.Marker(latlon);
                        marker.building = building;

                        marker.addEventListener('infowindowopen', function() {
                            for (var i in m.markers) {
                                m.markers[i].closeInfoWindow();
                            }
                        });

                        marker.addEventListener('click', function() {
                            Q.trigger({
								object: 'building',
								event: 'show',
								url: fetch_url,
								data: {
									'building_id': marker.building.id,
									'building_name': marker.building.name,
									'equipment_uniqid':equipment_uniqid
								}
							});
                        });

						var opts = {
                            position : latlon,
                            offset   : new BMap.Size(15, 25)
                        }
                        var label = new BMap.Label(building.name, opts);
                        marker.setLabel(label);

                        m.addOverlay(marker); 

						newMarkers[building.id] = marker;
					});

					$.each(markers, function(i, marker) { m.removeMarker(marker); });

					markers = newMarkers;
					delete data.buildings;
				}
			});

		}, 500);
	}

    var latlon = new BMap.Point(longitude, latitude);
    
    m.addEventListener('zoomend', function(){
        viewChanged();
    });
    m.addEventListener('tilesloaded', function(){
        viewChanged();
    });
    m.addEventListener('load', function(){
        viewChanged();
    });

    m.centerAndZoom(latlon, 16);

    // (xiaopei.li@2010.12.15)
    var refresh = function () {
        m.clearOverlays();
        markers = {};
        viewChanged();
    };

    if (Q.supportTouch) {
        $map.bind('touchmove', function(){ return false; });
    }

    var resizeTimeout = null;
    function _resize(){
		if (resizeTimeout) clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(function(){
			$map.css('width', '100%');
			refresh();
		}, 200);
    }

    $(window).resize(_resize);
    _resize();
});
