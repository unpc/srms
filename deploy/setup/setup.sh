#! /bin/bash
# lims2 服务器初始设置脚本
# (xiaopei.li@2011-12-08)
# 此脚本应与 plugins 目录一同打包使用
# 执行此脚本前, 应先配置好服务器的网络
# 参考: http://dev.genee.cc/doku.php/lims2/deploy/server


# 关于 php 配置 (xiaopei.li@2012-06-19)
#
# php 配置的加载顺序是:
# 1. 根据 c[gl]i 执行 /etc/php5/c[gl]i/php.ini
# 2. 按字典序遍历执行 /etc/php5/c[gl]i/conf.d/*
# 3. 要注意 php5/c[gl]i/conf.d 只是 php5/conf.d 的 synbol link
#
# 故可按以下方式修改 php 配置:
# 1. 与 c[gl]i 相关的配置写到 php5/c[gl]i/php.ini 的文件底部
# 2. 重载 extensions 的配置写在 php5/conf.d/zzz_genee.ini 中
#
# 另外, 在 php 文档中看到 .user.ini, but 'Only INI settings with the modes PHP_INI_PERDIR and PHP_INI_USER will be recognized in .user.ini-style INI files.', 只是些小配置, 用处不大, 别人看到后不用试了

# TODO setup 过程中, 在安装 mysql 和 mysql 创建 genee 时, 都需要输入密码, 可查找 unattended(无人值守) 的方法 (xiaopei.li@2012-02-20)

# 更新 sysctl
function config_sysctl {
	dir="/etc/sysctl.d"
	file="$dir/20-genee.conf"
	if [ -d "$dir" ] && [ ! -e "$file" ]; then
		echo "Configuring sysctl"
		cp "$PWD/plugins/sysctl" $file
		sysctl -p
	fi
}

# 更新 apt
function update_apt_source {
	echo "Updating apt-source"

	codename=`lsb_release -sc`
	sed -i "s/CODENAME/$codename/g" $PWD/plugins/sources.list

	cp /etc/apt/sources.list /etc/apt/sources.list.bak
	cp $PWD/plugins/sources.list /etc/apt/sources.list

	apt-get update -qy
}

# 安装 lims2 的依赖包
function install_dependancies_and_utils {
	echo "Installing dependancies"
	# dependancies
	apt-get install -qy mysql-server memcached lighttpd lighttpd-mod-magnet php5-fpm php5-cli php5-memcache php5-mysql php5-gd php5-xcache php5-suhosin php5-curl php5-ldap xinetd liblua5.1-0 clamav

	echo "Installing recommendations"
	# recommendations
	apt-get install -qy msmtp

	echo "Installing utils"
	# utils
	apt-get install -qy acpid tree autossh
}

function setup_php_log {
	echo "Configuring php log"

	# log file
	# 若修改日志文件地址, 需同步修改 plugins/php/c[gl]i.ini
	log_dir="/var/log/php5"
	if [ ! -d $log_dir ]; then
		mkdir $log_dir
	fi

	cli_log="$log_dir/cli.log"
	cgi_log="$log_dir/cgi.log"
	if [ ! -f $cli_log ]; then
		touch $cli_log
	fi
	if [ ! -f $cgi_log ]; then
		touch $cgi_log
	fi

	chown www-data:www-data $cli_log
	chown www-data:www-data $cgi_log
}

# 设置 php cgi
function setup_php_cgi {
	echo "Configuring php cgi"

	config_file="/etc/php5/cgi/php.ini"
	cat "$PWD/plugins/php/cgi.ini" >> $config_file
}

# 设置 php cli
function setup_php_cli {
	echo "Configuring php cli"

	config_file="/etc/php5/cli/php.ini"
	cat "$PWD/plugins/php/cli.ini" >> $config_file
}

function setup_php_extensions {
	echo "Modify php extensions config"
	cp "$PWD/plugins/php/zzz_genee.ini" $php_extension_config_dir
}

# 设置 php lua 库
function setup_php_lua {
	extention_file="$php_extension_dir/lua.so"
	config_file="$php_extension_config_dir/lua.ini"
	if [ ! -e $extention_file ]; then
		echo "Adding php lua.so"

		machine=`uname -m`

		if [ "$machine" == "x86_64" ]; then
		    lua_file="$PWD/plugins/php/lua_64.so"
		else
		    lua_file="$PWD/plugins/php/lua_32.so"
		fi

		cp $lua_file $extention_file
	fi
	if [ ! -e $config_file ]; then
		echo "Configuring php lua.so"
		cp "$PWD/plugins/php/lua.ini" $config_file
	fi
}

function customize_php {
	echo "Customizing php"

	php_extension_dir=`php -r "echo ini_get('extension_dir');"`
	php_extension_config_dir="/etc/php5/conf.d"

	setup_php_log
	# setup_php_cgi
	setup_php_cli
	setup_php_extensions
	setup_php_lua
}

function init_mysql {
	echo "Initiating mysql"

	mysql -u root -p -e "GRANT ALL ON *.* TO genee@'localhost' IDENTIFIED BY ''"
}

function customize_bash {
	echo "Customizing bash"

	cp "$PWD/plugins/bash_genee" /etc/

	echo '# genee
if [ -f /etc/bash_genee ]; then
    . /etc/bash_genee
fi' >> /etc/bash.bashrc

}

# main
update_apt_source && \
	install_dependancies_and_utils && \
    customize_bash && \
    config_sysctl && \
    customize_php && \
    init_mysql
