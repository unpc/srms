jQuery(function($){

	// 单文件上传
	//点击按钮，弹出一个dialog
	var $upload_one_file = $('#' + upload_one_file);

	// 大文件上传
	var $upload_big_file = $('#' + upload_big_file);

	//多文件上传
	var $upload_multiple_file = $('#' + upload_multiple_file_id);

	$upload_one_file.click(function() {
		var upload_form_opt = {
			title: '上传文件',
			data: upload_form
		};
		Dialog.show(upload_form_opt);
	});

	$upload_big_file.click(function() {
		var upload_big_form_opt = {
			title: '上传大文件',
			data: upload_big_form
		};
		Dialog.show(upload_big_form_opt);
	});

	$upload_multiple_file.click(function () {
		var upload_multiple_form_opt = {
			title: '批量上传文件',
			data: upload_multiple_form
		};
		Dialog.show(upload_multiple_form_opt);
	});
});
