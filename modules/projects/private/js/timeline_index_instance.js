/*
dtStart: 开始时间/Linux时间戳
dtEnd: 结束时间/Linux时间戳
nodes: 该时间段内的node/json格式
*/
jQuery(function($){
	var view = new TimelineView();
	view.init({
		dtStart : dtStart,
		dtEnd : dtEnd
	});
	view.addNodes(nodes);
});
