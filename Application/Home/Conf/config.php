<?php
return array(
	//'配置项'=>'配置值'
    //上传配置
    'URL_UPLOAD' => array(
        'maxSize' => 2*1024*1024,
        'exts'    => array('jpg', 'gif', 'png', 'jpeg'),
        'rootPath' => './Uploads/',
        'savePath'  =>  'banner/',
        'autoSub'   =>  false,
    ),
);