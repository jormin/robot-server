<?php

namespace frontend\controllers;

use yii\web\Controller;

class AttachmentController extends Controller
{

    /**
     * 下载文件
     */
    public function actionDownload(){
        $path = $_GET['path'];
        $filePath = \Yii::$app->basePath.'/..'.$path;
        if(!$path || !file_exists($filePath)){
            die;
        }
        \Yii::$app->response->sendFile($filePath);
    }
}