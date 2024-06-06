# 统计汇总模块

## 部署：
* `config/lab.php` 开启`analysis`(推送数据)和`component`(展示数据)模块
* `modules/analysis/config/analysis.php`
> 为向godiva注册服务时的注册信息，需更改url配置
* `modules/analysis/config/rest.php`
> 两个地址：
> app-center 为向godiva注册服务使用，需更改url
> godiva为数据推送地址
> 需更改url配置及secret（analysis register 时返回的secret）

* `modules/component/config/dashboard.php`
> 配置展示页面地址 `$config['base.src'] = 'http://www2.hunau.edu.cn:8082/dashboard';`
> 找gapper团队要

* 初次部署执行：
    * cli/cli.php analysis register (向app-center注册lims应用)
    * cli/cli.php analysis init (推送表结构)
    * cli/cli.php analysis full true (推送基础数据)
    * cli/cli.php analysis increment (推送聚合数据)
