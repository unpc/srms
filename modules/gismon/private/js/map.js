jQuery(function($){

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
                        /*
                        marker.click.addHandler(function(e, map, opt) {
                            window.location.href = building_url.replace('%id', building.id);
                        });
                        */
                        marker.addEventListener('infowindowopen', function() {
                            for (var i in m.markers) {
                                m.markers[i].closeInfoWindow();
                            }
                        });

                        marker.addEventListener('dragstart', function(e) {
                            m.closeInfoWindow();
                        });

                        marker.addEventListener('dragend', function(){
                            //marker.update();
                            if (building.url) {
                                Q.trigger({
                                    object: 'building',
                                    event: 'move',
                                    url: building.url,
                                    data: {},
                                    complete: function() {
                                        Q.trigger({
                                            object: 'building',
                                            event: 'move',
                                            url: building.submit_url,
                                            data: {
                                                building_id: building.id,
                                                new_longitude: marker.getPosition().lng,
                                                new_latitude: marker.getPosition().lat
                                            }
                                        });
                                    }
                                });
                            } else {
                                Q.trigger({
                                    object: 'building',
                                    event: 'move',
                                    data: {
                                        building_id: building.id,
                                        new_longitude: marker.getPosition().lng,
                                        new_latitude: marker.getPosition().lat
                                    }
                                });
                            }
                        });

                        var opts = {
                            offset   : new BMap.Size(5, -25)
                        }
                        var infoWindow = new BMap.InfoWindow(building.bubble, opts);
                        marker.addEventListener('mouseover', function(e){
                            m.openInfoWindow(infoWindow, marker.getPosition());
                        });

                        var opts = {
                            position : latlon,
                            offset   : new BMap.Size(15, 25)
                        }
                        var label = new BMap.Label(building.name, opts);
                        marker.setLabel(label);

                        if (isMarkersDraggable) {
                            marker.enableDragging();   
                        };

                        m.addOverlay(marker); 

                        newMarkers[building.id] = marker;
                
                    });
                    
                    $.each(markers, function(i, marker) { m.removeOverlay(marker); });

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

    $("a#enter_edit_mode").bind('click', function() {
        isMarkersDraggable = true;
        refresh();

        $(this).hide();
        $("a#exit_edit_mode").show();
    });

    $("a#exit_edit_mode").bind('click', function() {
        isMarkersDraggable = false;
        refresh();

        $(this).hide();
        $("a#enter_edit_mode").show();
    });

    $("a#goto_center").bind('click', function() {
        m.centerAndZoom(latlon, 16);
    });

    $("a#map_mode_road").click(function(){
        m.setMapType(BMAP_NORMAL_MAP);
    });

    $("a#map_mode_hybrid").click(function(){
        m.setMapType(BMAP_HYBRID_MAP);
    });

    if (Q.supportTouch) {
        $map.bind('touchmove', function(){ return false; });
    }

    var resizeTimeout = null;
    function _resize(){
        if (resizeTimeout) clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function(){
            $map.css('width', '100%');
            var $fullscreen = $map.parents('.fullscreen');
            if ($fullscreen.length) {
                $map.height($fullscreen.innerHeight());
            }
            else {
                $map.height(100);
                var $td = $('#center').parent();
                var h = $td.innerHeight() - $map.offset().top + $td.offset().top;
                $map.height(h - 12);
            }
            
            refresh();
        }, 200);
    }

    $(window).resize(_resize);
    _resize();
});
