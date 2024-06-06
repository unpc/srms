#!/usr/bin/python
# -*- coding: UTF-8 -*-
import os
import sys
startTime = float(sys.argv[1])
endTime = float(sys.argv[2])

def print_files(path):
    lsdir = os.listdir(path)
    dirs = [i for i in lsdir if os.path.isdir(os.path.join(path, i)) ]
    files = [i for i in lsdir if os.path.isfile(os.path.join(path, i)) ]
    if files:
        for f in files:
            target = os.path.join(path, f)
            target_stat = os.stat(target)
            if  target_stat.st_mtime >= startTime and target_stat.st_mtime < endTime:
#                print (target_stat.st_mtime,target_stat.st_ino,os.path.getsize(target),target)
                os.remove(target)
    if dirs:
        for d in dirs:
            print_files(os.path.join(path, d))


# 删除个人文件夹中的文件
private_dir = "/home/disk/" + sys.argv[3] + "/" +sys.argv[4] + "/share/users"
private_paths = os.listdir(private_dir)

for pri in private_paths:
   pri_path = os.path.join(private_dir, pri) + "/private"
   if os.path.exists(pri_path):
      print_files(pri_path)
for pub in private_paths:
   pub_path = os.path.join(private_dir, pub) + "/public"
   if os.path.exists(pub_path):
      print_files(pub_path)
for lab in private_paths:
   lab_path = os.path.join(private_dir, lab) + "/lab"
   if os.path.exists(lab_path):
      print_files(lab_path)

# 删除课题组中的文件
lab_dir = "/home/disk/" + sys.argv[3] + "/" +sys.argv[4] + "/share/labs"
lab_paths = os.listdir(lab_dir)

for lab in lab_paths:
   lab_path = os.path.join(lab_dir, lab)
   print_files(lab_path)

# 删除公共文件夹下的文件
pub_dir = "/home/disk/" + sys.argv[3] + "/" +sys.argv[4] + "/share/public"
print_files(pub_dir)
