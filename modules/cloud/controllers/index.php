<?php
class Index_Controller extends _Controller {
	function download_latest_backup() {
		if (!L('ME')->access('下载备份')) {
			URI::redirect('error/401');
		}

		$full_path = Cloud::get_latest_backup_path(); // 此处下载路径重新计算而不用链接传递, 是考虑这样对系统暴露更少, 更安全
		if (is_file($full_path)) {
			Downloader::range_download($full_path);
		}
	}
}
