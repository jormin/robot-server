<?php

namespace api\controllers;

use common\models\dao\Attachment;
use common\models\service\AttachmentService;

class AttachmentController extends BaseController
{

    /**
     * 上传文件
     */
    public function actionUpload(){
        $return = AttachmentService::upload($this->userID);
        $this->autoResult($return);
    }

    /**
     * 下载文件
     */
    public function actionDownload(){
        $path = $_GET['path'];
        $filePath = dirname(__FILE__).'/../..'.$path;
        if(!$path || !file_exists($filePath)){
            die;
        }
        $attachment = Attachment::find()->where(['path'=>$path])->asArray()->one();
        if(!$attachment){
            die;
        }
        \Yii::$app->response->sendFile($filePath,  $attachment['name']);
    }
}
