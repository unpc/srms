jQuery(function($){
	// 得到服务器的时区差
	var utc_offset = Q['utc_offset'] | 0;

	var d=new Date();
	//js获取的时间与服务器的时间偏移
	var local_offset = server_time*1000 - d.getTime();
	
	var last_remote_time = 0;//记录上次的remote_time;
	
	//总偏差=js与服务器的偏差 + 时区偏差
	var offset = local_offset + utc_offset;
	setInterval(function(){
		var local_utc = moment().utc(); //utc时间
		var remote_time = local_utc + offset;
		var diff = remote_time - last_remote_time;
		
		remote_time = last_remote_time + (Math.round(diff/1000) * 1000);
		
		last_remote_time = remote_time;
		var nd = new Date(remote_time); 
		var time = moment(nd).utc().format('YYYY/MM/DD HH:mm:ss');
		$('#footer_time').html(time);
	}, 1000);	
})
