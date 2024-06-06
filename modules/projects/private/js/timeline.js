var TimelineView;

(function($){

	TimelineView = function(container, listTemplate, rangeTemplate){
		container = container ? container : $('.timeline_view');
		listTemplate = listTemplate ? listTemplate : $('<div class="node timeline_node"><span class="toggle_button middle" /></div>');
		rangeTemplate = rangeTemplate ? rangeTemplate : $('<div class="block timeline_node"><div class="handler handler_left"/><div class="content"/><div class="handler_right handler"/></div>');
		this.container = container;
		this.rangeBlockView = container.find('.timeline_node_view');
		this.listBlockView = container.find('.timeline_node_list');
		this.nodes = [];
		this.header = new TimelineHeader(this);
		this.listTemplate = listTemplate;
		this.rangeTemplate = rangeTemplate;	
		
		$('<span class="move_left hidden"></span>').appendTo('body');
		$('<span class="move_right hidden"></span>').appendTo('body');
	};
	
	TimelineView.prototype.init = function(data){
		this.header.dtStart = data.dtStart;
		this.header.dtEnd = data.dtEnd;
		if (data.period) {
			this.header.period = data.period;
		}
		this.header.pitch = parseInt((this.header.dtEnd - this.header.dtStart) / this.header.container.width());	
		this.header.excurtion = parseInt(this.rangeBlockView.find('.timeline_header:first').height(), 10);
		this.listBlockView.css('top', this.header.excurtion);
	};
	
	TimelineView.prototype.addNodes = function(data){
		var prev = null;
		for(var i in data){
			if (typeof data[i] !== 'undefined') {
				var node = new TimelineNode(this, data[i], null);
				if (prev) {
					prev.next = node;
				}
				prev = node;
				this.nodes.push(node);
				node.render();
			}
		}
		return this;
	};

	TimelineHeader = function(view) {
		this.dtStart = 0;
		this.dtEnd = 0;
		this.period = 'week';
		this.container = view.container.find('.timeline_header');
	};
	
	TimelineHeader.prototype.date2X = function (dt) {
		var x = (dt - this.dtStart) / this.pitch;
		return x;
	};
	
	TimelineHeader.prototype.x2Date = function (x) {
		var dt = parseInt((x - this.container.offset().left) * this.pitch) + parseInt(this.dtStart);
		return parseInt(dt);
	};
	
	TimelineNode = function(view, data, parent){
		
		this.id = data.id;

		this.view = view;
		this.listBlock = view.listTemplate.clone();
		this.rangeBlock = view.rangeTemplate.clone();
		this.nodes = [];
		
		this.realStart = data.dtStart;
		this.realEnd = data.dtEnd;
		
		if (this.view.header.dtStart > this.realStart) {
			this.dtStart = this.view.header.dtStart;
		}
		else {
			this.dtStart = this.realStart;
		}
		
		if (this.view.header.dtEnd < this.realEnd) {
			this.dtEnd = this.view.header.dtEnd;
		}
		else {
			this.dtEnd = this.realEnd;
		}
		
		this.url = data.url;

		var week = new Date(this.dtStart*1000);
		var node;
		this.listBlock.append(data.title);
		this.rangeBlock.find('.content').append(data.title);
		
		this.level = 0;
		if(parent){
			node = parent;
			while (node.nodes.length > 0) {
				node = node.nodes[node.nodes.length-1];
			}
			node.listBlock.after(this.listBlock);
			this.parent = parent;
			this.level = parseInt(parent.level, 10) + 1;
		}
		else{
			view.listBlockView.append(this.listBlock);
		}

		this.listBlock.css('margin-left', this.level*20);
		
		view.rangeBlockView.append(this.rangeBlock);
		
		this.render();
		// view.rangeBlockView.height(view.listBlockView.outerHeight() + view.header.container.outerHeight() + 20);
		this.bindEvent();
		
		this.isExpanding = false;
		this.isCollapsed = true;
		
		node = this;
		//如果当前的 node 是非空任务集,那么给 toggle_button 加上按钮样式
		Q.trigger({
			object: 'task',
			event: 'children',
			data: {id:node.id},
			success: function(data) {
				if (data.return_value) {
					$(node.listBlock).find('.toggle_button').addClass('toggle_collapse');
					
					//给非空任务集绑定click事件
					node.listBlock.bind('click', function() {
						//找到当前的listBlock的 toggle_button
						this.toggle_button = $(this).find('.toggle_button');
						if (node.isExpanding) {
							return false;
						}
			
						if (node.isCollapsed) {
							node.isExpanding = true;
							//展开后加上 toggle_expand 样式, 去掉 toggle_collapse 样式
							this.toggle_button.removeClass('toggle_collapse');
							this.toggle_button.addClass('toggle_expand');
								
							Q.trigger({
								object: 'task',
								event: 'expand',
								data: {id: node.id},
								success: function(data) {
									data = data.children || [];	
									node.addChildren(data);
									//node后面的需要调整坐标
									node.render();
									//view.rangeBlockView.height(view.listBlockView.outerHeight() + view.header.container.outerHeight() + 20);
									node.isCollapsed = false;
									node.isExpanding = false;
									delete data.children; // 避免qcore处理该项
								}
							});
						}
						else {
							//展开后加上 toggle_expand 样式, 去掉 toggle_collapse 样式
							this.toggle_button.removeClass('toggle_expand');
							this.toggle_button.addClass('toggle_collapse');
							node.removeChildren();
							node.isCollapsed = true;
							node.render();
						}
					});
				}
			}
		});
		
		

	};
	
	TimelineNode.prototype.getY = function(){
		return this.listBlock.offset().top - this.view.listBlockView.offset().top + this.view.header.excurtion;
	};
	
	TimelineNode.prototype.addChildren = function (data)　{
		var prev = null;
		for(var i in data){
			if (typeof data[i] !=='undefined') {
				var child = new TimelineNode(this.view, data[i], this);
				if (prev) {
					prev.next = child;
				}
				prev = child;
				this.nodes.push(child);
			}
		}
	};

	TimelineNode.prototype.render = function(){
		var view = this.view;

		this.renderRangeBlock();
		for (var i in this.nodes) {
			if (typeof this.nodes[i] !=='undefined') {
				this.nodes[i].render();
			}
		}
		
		if (this.next) {
			this.next.render();
		}
		else {
			// render parent's next sibling;
			var parent = this.parent;
			while (parent) {
				if (parent.next) {
					parent.next.render();
					break;
				}
				parent = parent.parent;
			}
		}	
	};
	
	TimelineNode.prototype.renderRangeBlock = function(){
		if (this.realStart != this.dtStart) {
			this.rangeBlock.find('.handler_left').hide();
		}
		else {
			this.rangeBlock.find('.handler_left').show();
		}
		if (this.realEnd != this.dtEnd) {
			this.rangeBlock.find('.handler_right').hide();
		}
		else {
			this.rangeBlock.find('.handler_right').show();
		}
		
		this.rangeBlock.css({
			'left' : this.view.header.date2X(this.dtStart),
			'top' : this.getY(),
			'width' : this.view.header.date2X(this.dtEnd) - this.view.header.date2X(this.dtStart)
		});
				
	};
	
	TimelineNode.prototype.moved = function(e, dtStart, dtEnd, realStart, realEnd){
		//dtStart, dtEnd: node移动之前的起止时间
		if(e.button==2){
			// 如果是右键
			this.dtStart = dtStart;
			this.dtEnd = dtEnd;
			this.realStart = realStart;
			this.realEnd = realEnd;
			this.renderRangeBlock();
		}else{
			//发送AJAX请求
			var node = this;
			//在这里判断是往左移动还是往右移动,并且加上相应的样式
			Q.trigger({
				object: 'task',
				event: 'update',
				data: {id: this.id, dtstart: this.realStart, dtend: this.realEnd},
				success: function(data) {
					var moved = data.moved.data.moved;
					if(!moved){
						alert(data.moved.data.alert);
						node.dtStart = dtStart;
						node.dtEnd = dtEnd;
						node.realStart = realStart;
						node.realEnd = realEnd;
						node.renderRangeBlock();
					}
				}
			});
		}
	};
	
	TimelineNode.prototype.bindEvent = function(){
		//绑定事件
		var node = this;
		var dtStart = this.dtStart;
		var dtEnd = this.dtEnd;

		node.moving = false;
		
		function show_contextmenu(e) {

			if (node.moving) {
				return false;
			}

			var x = e.pageX;
			var y = e.pageY;
			var menu = node.view.container.find('.timeline_contextmenu');
			
			menu
			.css({
				left : x - node.view.container.offset().left,
				top : y - node.view.container.offset().top
			})
			.html('<span class="loading"/>').show();
			
			//o, e, data, func, url
			Q.trigger({
				object: 'task',
				event: 'contextmenu',
				data: {id: node.id, type: e.type},
				success: function(data) {
					menu.html(data.contextmenu.data);
					$('body').one('click', function(){
						menu.hide();
					});
					
					delete data.contextmenu; // 不触发后续qcore默认事件					
				}
			});
			
			e.stopPropagation();			
			return false;
			
		}
		
		function view_task(e) {
			window.location.href = node.url;
		}
		
		
		this.listBlock
			.bind('contextmenu.timeline', show_contextmenu)
			.set_unselectable();
			
		this.rangeBlock
			.bind('contextmenu.timeline', show_contextmenu)
			.bind('dblclick.timeline', view_task)
			.set_unselectable();

		$('.content', this.rangeBlock)
		.bind('click', show_contextmenu)
		.bind('mousedown', function(e){
			var dtMouse = node.view.header.x2Date(e.pageX);
			var tmp_dtStart = node.dtStart;
			var tmp_dtEnd = node.dtEnd;
			var tmp_realStart = node.realStart;
			var tmp_realEnd = node.realEnd;

			if(node.moving && e.button==2) {
				//在移动中的node上右键单击，node还原
				if(node.moving) {
					node.moved(e, tmp_dtStart, tmp_dtEnd, tmp_realStart, tmp_realEnd);
					return false;
				}
				
				return;
			}
			
			$(document)
			.bind('mousemove.timeline_content', function(e){
				node.moving = true;
												
				var x = node.view.header.x2Date(e.pageX) - dtMouse;
				node.realStart = tmp_realStart + x;
				node.realEnd = tmp_realEnd + x;
				if (node.realStart >= node.view.header.dtStart) {
					node.dtStart = node.realStart;
				}
				else {
					node.dtStart = node.view.header.dtStart;
				}
				if (node.realEnd <= node.view.header.dtEnd) {
					node.dtEnd = node.realEnd;
				}
				else {
					node.dtEnd = node.view.header.dtEnd;
				}
				node.renderRangeBlock();
				
				//移动的时候出现向方向图标
				var moveLeft = e.pageX - 15;
				var moveTop = parseInt(node.rangeBlock.offset().top);
				if ($(node).data('mouseX') > e.pageX) {
					$('span.move_left').show().css({
						left : moveLeft,
						top : moveTop
					});
					$('span.move_right').hide();
				}
				if ($(node).data('mouseX') < e.pageX) {
					$('span.move_right').show().css({
						left : moveLeft,
						top : moveTop
					});
					$('span.move_left').hide();
				}
				//缓存鼠标移动的值
				$(node).data('mouseX', e.pageX);		
				
			})
			.one('mouseup', function(e){
				//span.move_left && span.move_right 消失
				$('span.move_left').hide();
				$('span.move_right').hide();
								
				$(this).unbind('mousemove.timeline_content');
				if (node.moving) {
					// 有了鼠标移动，就尝试修改服务器的相关值
					node.moved(e, tmp_dtStart, tmp_dtEnd, tmp_realStart, tmp_realEnd);
					node.moving = false;
					node.renderRangeBlock();
				}
			});
		});
		
		$('.handler', this.rangeBlock)
		.bind('mousedown', function(e){
			var movingStart = $(this).hasClass('handler_right') ? false : true;
			var tmp_dtStart = node.dtStart;
			var tmp_dtEnd = node.dtEnd;
			var tmp_realStart = node.realStart;
			var tmp_realEnd = node.realEnd;
			
			if(e.button==2) {
				//在移动中的node上右键单击，node还原
				if(node.moving) {
					node.moved(e, tmp_dtStart, tmp_dtEnd, tmp_realStart, tmp_realEnd);
					return false;
				}
				return;
			}

			$(document)
			.bind('mousemove.timeline_handler', function(e){
				node.moving = true;
				var dt = node.view.header.x2Date(e.pageX);
				if (movingStart) {
					node.realStart = node.dtStart = dt;
				}
				else {
					node.realEnd = node.dtEnd = dt;
				}
				
				if (node.realEnd < node.realStart) {
					dt = node.realStart;
					node.realStart = node.realEnd;
					node.realEnd = dt;
				}
				
				if (node.dtEnd < node.dtStart) {
					dt = node.dtStart;
					node.dtStart = node.dtEnd;
					node.dtEnd = dt;
					movingStart = !movingStart;
				}
				node.renderRangeBlock();
			})
			.one('mouseup', function(e){
				$(this).unbind('mousemove.timeline_handler');
				if(node.moving) {
					node.moved(e, tmp_dtStart, tmp_dtEnd, tmp_realStart, tmp_realEnd);
					node.moving = false;
					node.renderRangeBlock();
				}
			});
		});
	};
	
	TimelineNode.prototype.removeChildren = function(){
		for(var i in this.nodes){
			if (typeof this.nodes[i] !== 'undefined') {
				this.nodes[i].remove();
			}
		}
		this.nodes = [];
	};
	
	TimelineNode.prototype.remove = function(){
		this.removeChildren();
		this.listBlock.remove();
		this.rangeBlock.remove();
	};
	
})(jQuery);










