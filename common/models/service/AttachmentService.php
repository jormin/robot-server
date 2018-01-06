<?php

namespace common\models\service;

use common\models\dao\Attachment;
use common\models\lib\UserMsg;
use Jormin\Excel\Excel;
use yii\web\UploadedFile;

/**
 * Class AttachmentService
 * @package common\models\service
 */
class AttachmentService
{

    /**
     * 上传文件
     *
     * @param $userID
     * @return array
     */
    public static function upload($userID){
        $return = ['status'=>0, 'msg'=>UserMsg::$timeOut];
        $uploadFile = UploadedFile::getInstanceByName('file');
        if (!$uploadFile) {
            return $return;
        }
        $fileName = time().'.'.$uploadFile->extension;
        $relatePath = '/storage/upload/'.date('Y').'/'.date('m').'/'.date('d');
        $absolutePath = dirname(__FILE__).'/../../..'.$relatePath;
        if(!file_exists($absolutePath)){
            mkdir($absolutePath,0777, true);
        }
        $absolutePath .= '/'.$fileName;
        $relatePath .= '/'.$fileName;
        if(!$uploadFile->saveAs($absolutePath)){
            $return['msg'] = '保存文件失败';
            return $return;
        }
        $attachment = new Attachment();
        $attachment->userID = $userID;
        $attachment->name = $uploadFile->baseName.'.'.$uploadFile->extension;
        $attachment->path = $relatePath;
        $attachment->type = 0;
        if($attachment->save()){
            $return = ['status'=>1, 'msg'=>'上传文件成功', 'data'=>$attachment->attributes];
        }else{
            $return['msg'] = '记录文件失败';
        }
        return $return;
    }
}
