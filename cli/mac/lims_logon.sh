#!/bin/sh

# sudo defaults write com.apple.loginwindow LoginHook /usr/local/bin/lims_logon

URL="http://lims.sky.nankai.edu.cn/index.php/!equipments/computer/logon"
USER=${1}
COMPUTER=`/bin/hostname -s`

curl $URL -d user=$USER -d computer=$COMPUTER