<?php

namespace common\models\service;

use common\models\dao\Attachment;
use common\models\lib\UserMsg;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WMV;
use Jormin\BaiduSpeech\BaiduSpeech;
use Jormin\Excel\Excel;
use Jormin\IP\IP;
use Jormin\TuLing\TuLing;
use yii\web\UploadedFile;

/**
 * Class UserService
 * @package common\models\service
 */
class UserService
{

    /**
     * 上传文件
     *
     * @param $userID
     * @return array
     */
    public static function chat($userID){
        $return = ['status'=>0, 'msg'=>UserMsg::$timeOut];
        $response = AttachmentService::upload($userID);
        if($response['status'] == 0){
            return $return;
        }
        $inputFile = \Yii::$app->basePath . '/../'.$response['data'];
        $outFile = pathinfo($inputFile, PATHINFO_DIRNAME).'/'.basename($file, pathinfo($file, PATHINFO_EXTENSION)).'.wav';
        $ffmpeg = FFMpeg::create();
        $audio = $ffmpeg->open($file);
        $audio->save(new WMV(), $outFile);
        if(!file_exists($outFile)){
            $return['msg'] = '音频文件转码出错';
            return $return;
        }
        $baiduSpeechParams = \Yii::$app->params['baiduSpeech'];
        $baiduSpeech = new BaiduSpeech($baiduSpeechParams['appID'], $baiduSpeechParams['apiKey'], $baiduSpeechParams['secretKey']);
        $response = $baiduSpeech->recognize($outFile, null, null, $userID);
        $response = $baiduSpeech->combine(\Yii::$app->basePath.'/../storage/combine/', $response['text'], 1);
        if(!$response['success']){
            $return['msg'] = '合成语音文件失败，失败原因：'.$response['msg'];
            return $return;
        }
        $userMessage = current($return['data']['result']);
        $tuLingParams = \Yii::$app->params['tuLing'];
        $tuLing = new TuLing($tuLingParams['apiKey']);
        $location = IP::ip2addr(gethostbyname(gethostname()), true, '');
        $response = $tuLing->chat($userMessage, 1, $location);
        if(!$response['text']){
            $return['msg'] = '没有回复文本消息';
            return $return;
        }
        $response = $baiduSpeech->combine(\Yii::$app->basePath.'/../storage/combine/', $response['text'], $userID);
        if(!$response['success']){
            $return['msg'] = '合成语音文件失败，失败原因：'.$response['msg'];
            return $return;
        }
        $return = ['status'=>1, 'msg'=>'操作成功', 'data'=>$response['data']];
        return $return;
    }
}
