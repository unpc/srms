# sync
RQ181911—多站点人员信息、课题组信息建立同步机制
##产品需求：
* 在其中一个站点新建人员、课题组会在其余站点自动建立相同的用户及课题组
* 管理员在其中一个站点对人员、课题组进行激活操作，其他站点同时激活成功(不需要同步角色信息)
* 山大做完后，其他学校需要可以简单配置使用此功能
* 需同步审批者、建立者、组织机构，不同步头像、文件系统

##人员同步规则：
* 同步人员基本信息、及账号密码、课题组信息、课题组PI
* 在任何站点添加、修改人员及课题组PI信息会同步到各个站点（临时课题组、临时用户不同步）
* 在任何站点无法删除人员、课题组，只能做未激活操作（使用记录未同步，删除时需要判断所有站点是否有使用记录）
* 组织机构同步：其中一个站点增加、修改、删除，其余站点全部跟着同步

###各站点同步规则

####山东大学：人员信息+组织机构同步
* 同步人员基本信息、及账号密码、课题组信息、课题组PI
* 在任何站点添加、修改人员及课题组PI信息会同步到各个站点（临时课题组、临时用户不同步）
* 在任何站点无法删除人员、课题组，只能做未激活操作（使用记录未同步，删除时需要判断所有站点是否有使用记录）
* 组织机构同步：其中一个站点增加、修改、删除，其余站点全部跟着同步

####广东工业：
* 人员信息同步（与山大机制一样）
* 院级仪器映射到校级仪器列表（校级有自己的仪器）
* 校级统计模块汇总各院级仪器数据

####浙江中医药：
* 人员同步是按照山大的方式，两边系统人员和课题组同步
* 所有仪器全部映射到滨文校区
* 两套系统均有历史数据
* 采用滨文校区gateway（上海提供的）

####武汉大学：
* 人员信息同步（与山大机制一样），做了统一身份认证对接包含了组织机构
* 各院级仪器映射到校级
* 校级统计模块汇总各院级仪器数据
* 学院包含：武汉大学基础医学院、武汉大学化学院、武汉大学药学院、武汉大学分析测试中心（新）、武汉大学物理学院、武汉大学生科院

##实现机制
* mq为单向通信机制, 每次同步通过hook将信息提交给rabbitmq的exchange, 再根据绑定分发到相关队列
  * swoole worker 自动将当前站点队列绑定至exchenge, 通过supervisor控制
  > 即, 当需要A <- B, A <- C 的单向同步时, 只需配置A站点 的swoole-worker(即 只有A的队列绑定exchange)
  > 当需要A <-> B, A <-> C 的双向同步时, 需配置A,B,C站点 的swoole-worker(A, B, C的队列绑定exchange)
  
  * config/sync.php中的topics, config/hooks.php中的_r hooks 决定一系列事件是否发送
  > 即, 当整个系统不需要仪器同步时, 将config/sync.php的equipment.(save|delete), config/hooks.php中的user_equipment.(connect|disconnect)删除即可

##开发配置

* lab.php（update）
```php
    $config['modules']['sync'] = TRUE;
```
* site/sync/config/hooks.php (add)
```php
<?php
//需要更新connect关系需要加上以下hooks
$config['user_lab.connect'][] = 'Sync_Publish::on_relationship_connect';
$config['user_lab.disconnect'][] = 'Sync_Publish::on_relationship_disconnect';
$config['user_group.disconnect'][] = 'Sync_Publish::on_relationship_disconnect';
$config['user_group.disconnect'][] = 'Sync_Publish::on_relationship_disconnect';
```

* site/sync/config/rules.php (add)
```php
<?php
/**
 * 人员、仪器、课题组等基础信息同步默认规则
 */
$config['sync_rules']['user'] = [
    'allow_delete' => false,//允许各个站点删除
];

$config['sync_rules']['lab'] = [
    'allow_delete' => false,//允许各个站点删除
];

$config['sync_rules']['equipment'] = [
    'allow_delete' => true,//允许各个站点删除
];

```

* sync.php
```php
<?php
$config['sites'] = [
    'zcmu' => [
        'name' => '浙江中医药大学中医药科学院(滨文校区)(zcmu)',
        'url' => 'http://IP/lims',
    ],
    'zcmu_yxy' => [
        'name' => '浙江中医药大学中医药科学院(富春校区)(zcmu_yxy)',
        'url' => 'http://IP/lims_other',
    ],
];

//控制哪些ORM同步,控制历史数据是否处理
$config['topics'] = [
    'user.save',
    'tag.save',
    'lab.save',
    'user.delete',
    'tag.delete',
    'lab.delete',
];

```
##运维部署

1. 部署rabbitmq服务
  
    - docker pull rabbitmq:3.7.17-management-alpine

    - docker run \
    --name rabbitmq \
    -d \
    -p 5672:5672/tcp \
    -p 15672:15672/tcp \
    -v /home/genee/rabbitmq:/var/lib/rabbitmq:rw \
    --restart=always \
    --privileged \
    -e RABBITMQ_DEFAULT_USER=user \
    -e RABBITMQ_DEFAULT_PASS=dmw2F2fc \
     rabbitmq:3.7.17-management-alpine
     
    - docker正常启动且访问IP:15672可使用user账户登录

2. 升级所有站点lims2容器

    - 使用"docker.genee.in/genee/lims2:v1.2.0-d2019091201"镜像构建容器(swoole4.3.6、php7.2)，找不到镜像就去192.168.18.17重新导出

3. 所有站点容器升级完之后升级lims2代码

    ```php
       为了减少不可预期问题的出现，建议在每个站点代码升级完成之后立即注释掉当前站点下config/lab.php的sync模块
       $config['modules']['sync'] = TRUE;//注释掉该行
    ```
    - docker exec -it lims2 bash
    
    - cd /var/lib/lims2/ && cp -r supervisor.*  /etc/supervisor/conf.d/
    
    - supervisorctl reload
    
    - 检查LIMS仪器预约功能是否正常（为了尽可能减少对线上的影响时间，所以多做这一步）
    
    - 在/var/lib/lims2/下执行 composer update
    
    - 替换/var/lib/lims2/modules/sync/supervisor.sync.conf中SITE_ID_VALUE,LAB_ID_VALUE为对应站点SITE_ID，LAB_ID
    
    - cp /var/lib/lims2/modules/sync/supervisor.sync.conf /etc/supervisor/conf.d/
    
    - **不要重启supervisorctl**
   
4. 以下操作尽量保持各个站点同时操作 

    - 修改lab.php，去掉刚刚对sync模块的注释 

    - 执行SITE_ID=SITE_ID LAB_ID=LAB_ID php /var/lib/lims2/cli/cli.php sync sync_objects

    - 重启lims容器。查看cli.log是否频繁报错，不报错即为正常。

5. 验证历史数据是否正常，包含检查同步功能是否正常，如添加用户，仪器，课题组，用户更换课题组，



##done，验证下试试