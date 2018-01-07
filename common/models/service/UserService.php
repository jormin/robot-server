<?php

namespace common\models\service;

use common\models\dao\Attachment;
use common\models\dao\UserChatRecord;
use common\models\dao\UserConfig;
use common\models\lib\UserMsg;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WMV;
use Jormin\BaiduSpeech\BaiduSpeech;
use Jormin\Excel\Excel;
use Jormin\IP\IP;
use Jormin\TuLing\TuLing;
use League\Flysystem\Filesystem;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
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
        $response = AttachmentService::upload();
        if($response['status'] == 0){
            return $return;
        }
        $messageAudio = $response['data'];
        $outFile = AttachmentService::convert($messageAudio);
        if(!file_exists($outFile)){
            $return['msg'] = '音频文件转码出错';
            return $return;
        }
        $baiduSpeechParams = \Yii::$app->params['baiduSpeech'];
        $baiduSpeech = new BaiduSpeech($baiduSpeechParams['appID'], $baiduSpeechParams['apiKey'], $baiduSpeechParams['secretKey']);
        $response = $baiduSpeech->recognize($outFile, null, null, $userID);
        if(!$response['success']){
            $return['msg'] = '识别语音文件失败，失败原因：'.$response['msg'];
            $return['data'] = $response['data'];
            $return['file'] = $outFile;
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
        $reply = $response['text'];
        $response = $baiduSpeech->combine(\Yii::$app->basePath.'/../storage/combine/', $reply, $userID);
        if(!$response['success']){
            $return['msg'] = '合成语音文件失败，失败原因：'.$response['msg'];
            return $return;
        }
        // 上传文件
        $qiniuParams = \Yii::$app->params['qiniu'];
        $adapter = new QiniuAdapter($qiniuParams['accessKey'], $qiniuParams['secretKey'], $qiniuParams['bucket'], $qiniuParams['domain']);
        $flysystem = new Filesystem($adapter);
        $replyAudio = str_replace(\Yii::$app->basePath.'/..',"", $response['data']);
        $flysystem->write($messageAudio, file_get_contents(\Yii::$app->basePath . '/..'.$messageAudio));
        $flysystem->write($replyAudio, file_get_contents(\Yii::$app->basePath . '/..'.$response['data']));

        $userChatRecord = new UserChatRecord();
        $userChatRecord->userID = $userID;
        $userChatRecord->message = $userMessage;
        $userChatRecord->reply = $reply;
        $userChatRecord->messageAudio = $messageAudio;
        $userChatRecord->replyAudio = $replyAudio;
        $userChatRecord->config = json_encode(\Yii::$app->params['defaultChatConfig']);
        if(!$userChatRecord->save()){
            $return['msg'] = '记录文件失败';
        }
        $return = ['status'=>1, 'msg'=>'操作成功', 'data'=>['reply'=>$reply, 'audio'=>$replyAudio]];
        return $return;
    }
}
