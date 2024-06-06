# 升级：

### 1. 首先确定是哪台服务器

>若服务器首次升级需要使用[lims2-env](https://github.com/genee-projects/lims2-deploy-doc#lims2-env)来代替

### 2. 将当前版本的代码筛选至如下结构，去除冗代码并打包：

```
.
├── application
├── cli
├── globals.php -> public/globals.php
├── modules
├── public
├── system
├── vendor
└── version
```
> 如有定制化需求需要升级，建议核对后单独升级定制化需求

### 3. 传递代码至服务器：

正常服务器建议使用[七牛云存储](portal.qiniu.com)来进行代码包上传下载

> 遇到不通其他外网或无法解析域名的服务器可以通过rsync来进行传输 
> 若非首次升级请跳至[第7步](#jump)
    
### 4. 备份旧代码

进入容器`sudo docker exec -it lims2 bash`

将`/usr/share/lims2`目录拷贝至容器内的`/volumes`目录下并筛选无用代码至目录为

```
.
├── application
├── cli
├── globals.php -> public/globals.php
├── modules
├── public
├── system
├── site
├── vendor
└── version
```

### 5. 覆盖代码

将下载或上传上来的代码解包并与拷贝出来的代码进行覆盖

`cp -R Nlims/* /opt/lims2/volumes/lims2/.`
    
### 6. 替换容器

`sudo docker stop lims2`
> 首次升级切记不要删除

参考 [lims2-env](https://github.com/genee-projects/lims2-deploy-doc#lims2-env) 中的命令

清理旧代码 `rm -rf /var/lib/lims2/*`

<span id="jump"></span>

### 7. 整理代码结构

拷贝新代码 `cp -R /volumes/lims2/* /var/lib/lims2/.`

刷新数据库结构 `SITE_ID LAB_ID /var/lib/lims2/cli.php create_orm_tables.php`

更新目录权限 `chown -R www-data:www-data /var/lib/lims2`

建立软连接 `ln -s /var/lib/lims2 /usr/share/.`
    
### 8. 清空 redis

# 部署：


### 开始：

从工程师手里接收安装好操作系统的服务器，需要检查：

1.网络情况：是否能通过vpn登录服务器，能否访问源服务器

2.服务器配置信息：cpu、内存、硬盘大小（是否全部挂载）

3.硬盘分区形式：是否采用lvm（需采用）

4.家目录是否加密（不加密）


### 安装docker服务

1.添加源及安装服务

```
# step 1: 安装必要的一些系统工具
sudo apt-get update
sudo apt-get -y install apt-transport-https ca-certificates curl software-properties-common
# step 2: 安装GPG证书
curl -fsSL http://mirrors.aliyun.com/docker-ce/linux/ubuntu/gpg | sudo apt-key add -
# Step 3: 写入软件源信息
sudo add-apt-repository "deb [arch=amd64] http://mirrors.aliyun.com/docker-ce/linux/ubuntu $(lsb_release -cs) stable"
# Step 4: 更新并安装 Docker-CE
sudo apt-get -y update
sudo apt-get -y install docker-ce

# 安装指定版本的Docker-CE:
# Step 1: 查找Docker-CE的版本:
# apt-cache madison docker-ce
#   docker-ce | 17.03.1~ce-0~ubuntu-xenial | http://mirrors.aliyun.com/docker-ce/linux/ubuntu xenial/stable amd64 Packages
#   docker-ce | 17.03.0~ce-0~ubuntu-xenial | http://mirrors.aliyun.com/docker-ce/linux/ubuntu xenial/stable amd64 Packages
# Step 2: 安装指定版本的Docker-CE: (VERSION 例如上面的 17.03.1~ce-0~ubuntu-xenial)
# sudo apt-get -y install docker-ce=[VERSION]
```

将当前genee用户加入docker组

```
~$ sudo usermod -a -G docker genee
```

2.导入镜像

根据crm站点模块信息选取相关功能镜像：

正常服务器建议使用[七牛云存储](portal.qiniu.com)来进行镜像包上传下载

镜像包七牛云地址在[docker部署文档](https://github.com/genee-projects/lims2-deploy-doc/blob/master/README.md)中

示例：家目录下建立docker_images目录，并且将镜像包下载到此目录并导入

```
~/docker_images$ ls
beanstalkd.v1.10.0-d2015080301.tar.gz      genee-gini.latest.tar.gz                 node-lims2.v1.0.2-d2015081701.tar.gz
cron-server.new.v0.2.0-d2017060501.tar.gz  glogon-server.v0.5.1-d2016081001.tar.gz  redis.v2.8.17-d2015080301.tar.gz
debade-courier.v0.3.0-d2015122301.tar.gz   lims2.v1.0.1-d2017022103.tar.gz          reserv-server.v0.4.3-d2017060101.tar.gz
debade-trigger.v0.1.7-d20150820101.tar.gz  mariadb.v10.1.10-d2015122701.tar.gz      sphinxsearch.v2.2.9-d2015080301.tar.gz
epc-server.v0.4.1-d2016081001.tar.gz       nginx.1.11-alpine.tar.gz                 update.v0.2.12-d2015081401.tar.gz
~/docker_images$ ls *.tar.gz|xargs -n1 -i{} tar zxf {}
~/docker_images$ ls *.tar|xargs -n1 -i{} docker load --input {}
```

### 代码准备
将lims代码包，lims vendor包，自主搭建前台、新统计、基表三、送样报告审批的vendor包下载到**docker_images**目录，必须为此目录，且lims代码及vendor包的命名要遵循以下规则：

```
~/docker_images$ ls
Nlims2.17.306.tgz  vendor.eq-stat.tgz  vendor_php7.0.tgz  vendor.sj-tri.tgz  vendor.unpc.tgz vendor.approval.tgz
~/docker_images$ mv Nlims2.17.306.tgz Nlims2.tgz
~/docker_images$ mv vendor_php7.0.tgz vendor.tgz
~/docker_images$ ls
Nlims2.tgz  vendor.eq-stat.tgz  vendor.sj-tri.tgz  vendor.tgz  vendor.unpc.tgz vendor.approval.tgz
```

### 部署平台系统
#### ansible准备

1.登录reserva服务器

2.进入~/genee-playbooks/host_vars目录，以demo.geneegroup.cn为模板拷贝一个以目标服务器fqdn命名的目录

```
$ cd ~/genee-playbooks/host_vars
~/genee-playbooks/host_vars$ cp -r demo.geneegroup.cn exxxxxx.server.genee.cn
~/genee-playbooks/host_vars$ cd exxxxxx.server.genee.cn
```
3.根据crm中的信息更改lims2spec.yml文件中的相关内容

4.在main.yml文件中配置目标服务器的ip地址及ssh端口（默认22可不配置）

5.在lims2\_id\_list.yml文件中配置site\_id和lab_id

6.进入~/genee-playbooks目录，编辑hosts文件加入刚刚的fqdn

7.编辑~/genee-playbooks/playbooks/install-lims2-begin.yml

```
---
- hosts: exxxxxx.server.genee.cn   #此处写入目标服务器的fqdn
  become: yes
  gather_facts: True
  roles:
    - docker
    - python
    - mail
    - nginx
    - ntp
    - ssh                          
    - users                           
    - mariadb                         
    - redis
    - lims2_01
    - beanstalkd
    - node_lims2
    - reserv_server
    - sphinxsearch
    - genee_updater_server
    - env_server
    - cron_server
    - debade
    - glogon_server
    - epc_server
    - gdoor_server
    - vidmon_haikan
    - nagios
    - logrotate
#    - eq_stat              #此处的新统计、自主搭建前台和基表三要暂时禁用
#    - unpc                 #稍后再执行
#    - sj_tri
#    - sample_approval
#    - lims2-backup
```
#### 执行playbooks
必须在~/genee-playbooks下执行此命令

```
~/genee-playbooks$ ansible-playbook playbooks/install-lims2-begin.yml
```

#### 新增平台genee用户
1.登录客户服务器，清除刚刚部署的lims容器，重新手动执行docker run命令

```
~$ docker rm -f lims2
~$ docker run \
    --name=lims2 \
    -d \
    -v /opt/lims2/volumes:/volumes \
    -v /etc/sphinxsearch/conf.d:/etc/sphinxsearch/conf.d \
    -v /etc/lims2:/etc/lims2 \
    -v /etc/msmtprc:/etc/msmtprc \
    -v /var/run/genee-nodejs-ipc:/var/run/genee-nodejs-ipc \
    -v /var/lib/lims2:/var/lib/lims2 \
    -v /var/lib/lims2_vidcam:/var/lib/lims2_vidcam \
    -v /home/disk:/home/disk \
    -p 3007:9000/tcp \
    --restart=always \
    --privileged \
    docker.genee.in/genee/lims2:v1.0.1-d2017022103
```
<span id="jump2"></span>
2.新增genee用户

```
~$ docker exec -it lims2 bash
root@c32948a33342:/# cd /var/lib/lims2
root@c32948a33342:/var/lib/lims2# SITE_ID=siteid LAB_ID=labid php cli/add_user.php genee
Account: genee
Email[nobody@geneegroup.com]: support@geneegroup.com
Name[Doe John]: 技术支持
Password: XXXXXXX
root@c32948a33342:/var/lib/lims2# SITE_ID=cf LAB_ID=demo php cli/create_orm_tables.php
root@c32948a33342:/var/lib/lims2# exit
~$ sudo chown -R www-data:www-data /home/disk

```
#### 部署新统计、自主搭建前台、基表三

1.reserva服务器上编辑~/genee-playbooks/playbooks/install-lims2-begin.yml

```
---
- hosts: exxxxxx.server.genee.cn
  become: yes
  gather_facts: True
  roles:
#    - docker
#    - python
#    - mail
#    - nginx
#    - ntp
#    - ssh
#    - users
#    - mariadb
#    - redis
#    - lims2_01
#    - beanstalkd
#    - node_lims2
#    - reserv_server
#    - sphinxsearch
#    - genee_updater_server
#    - env_server
#    - cron_server
#    - debade
#    - glogon_server
#    - epc_server
#    - gdoor_server
#    - vidmon_haikan
#    - nagios
#    - logrotate
    - eq_stat
    - unpc
    - sj_tri
    - sample_approval
    - lims2-backup
```
2.执行playbooks

```
~/genee-playbooks$ ansible-playbook playbooks/install-lims2-begin.yml
```

#### 检查配置与功能(客户服务器上操作)
1.自动分发功能配置ip,根据实际情况配置site_url处的ip

```
~$ cat /home/genee/genee-updater-server/config/default.js
module.exports = {
    /** 提供给客户端访问的url地址,部署时需配置 */
    "site_url": "http://xxx.xxx.xxx.xxx:3000",  
    /** 监听端口 */
    "site_port": "3000",
    /** 数据目录名 */
    "data_path": "files",
    /** 日志路径(绝对路径) */
    //"log_dir":"",
    /** 日记记录是否不输出到控制台,生产环境请设置为true */
    "console_quiet": false,
    /** 日志级别设置, 值可为"silly" "debug" "verbose" "info" "warn" "error" 之一,默认为"debug" */
    "log_level": "debug"
};
```
2.glogon服务配置ip，根据实际情况配置lims2_api处的ip

```
/** 注意更改此配置后需要重启进程才生效 */

module.exports = {
    /** 设置 rpc 绑定地址 */
    rpc_bind: 'ipc:///var/run/genee-nodejs-ipc/glogon-server.ipc',
    /** 缓存设置,默认不开启*/
    cache: {
        enable: false, // 是否启用, 默认不开启
        redis_port: 6379,
        redis_host: '127.0.0.1'
    },
    /** 设置控制台输出是否静默, 默认静默 true */
    console_quiet: true,
    /** 设置日志等级, 值为"silly" "debug' 'verbose' 'info' 'warn' 'error' 之一,默认为'debug' */
    log_level: 'debug',
    ports: [
        {
            port: 2430,
            /** lims 2.13 后请设置为站点域名, 127.0.0.1会有问题 */
            lims2_api: 'http://xxx.xxx.xxx/lims/api',         
        }
    ]
}
```
3.检查site目录下system.php文件和vidmon.php文件（若有视频模块）中的IP配置

例：system.php  若知道实际平台被访问ip或域名，可在下方xxx.xxx.xxx.xxx配置并取消注释

```
genee@demo:/var/lib/lims2/sites/cf/labs/demo/config$ sudo vim system.php
<?php
$config['email_name'] = '基理科技大型仪器共享管理系统';

if (defined('CLI_MODE')) {
    //$config['base_url']  = $config['script_url'] = 'http://xxx.xxx.xxx.xxx/lims/';
}
```

例：vidmon.php 下方xxx.xxx.xxx.xxx处会由ansible默认配置成客户服务器eth0的地址，但需安照和工程师确定的服务器被摄像头连通的地址来对照更改。

```
genee@demo:/var/lib/lims2/sites/cf/labs/demo/config$ sudo vim vidmon.php
<?php

//发送capture命令后，上传capture图片的路径
$config['capture_upload_url'] = 'http://xxx.xxx.xxx/lims/!vidmon/snapshot/upload.%vidcam_id';
```
>改完site目录下的文件内容后，请进入lims2容器执行一下create\_orm_tables.php脚本，执行方法见[新增genee用户](#jump2)

>以上所有检查IP配置的操作在不确定实际网络环境的情况下（如服务器在公司部署再邮寄到客户手里的情况）可先不做，其中glogon服务的ip配置留空，保证该服务在未经后期配置的情况下不能工作。

4.检查预约功能、文件系统、消息通知

<span id="jump3"></span>
### 本地备份
已扩展到playbook中的lims2-backup角色中

检查是否生成备份文件目录

```
~$ ls /backups_jail/backups/lims2/
```

手动执行一次cron内容，查看是否工作正常


# 其他后续补充操作：

#### 更新servers.genee.cn

* servers.genee.cn(192.168.0.12 --> /usr/local/share/genee-servers/data)
    * 参考 lims2 代码库
    * 参考客户信息总表
    * 重启 servers.genee.cn 

        ```
        genee@reserva:/usr/local/share/genee-servers/data$ 
        sudo stop genee-servers
        sudo start genee-servers
        ```
        
#### 更新ntp服务配置(ansible自动添加，可作为检查项)

* 更新/etc/ntp.conf

    ```
    driftfile /var/lib/ntp/ntp.drift

    statistics loopstats peerstats clockstats
    filegen loopstats file loopstats type day enable
    filegen peerstats file peerstats type day enable
    filegen clockstats file clockstats type day enable

    server 0.ubuntu.pool.ntp.org
    server 2.ubuntu.pool.ntp.org

    server 0.asia.pool.ntp.org
    server 1.asia.pool.ntp.org
    server cn.pool.ntp.org

    server s2a.time.edu.cn 
    #server s2b.time.edu.cn 
    #server s2c.time.edu.cn 
    server s2g.time.edu.cn 

    server 127.127.1.1
    fudge 127.127.1.1 stratum 10 

    restrict -4 default kod notrap nomodify nopeer noquery
    restrict -6 default kod notrap nomodify nopeer noquery

    restrict 127.0.0.1
    restrict ::1

    ```

#### 备份

* [本地备份](#jump3)

* 远程备份 [备份部署文档](https://bitbucket.org/genee-yiqikong/lims2-backup) (需要权限)

#### 服务器监控

> 具体的nagios完全文档请参考 [帮助文档](https://bitbucket.org/genee-yiqikong/nagios-etc) (需要权限) 
    
> 相应的配置文件存放在 `genee@vpn.genee.cn:~/docker/nagios/usr/local/nagios/` 下

* 检查

    查看客户服务器上是否存在nagios用户以及check_disk文件等
    
* 修改nagios的hosts.cfg、services.cfg

    ```
    genee@vpn.genee.cn:~/docker/nagios/usr/local/nagios/etc/objects$ sudo ./add_nagios.sh exxxxxx.server.genee.cn xxx.xxx.xxx.xxx
    ```

* 重启nagios服务

    ```
    genee@vpn.genee.cn:~/docker/nagios$ docker-compose restart
    ```
* **不要提交`usr/local/nagios/.ssh`下的两个修改**

### 升级至Lims2.5版本

```bash
$ cd /var/lib/lims2/
$ cp public/globals.php.example public/globals.php
$ cp application/config/database.php.example application/config/database.php
$ rm -rf public/cache
```
