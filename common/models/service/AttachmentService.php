<?php

namespace common\models\service;

use common\models\dao\Attachment;
use common\models\lib\UserMsg;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WMV;
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
     * @return array
     */
    public static function upload(){
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
        $return = ['status'=>1, 'msg'=>'上传文件成功', 'data'=>$relatePath];
        return $return;
    }

    /**
     * 文件转码
     *
     * @param $file
     * @return string
     */
    public static function convert($file){
        $inputFile = \Yii::$app->basePath . '/../'.$file;
        $outFile = pathinfo($inputFile, PATHINFO_DIRNAME).'/'.basename($inputFile, pathinfo($inputFile, PATHINFO_EXTENSION)).'.wav';
        p($inputFile);
        p($inputFile);
        $ffmpeg = FFMpeg::create();
        $audio = $ffmpeg->open($file);
        $audio->save(new WMV(), $outFile);
        return $outFile;
    }
}
