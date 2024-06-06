(function($) {

	Q.tag_sortable = function (root_id, root_container_id, tag_url, tag_name = 'tag') {
		
		$document = $(document);
		$body = $('body');

		var $root_container = $('#' + root_container_id);

		var $sort_placeholder = $('<div class="tag_sort_placeholder hidden"><div>&#160;</div></div>');
		var $title_placeholder = $("<div class='tag_title_placeholder hidden'><div>&#160;</div></div>");

		var delta = 6;

		$root_container
		.find('.tag_drag_handle')
		.livequery('mousedown touchstart', function(e) {
			e = Q.event(e);
			var isTouch = e.isTouch;

			var $handle = $(this);
			var $helper = $handle.parents('.tag_title:first').find('.tag_drag_helper').clone();

			$helper
			.css({
				position: 'absolute',
				left: e.pageX + 5, top: e.pageY + 5
			})
			.appendTo('body');

			var oldCursor = $body.css('cursor');
			$body.css({cursor:'move'});

			var $items = $root_container.find('.tag_item');
			var items_count = $items.length;

			var hasTarget;
			var sortable = false;

			var index;
			var prev_index;
			var current_index = $items.index($handle.parents('.tag_item:first'));

			$sort_placeholder.appendTo('body');
			$title_placeholder.appendTo('body');

			var _dragmove = function(e) {
				e = Q.event(e);

				$helper.css({
					left: e.pageX + 5, top: e.pageY + 5
				});

				function _drag_over(index) {
					//根据获取到的item索引定义各个变量的值
					var $item = $items.eq(index);
					var offset = $item.offset();
					var top = offset.top;
					var bottom = offset.top + $item.height();

					//如果鼠标停留到其他区域，就不做任何处理，直接return
					if (e.pageX < $items.eq(index).offset().left || e.pageX > (offset.left + $item.outerWidth()) || e.pageY < (top- delta) || e.pageY > (bottom + delta)) {
						$sort_placeholder.hide();
						$title_placeholder.hide();

						hasTarget = false;
						return;
					}

					hasTarget = true;

					//如果在块的上方部位，显示出$show层，pre_index赋值，并在之后进行sort排序操作
					if (e.pageY <= (top + delta)) {
						$title_placeholder.hide();
						$sort_placeholder.css({
							top : Math.floor(top - $sort_placeholder.height() / 2),
							left : offset.left,
							width : $item.outerWidth()
						}).show();

						sortable = true;
						prev_index = index - 1;
					}
					//如果在块的中部 ，显示出$over层，并在之后做drop操作
					else if (e.pageY < (bottom-delta)){

						$sort_placeholder.hide();
						$title_placeholder.css({
							top : top,
							left : offset.left,
							width : $item.find('.tag_title:first').outerWidth(),
							height : $item.find('.tag_title:first').outerHeight()
						}).show();
						sortable = false;
					}
					//如果在块的下方部位，显示出$show层，pre_index复制，并在之后进行sort排序
					else {
						$title_placeholder.hide();
						$sort_placeholder.css({
							top :  Math.floor(bottom - $sort_placeholder.height() / 2),
							left : offset.left,
							width : $item.outerWidth()
						}).show();

						sortable = true;

						prev_index = index;
						//如果在上方item和下方item存在父子关系，则显示出下方$show层的样式，并且替换掉index值为下方item的index值，并重新运行_drag_over函数
						if (index < (items_count - 1)) {
							var next_index = index + 1;
							var $parent_of_next = $items.eq(next_index).parents('.tag_item:first');
							var parent_index = $items.index($parent_of_next);
							if (index == parent_index) {
								return _drag_over(next_index);
							}
						}

					}

				}

				index = get_hover_index(0, items_count - 1, e.pageY);
				_drag_over(index);

				e.preventDefault();
				return false;
			};

			var _dragend = function(e) {
				if (isTouch) {
					$handle
					.unbind('touchmove', _dragmove);
				}
				else {
					$document
					.unbind('mousemove', _dragmove);
				}

				e = Q.event(e);
				$body.css({cursor:oldCursor});
				$helper.remove();

				//初始化变量
				$sort_placeholder.hide().detach();
				$title_placeholder.hide().detach();

				if (hasTarget) {

					var $current_item = $items.eq(current_index);
					var pre_id = 0;
					var bot_id;
					var current_id = $current_item.find('.tag_drag_handle:eq(0)').classAttr('tag_id');
					var rec_id = root_id;
					var uniqid;
					var parent_uniqid = root_container_id;
					var collapse=1;

					var $item = $items.eq(index);

					(function(){
						if (sortable) {
							//设置部分变量值
							var $parent = $item.parents('.tag_item:eq(0)');
							if($parent.length) {
								rec_id = $parent.find('.tag_drag_handle:eq(0)').classAttr('tag_id');
								collapse = $parent.find('.toggle_expand').length ? 0 : 1;
								uniqid = $parent.attr('id');
							}

							//阻止 向自己子标签拖动  同级标签相邻上下拖动  不同级标签相邻上下拖动
							if (rec_id == current_id) { return; }
							if (index == current_index ) { return; }
							var up_index = $items.index($item.prev('.tag_item'));
							if (index == current_index+1 && prev_index == current_index && prev_index == up_index) { return; }
							if (index == current_index-1 && index ==  prev_index ) { return; }

							// 根据索性获取排序的上方ID值
							if (prev_index >= 0) {
								if(prev_index == (index-1)) {
									var $pre_item = $item.prev('.tag_item:eq(0)');

									if($pre_item.length) {
										pre_id = $pre_item.find('.tag_drag_handle:eq(0)').classAttr('tag_id');
										var $parent_item = $items.eq(prev_index).parents('.tag_item:eq(0)');
										if($parent_item.length) {
											if($parent_item.find('.tag_drag_handle:eq(0)').classAttr('tag_id') == pre_id) {
												if(current_id == $items.eq(index).find('.tag_drag_handle:eq(0)').classAttr('tag_id')) {
													return;
												}
											}
										}
									}
								}
								else if(prev_index == index) {
									pre_id = $item.find('.tag_drag_handle:eq(0)').classAttr('tag_id');
								}
							}
							else {
								pre_id = 0;
							}
							if (pre_id == current_id ) { return ; }
							uniqid = uniqid ? uniqid : parent_uniqid;

							//根据各个变量参数 先进行添加标签操作
							Q.trigger({
								object:'tag_move',
								event:'change',
								url:tag_url,
								data:{
									'tag_name': tag_name,
									'rec_id': rec_id,
									'current_id' : current_id,
									'uniqid' : uniqid,
									'parent_uniqid' : parent_uniqid,
									'collapse' : collapse
								},
								success:function(data){
									//如果添加成果之后，删除掉之前的标签，并且对新标签所在模块进行排序
									if(!data[0].error){
										var $parent = $current_item.parents('.tag_item:eq(0)');
										if($parent.find('.tag_item').length <= 1) {
											$parent.find('.toggle_button').replaceWith("<span class='toggle_button middle'></span> ");
										}
										$current_item.remove();
										Q.trigger({
											object:'tag_sortable',
											event:'change',
											url:tag_url,
											data:{
												'tag_name': tag_name,
												'prev_id': pre_id ,
												'current_id' : current_id,
												'uniqid' : uniqid,
												'parent_uniqid' : parent_uniqid
											}
										});
									}

								}
							});

						}
						else {
							//根据移动到的item获取到各个参数
							rec_id = $item.find('.tag_drag_handle:eq(0)').classAttr('tag_id');
							uniqid = $item.attr('id');
							collapse = $item.find('.toggle_expand').length ? 0 : 1;
							if(rec_id != current_id) {
								//把标签添加到指定的tag
								Q.trigger({
									object:'tag_move',
									event:'change',
									url:tag_url,
									data:{
										'tag_name': tag_name,
										'rec_id': rec_id,
										'current_id' : current_id,
										'uniqid' : uniqid,
										'parent_uniqid' : parent_uniqid,
										'collapse' : collapse,
										'is_refresh' :true
									},
									success:function(data){
										if(!data[0].error) {
											var $parent = $current_item.parents('.tag_item:eq(0)');
											if ($parent.find('.tag_item').length <= 1) {
                                                //$parent.find('span.toggle_expand').removeClass('toggle_expand');
												$parent.find('.toggle_button').replaceWith("<span class='toggle_button middle'></span> ");
											}
											$current_item.remove();
										}

									}
								});
							}
						}

					})();

				}

				e.preventDefault();
				return false;
			};

			//二分函数，获取到鼠标所停留的item索引
			var get_hover_index = function(start, end, y){

				if(y < $items.eq(0).offset().top) { return 0; }
				if(y > ($items.eq(items_count-1).offset().top + $items.eq(items_count-1).height())) { return items_count-1; }

				var middle = parseInt((end-start)/2, 10) + start;

				if(middle == start) { return start; }
				if(middle == end) { return end; }

				var start_y = $items.eq(start).offset().top;
				var end_y = $items.eq(end).offset().top;
				var middle_y = $items.eq(middle).offset().top;

				if(y >= start_y && y <= middle_y) {
					return get_hover_index(start,middle,y,$items);
				}
				if(y >= middle_y && y <= end_y) {
					return get_hover_index(middle,end,y,$items);
				}
				if(y > end_y && y <= (end_y+$items.eq(end).height())){
					return end;
				}
			};

			if (isTouch) {
				$handle
				.bind('touchmove', _dragmove)
				.one('touchend', _dragend);
			}
			else {
				$document
				.bind('mousemove', _dragmove)
				.one('mouseup', _dragend);
			}

			e.preventDefault();
			return false;
		});


	};

})(jQuery);
