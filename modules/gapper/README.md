# gapper配置
gapper模块提供lims用户升级为gapper用户，gapper用户登陆功能。需要进行如下配置

##在gapper app配置

###gapper/config/gapper.php
配置各个app的信息。app需要提前在gapper中注册好，client_id必须一致

	$config['apps'] = [
		'hello' => [ //app名称，唯一
			'intro' => '简介', //app简介
			'name' => 'hello', //app名称
			'title' => 'title', //app标题，用于显示在前台页面
			'url' => 'http://hello.gapper.in', //app url
			'icon_url' => '!gapper/images/gapper.png', //app的图标，当没有获得gapper图标时显示该图标
	        'client_id' => '123456', //app的client_id，用于跳转
		],
	    //...
	];

###gapper/config/layout.php
配置各个app在前台显示的图标

	$config['sidebar.menu']['hello'] = array(  //app名称
	    '#module' => 'gapper',
		'desktop' => array(
			'title' => 'hello',
			'icon' => '!gapper/icons/48/hello.png', //app 48px图标
			'url' => '!gapper/index?app=hello',
			),
		'icon' => array(
			'title' => 'hello',
			'icon' => '!gapper/icons/32/hello.png', //app 32px图标
			'url' => '!gapper/index?app=hello',
			),
		'list'=>array(
			'title' => 'hello',
			'icon' => '!gapper/icons/16/gapper.png', //app 16px图标
			'url' => '!gapper/index?app=hello',
			),
	);
	//...


## 各个站点开启gapper模块配置

### lab/labs/demo/config/lab.php
开启gapper模块

	$config['modules'] += array(
    	'gapper' => TRUE
    );


### lab/labs/demo/config/gapper.php
在gapper中注册lims-demo app   配置client_id和client_secret

	$config['client'] = [
	    'api' => 'http://gapper.in/api',
	    'client_id' => 'c5ece32d25eeea71f27dabdaa13bd5fa', //lims-demo client_id
	    'client_secret' => 'bda450a56de80cb31fce8e0cdca7ec47',   //lims-demo client_secret
	];