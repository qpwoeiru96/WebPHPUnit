<?php
require(dirname(dirname(__DIR__)) . '/framework/yiilite.php');

$config = CMap::mergeArray(
    require(dirname(dirname(__DIR__)) . '/common/config/base.php'),
    array(
    //基础目录为当前目录上一级 application
    'basePath'    => dirname(__DIR__) ,
    //runtime目录为与基础目录同级的runtime
    'runtimePath' => dirname(__DIR__) . '/cache'
)
);
            
//注册一个CFakeApplication
eval('class CFakeApplication extends CApplication { public function processRequest() {} }');

Yii::createApplication('CFakeApplication', $config);
Yii::$enableIncludePath = false;