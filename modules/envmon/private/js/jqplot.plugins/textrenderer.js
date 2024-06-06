/**
 * Copyright (c) 2009 - 2010 Chris Leonello
 * jqPlot is currently available for use in all personal or commercial projects 
 * under both the MIT and GPL version 2.0 licenses. This means that you can 
 * choose the license that best suits your project and use it accordingly. 
 *
 * The author would appreciate an email letting him know of any substantial
 * use of jqPlot.  You can reach the author at: chris at jqplot dot com 
 * or see http://www.jqplot.com/info.php .  This is, of course, 
 * not required.
 *
 * If you are feeling kind and generous, consider supporting the project by
 * making a donation at: http://www.jqplot.com/donate.php .
 *
 * Thanks for using jqPlot!
 * 
 */
(function($) {

    $.jqplot.TextRenderer = function(){
        $.jqplot.LineRenderer.call(this);
    };
    
    $.jqplot.TextRenderer.prototype = new $.jqplot.LineRenderer();
    $.jqplot.TextRenderer.prototype.constructor = $.jqplot.TextRenderer;
    
    $.jqplot.TextRenderer.prototype.init = function (options) {

		this._target = $('#' + options.target);
		this._target.data('jqplot.textTick', []);
		
        $.jqplot.LineRenderer.prototype.init.call(this, options);

		this.tickRenderer = $.jqplot.TextTickRenderer;
        // set the yaxis data bounds here to account for hi and low values
        var db = this._yaxis._dataBounds;
        var d = this._plotData;
        // if data points have less than 5 values, force a hlc chart.
		for (var j=0; j<d.length; j++) { 
			db.min = Math.min(db.min, d[j][0]);
			db.max = Math.max(db.max, d[j][0]);
		}
		
		this._isText = true;

		this._tooltipElem = $('<div class="jqplot-highlighter-tooltip" style="position:absolute;display:none"></div>');
		this._target.prepend(this._tooltipElem);
		
    };
    
    // called with scope of series
    $.jqplot.TextRenderer.prototype.draw = function (ctx, gd, options) {        

        var opts = {lineJoin:'round', lineCap:'round', fill:this.fill, isarc:false, strokeStyle:this.color, fillStyle:this.fillColor, lineWidth:this.lineWidth, closePath:this.fill};
        var db = this._yaxis._dataBounds;
        var yp = this._yaxis.series_u2p;

        ctx.save();
        if (gd.length) {
			ctx.lineWidth = 0.5;
			ctx.lineJoin = opts.lineJoin || this.lineJoin;
			ctx.lineCap = opts.lineCap || this.lineCap;
			ctx.strokeStyle = (opts.strokeStyle || opts.color) || this.strokeStyle;
			ctx.fillStyle = opts.fillStyle || this.fillStyle;
			ctx.font = "700 10px Arial";
			
 			var ymin, ymax;
			ymin = yp.call(this._yaxis, this._yaxis._min);
			ymax = yp.call(this._yaxis, this._yaxis._max);
			
			var i, j;
			var tick, fw;
			var h = Math.abs(ymax - ymin) + 1;
			var textHeight = 15;
			var maxIndex = Math.floor(h/textHeight);
			var minTick, minTickIndex;

			var textTick = this._target.data('jqplot.textTick') || [];
            for (i=0; i < gd.length; i++) {
	 			ctx.beginPath();
				ctx.moveTo(gd[i][0], ymin);
 				ctx.lineTo(gd[i][0], ymax);
	            ctx.stroke();

				/*
				// 查找tick
				fw = ctx.measureText(this._plotData[i][1]);
				minTick = textTick[0];
				minTickIndex = 0;
				
				index=-1;
				for (j=0; j < textTick.length; j++) {
					tick = textTick[j];
					if (tick < minTick) {
						minTickIndex = j;
						minTick = tick;
					}
					if (tick + fw.width < gd[i][0]) {
						tick = gd[i][0] + fw.width;
						index = j;
						break;
					}
				}
				
				if (index < 0) {
					if (j < maxIndex) {
						textTick.push(gd[i][0] + fw.width);
						index = textTick.length - 1;
					}
					else {
						textTick[minTickIndex] = gd[i][0] + fw.width;
						index = minTickIndex;
					}
				}
				
				ctx.fillStyle = '#666';
				ctx.fillText(this._plotData[i][1], gd[i][0] + 2, ymin - index * textHeight - 4);
				
				*/

           }
           this._target.data('jqplot.textTick', textTick); 
           
 		}
        
        ctx.restore();
        
    };
    
    $.jqplot.TextRenderer.prototype.drawShadow = function(ctx, gd, options) {
        // This is a no-op, shadows drawn with lines.
    };

    $.jqplot.TextAxisRenderer = function() {
        $.jqplot.LinearAxisRenderer.call(this);
    };
    
    $.jqplot.TextAxisRenderer.prototype = new $.jqplot.LinearAxisRenderer();
    $.jqplot.TextAxisRenderer.prototype.constructor = $.jqplot.TextAxisRenderer;
        
    $.jqplot.TextLegendRenderer = function(){
        $.jqplot.TableLegendRenderer.call(this);
    };
    
    $.jqplot.TextLegendRenderer.prototype = new $.jqplot.TableLegendRenderer();
    $.jqplot.TextLegendRenderer.prototype.constructor = $.jqplot.TextLegendRenderer;
    
    /**
     * Class: $.jqplot.TextLegendRenderer
     */
    $.jqplot.TextLegendRenderer.prototype.init = function(options) {
        // Group: Properties
        //
        // prop: numberRows
        // Maximum number of rows in the legend.  0 or null for unlimited.
        this.numberRows = null;
        // prop: numberColumns
        // Maximum number of columns in the legend.  0 or null for unlimited.
        this.numberColumns = null;
        $.extend(true, this, options);
    };
          
    $.jqplot.TextTickRenderer = function() {
        $.jqplot.AxisTickRenderer.call(this);
    };
    
    $.jqplot.TextTickRenderer.prototype = new $.jqplot.AxisTickRenderer();
    $.jqplot.TextTickRenderer.prototype.constructor = $.jqplot.TextTickRenderer;
    
    function preInit(target, data, options) {
        options = options || {};
        options.axesDefaults = options.axesDefaults || {};
        options.legend = options.legend || {};
        options.seriesDefaults = options.seriesDefaults || {};
        // only set these if there is a pie series
        var setopts = false;
        
        if (options.seriesDefaults.renderer == $.jqplot.TextRenderer) {
            setopts = true;
        }
        else if (options.series) {
            for (var i=0; i < options.series.length; i++) {
                if (options.series[i].renderer == $.jqplot.TextRenderer) {
                    setopts = true;
                    $.extend(options.series[i].rendererOptions, {target: target});
                }
            }
        }
        
        if (setopts) {
            options.axesDefaults.renderer = $.jqplot.TextAxisRenderer;
            options.legend.renderer = $.jqplot.TextLegendRenderer;
            options.legend.preDraw = true;
            options.seriesDefaults.pointLabels = {show: false};
       }
        
        
    }

    $.jqplot.preInitHooks.push(preInit);
    
    $.jqplot.TextTickRenderer = function() {
        $.jqplot.AxisTickRenderer.call(this);
    };
    
    $.jqplot.TextTickRenderer.prototype = new $.jqplot.AxisTickRenderer();
    $.jqplot.TextTickRenderer.prototype.constructor = $.jqplot.PieTickRenderer;
    
    $.jqplot.eventListenerHooks.push(['jqplotMouseMove', handleMove]);
    
    var fadeOutTimeout;
    function showTooltip(plot, s, d, gp) {

        var hl = plot.plugins.highlighter;
        var elem = s._tooltipElem;
		
		var x = gp.x + plot._gridPadding.left - elem.outerWidth(true) - hl.tooltipOffset;
               
    	var y = gp.y + plot._gridPadding.top  - elem.outerHeight(true);
                
                
		elem.appendTo(s._target);
		elem.css({
			left: x,
			top: y
		});
		
		var strs = [];
		for (var i=0; i<d.length; i++) {
			strs.push(d[i]);
		}
		strs.unshift(s.rendererOptions.textFormatString);
		
		var str = $.jqplot.sprintf.apply($.jqplot.sprintf, strs);
		
		elem.html(str);
		elem.stop(true, true).fadeIn();
		
    }
    
    function handleMove(ev, gridpos, datapos, neighbor, plot) {
        var c = plot.plugins.cursor;
 
 		if (neighbor != null) return;
 
 		for (var i=0; i<plot.series.length; i++) {
 			if (plot.series[i]._isText == undefined) continue;
 			var s = plot.series[i];
 			var threshold = s.neighborThreshold || 2;
  			for (var j=0; j<s.gridData.length; j++) {
 				if (Math.abs(s.gridData[j][0] - gridpos.x) <= threshold) {
            		showTooltip(plot, s, s._plotData[j], {x:s.gridData[j][0], y:gridpos.y});
            		return;
 				}
 			}
 			
 			var elem = s._tooltipElem;
 			if (elem.is(':visible')) elem.stop(true, true).fadeOut();

 		}
		
     }
    
})(jQuery);
    
    