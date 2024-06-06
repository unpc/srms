#!/bin/sh

# sudo defaults write com.apple.loginwindow LogoutHook /usr/local/bin/lims_logoff

URL="http://lims.sky.nankai.edu.cn/index.php/!equipments/computer/logoff"
USER=`/usr/bin/logname`
COMPUTER=`/bin/hostname -s`

curl $URL -d user=$USER -d computer=$COMPUTER
