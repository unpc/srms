#!/bin/bash

# LIMS2 的仪器摄像头监控功能依赖 crtmpserver(C++ RTMP Server) (>=0.690).

# apt-get 安装 crtmpserver 的缺陷:
# 1.
# Ubuntu apt 源中的 crtmpserver 版本都很老(至少到 12.04 仍仅为 0.611),
# apt-get 安装后, 还需用官网下载的二进制替换 /usr/sbin/crtmpserver, 才达到系统要求.
# 2.
# 而针对操作系统为 Ubuntu 10.04 老客户, apt 源中无 crtmpserver 及相关依赖包

# 故自己制作 deb (crtmpserver_0.716_amd64.deb), 并做此安装脚本,

# 安装 crtmpserver 应:
# $ cd crtmp
# $ sudo ./install.sh

# 但需注意:
#
# 1. 若操作系统为 10.04, 需取消注释以下依赖安装
# dpkg -i libtinyxml2.6.2_2.6.2-1_amd64.deb
# dpkg -i multiarch-support_2.13-20ubuntu5_amd64.deb
# dpkg -i libssl1.0.0_1.0.0e-2ubuntu4_amd64.deb
#
# 2. 更高版本的系统, 第一次运行此脚本时, 也可能由于依赖未装,
# 而安装不成功. 需 $ sudo aptitude -f, 并选择安装依赖的选项
# 再重新 $sudo ./install.sh

# (xiaopei.li@2012-10-27)

dpkg -i crtmpserver_0.716_amd64.deb

CONFIG_PATH="/etc/lims2/crtmpserver"
CONFIG_FILE="$CONFIG_PATH/live.lua"
INIT_FILE="/etc/init.d/crtmpd"

if [ ! -d $CONFIG_PATH ]; then
	mkdir -p $CONFIG_PATH
fi
if [ ! -e "$CONFIG_FILE" ]; then
	cp live.lua $CONFIG_FILE
fi

if [ ! -e "$INIT_FILE" ]; then
	cp crtmpd $INIT_FILE
fi

LOG_DIR="/var/log/crtmp"
if [ ! -d $LOG_DIR ]; then
	mkdir $LOG_DIR
fi

update-rc.d crtmpd defaults

service crtmpd start
