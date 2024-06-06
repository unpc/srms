DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `name` varchar(150) NOT NULL,
  `name_abbr` varchar(150) NOT NULL DEFAULT '',
  `parent_id` bigint(20) NOT NULL DEFAULT '0',
  `root_id` bigint(20) NOT NULL DEFAULT '0',
  `readonly` tinyint(4) NOT NULL DEFAULT '0',
  `ctime` int(11) NOT NULL DEFAULT '0',
  `mtime` int(11) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL DEFAULT '0',
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`parent_id`),
  KEY `parent` (`parent_id`),
  KEY `root` (`root_id`),
  KEY `ctime` (`ctime`),
  KEY `mtime` (`mtime`),
  KEY `weight` (`weight`)
) ENGINE=MyISAM AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;
LOCK TABLES `tag` WRITE;
INSERT INTO `tag` VALUES ('组织机构','zu zhi ji gou',0,0,1,1385357274,1385357274,0,1),('天津大学','tian jin da xue',1,1,0,1385449136,1385449136,1,4),('南开大学','nan kai da xue',1,1,0,1385449139,1385449139,2,5),('化工学院','hua gong xue yuan',4,1,0,1385449150,1385449150,1,6),('化工学院大型仪器平台','hua gong xue yuan da xing yi qi ping tai',6,1,0,1385449157,1385449157,1,7),('结晶中心','jie jing zhong xin',6,1,0,1385449165,1385449165,2,8),('化学学院','hua xue xue yuan',5,1,0,1385449800,1385449800,3,64),('化学学院行政','hua xue xue yuan xing zheng',64,1,0,1385449807,1385449807,1,65),('化学实验教学中心','hua xue shi yan jiao xue zhong xin',64,1,0,1385449817,1385449817,2,66),('基础化学实验室','ji chu hua xue shi yan shi',66,1,0,1385449825,1385449825,1,67),('中级化学实验室','zhong ji hua xue shi yan shi',66,1,0,1385449832,1385449832,2,68),('综合化学实验室','zong he hua xue shi yan shi',66,1,0,1385449838,1385449838,3,69),('化学教学中心办公室','hua xue jiao xue zhong xin ban gong shi',66,1,0,1385449845,1385449845,4,70),('元素有机化学国家重点实验室','yuan su you ji hua xue guo jia zhong dian shi yan shi',64,1,0,1385449855,1385449855,3,71),('功能高分子材料实验室','gong neng gao fen zi cai liao shi yan shi',64,1,0,1385449865,1385449865,4,72),('化学系','hua xue xi',64,1,0,1385449876,1385449876,5,73),('无机化学教研室','wu ji hua xue jiao yan shi',73,1,0,1385449886,1385449886,1,74),('有机化学教研室','you ji hua xue jiao yan shi',73,1,0,1385449895,1385449895,2,75),('分析化学教研室','fen xi hua xue jiao yan shi',73,1,0,1385449903,1385449903,3,76),('物理化学教研室','wu li hua xue jiao yan shi',73,1,0,1385449911,1385449911,4,77),('高分子化学教研室','gao fen zi hua xue jiao yan shi',73,1,0,1385449919,1385449919,5,78),('化学系其它','hua xue xi qi ta',73,1,0,1385449927,1385449927,6,79),('材料化学系','cai liao hua xue xi',64,1,0,1385449937,1385449937,6,80),('新催化材料化学研究所','xin cui hua cai liao hua xue yan jiu suo',80,1,0,1385449949,1385449949,1,81),('新能源化学研究所','xin neng yuan hua xue yan jiu suo',80,1,0,1385449956,1385449956,2,82),('材料系其他','cai liao xi qi ta',80,1,0,1385449963,1385449963,3,83),('农药国家工程研究中心','nong yao guo jia gong cheng yan jiu zhong xin',64,1,0,1385449979,1385449979,7,84),('化学学院中心实验室','hua xue xue yuan zhong xin shi yan shi',64,1,0,1385449988,1385449988,8,85);
UNLOCK TABLES;
