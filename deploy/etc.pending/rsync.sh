#!/bin/bash
# 此机制尚未实行(xiaopei.li@2012-06-29)
# 同步当前 lims2 的用户目录和数据库至远程服务器

# 对本地的标识
# HOSTNAME 需在主环境里 export
# HOST=$HOSTNAME
HOST=some_server

# 需同步目录
SDIR=/var/lib/lims2/

# TODO 需同步数据库,
# foreach /etc/lims2/backup_list as $site => $lab
#    mysqldump $lab | gzip -c > $lab.sql.gz
# BAKLIST 是需要备份的 lab 列表, 格式为 "site_id\tlab_id"
BAKLIST=/etc/lims2/backup_list
DBUSER=genee
DBPREFIX=lims2_
while read SITE_ID LAB_ID
do
	# TODO 加判断 site_id/lab_id 正确性的判断
	# 加之前需注意backup_list中不能有空行
	DBBAKPATH=$SDIR/dbs
	[ -d $DBBAKPATH ] || mkdir -p $DBBAKPATH
	rm -r $DBBAKPATH/*

	if [ -x /usr/bin/mysqldump ]
	then
		DBNAME=$DBPREFIX$LAB_ID
		mysqldump -u $DBUSER $DBNAME | gzip > $DBBAKPATH/db_$LAB_ID.gz
	fi

done < $BAKLIST

# 远程备份服务器
TSERVER=192.168.0.15
# 远程备份根目录
TDIR=/backup/rsync/$HOST
# 远程用户
TUSER=rsync

echo $TIR

exit
##############
# -a, archive mode, which ensures that symbolic links, devices, attributes, permissions, ownerships, etc. are preserved in the transfer. 适于备份
# -z, compress
# -v, verbose
# --delete, 在服务器端删除客户端已删除的文件. archive mode 中不包含 --delete
# --rsh=ssh, 使用 ssh 
# TODO log
OPTS="-azv --delete --rsh=ssh"

# now the actual transfer
rsync $OPTS $SDIR $TUSER@$TSERVER:$TDIR
