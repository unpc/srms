<?php

$config['system.ready'][] = 'NFS::setup';
$config['nfs.submit_require_file_has_uploaded'][] = 'NFS::file_has_uploaded';

$config['nfs.api.v1.lite.GET'][] = 'Nfs_Lite_API::nfs_lite_get';
$config['nfs.api.v1.lite.POST'][] = 'Nfs_Lite_API::nfs_lite_post';
$config['nfs.api.v1.lite.DELETE'][] = 'Nfs_Lite_API::nfs_lite_delete';
