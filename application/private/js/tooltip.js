(function($){
	Q.Tooltip = function(opt) {
		if (typeof(opt) !== 'object') {
			opt = {content: opt};
		}
		this.opt = opt;
	};
	
	/*
	*BUG #4074
	*当鼠标从上往下移时, 触发tooltip的_mouseenver事件, $tip会show, 同时鼠标有可能触发$tip的mouseover事件, 导致$tip还没有
	*真正的显示就被删除了。修复bug分为两个步骤
	*1、取消$tip的mouseover事件, 因为tooltip的_mouseleave事件在鼠标离开tooltip元素后就会触发, 删除$tip, 不需要通过$tip的mouseover事件来删除
	*2、解决当鼠标同时跨tooltip和$tip时, $tip会挡住鼠标, 触发tooltip的_mouseleave, 但此时鼠标并没有真正的leave, 又会触发tooltip.mouseenter
	*导致$tip不停的显示和隐藏
	*/
	var _checkTimeout;
	
	var el; //元素与left的距离
	var et; //元素与top的距离
	var ml; //鼠标与left的距离
	var mt; //鼠标与top的距离
	var tw;	//触发tip的元素宽度
	var th; //触发tip的元素高度
	
	$(document).mousemove(function(e) {
		ml = e.pageX;
		mt = e.pageY;
	});
	
	//定义Q.Tooltip的show方法.
	Q.Tooltip.prototype.show = function(x, y, $prev) {
		if (this.$tip) {
			return;
		}	
		var $tip = $('<div class="tooltip_wrapper"><div class="tooltip_content" /></div>');
		
		if (this.opt.extraClass) {
			$tip.addClass(this.opt.extraClass);
		}
		$tip.children('.tooltip_content').html(this.opt.content);
		if (typeof($prev)!=='undefined') {
			$prev.after($tip);
		}
		else {
			$tip.appendTo('body');
		}
		
		var $el = this.opt.el;//获取触发事件的元素
		//如果未设定x y, 需要根据触发事件的元素确定位置
        if (typeof(x) == 'undefined' || typeof(y) == 'undefined') {
            var offset = $el.offset();

            var deltaX;

            switch (this.opt.position) {
	            case 'left':
	                deltaX = Math.min(15, Math.round($el.outerWidth() / 4));
	                break;
	            case 'right':
	                deltaX = Math.max($el.outerWidth() - 15, Math.round($el.outerWidth() * 3 / 4));
	                break;
	            default:
	                deltaX = Math.round($el.outerWidth() / 2) - 1;
            }

            if (typeof(x) == 'undefined') {
                x = offset.left + deltaX;
            }

            if (typeof(y) == 'undefined') {
                y = offset.top + this.opt.offsetY - 3;
            }
        }
        
        var w = $tip.outerWidth(true);
		var h = $tip.outerHeight(true);

		this.x = x - Math.round(w/2) + 1;
		this.x = (this.x > 0) ? this.x : 0;
		this.y = y - h;
		
		$tip.css({display: 'none'});
		
		$tip
		.css({
			left: this.x,
			top: this.y + 5,
			opacity: 0,
			display: 'block'
		})
		.animate({
			top: this.y,
			opacity: 1
		}, 50);

		_checkTimeout =  setTimeout(function(){
			//clearQueue: Remove from the queue all items that have not yet been run.
			var nw = $tip.outerWidth(true);
			var nh = $tip.outerHeight(true);

			if (nw != w || nh != h) {
				w = nw;
				h = nh;

				this.x = x - Math.round(w/2) + 1;
				this.y = y - h;
		
				$tip
				.css({
					left: this.x
				})
				.clearQueue()
				.animate({
					top: this.y
				}, 50);
	
			}

		}, 50);
		
		this.$tip = $tip;
	};
	
	//定义Q.Tooltip的remove方法
	Q.Tooltip.prototype.remove = function() {
	 	if (_checkTimeout) {
			clearTimeout(_checkTimeout);
			_checkTimeout = null;
		}	
		if (this.$tip) {
			this.$tip.remove();//在页面删除该节点
			this.$tip = null;//清空对象的$tip属性		
		}
	}

	function _mouseenter() {
		var el = this;
		var $el = $(this);
		
		var tooltip = $el.data('tooltip');
			
		if (!tooltip) {
			//由于心跳机制导致之前产生的tip可能没有remove, 因此这个地方先要remove之前的所有tip
			$(document).find('div.tooltip_wrapper').remove();
			/*
           NO. BUG#151 (Cheng.Liu@2010.11.10)
           将offsetY的值在为Nan时修改为默认为0
           避免无法定位top坐标
	       */
			tooltip = new Q.Tooltip({
				content: $el.classAttr('tooltip'),
				extraClass: $el.classAttr('tooltip_class'),
				position: $el.classAttr('tooltip_position'),
				offsetY: parseInt($el.classAttr('tooltip_offsetY')||0, 10),
                el: $el
			});
			$el.data('tooltip', tooltip);
		}
			
		if (tooltip.removeTimeout) {
			window.clearTimeout(tooltip.removeTimeout);
			tooltip.removeTimeout = null;
		}
		
		if (tooltip.showTimeout) {
			window.clearTimeout(tooltip.showTimeout);
			tooltip.showTimeout = null;
			return;
		}
	
		tooltip.showTimeout = window.setTimeout(function(){

			if ($el.data('tooltip_suppress')) {
				return;
			}
				
            tooltip.show();
            
		}, 50);

		
	}
	
	function _mouseleave(e) {
		var $el = $(this);
		var _checkMouseLeave = setInterval(function() {
			el = $el.offset().left;
			et = $el.offset().top;
			tw = $el.width();
			th = $el.height();

			//鼠标在元素内容, 不能进行移除操作
			if ( ml > el && (ml - el) < tw && mt > et && (mt - et ) < th ) return;
					
			var tooltip = $el.data('tooltip');
			if (!tooltip) {
				return; 
			}
			
			if (tooltip.showTimeout) {
				window.clearTimeout(tooltip.showTimeout);
				tooltip.showTimeout = null;
			}
			
			tooltip.removeTimeout = window.setTimeout(function() {
				tooltip.remove();
			}, 50);
			
			clearInterval(_checkMouseLeave);
			//删除后, 将相关变量清空
			el = null;
			et = null;
			ml = null;
			mt = null;
			th = null;
			tw = null;
		}, 100);
		
	}
	
	
	$('[class*="tooltip\:"], [q-tooltip]')
	.on('mouseenter', _mouseenter)
	.on('mouseleave', _mouseleave);
})(jQuery);
