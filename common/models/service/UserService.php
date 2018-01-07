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

    public static function recognize($userID){
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
            $return['data'] = array_key_exists('data', $response) ? $response['data'] : null;
            return $return;
        }
        $userMessage = current($response['data']);
        $userChatRecord = new UserChatRecord();
        $userChatRecord->userID = $userID;
        $userChatRecord->message = $userMessage;
        $userChatRecord->messageAudio = $messageAudio;
        $userChatRecord->config = json_encode(\Yii::$app->params['defaultChatConfig']);
        if(!$userChatRecord->save()){
            $return['msg'] = '记录聊天信息出错';
            return $return;
        }
        $return = ['status'=>1, 'msg'=>'识别成功', 'data'=>['chatRecord'=>UserChatRecord::combineCHatRecord($userChatRecord->attributes)]];
        return $return;
    }

    /**
     * 聊天
     *
     * @param $userID
     * @param $charRecordID
     * @return array
     */
    public static function chat($userID, $charRecordID){
        $return = ['status'=>0, 'msg'=>UserMsg::$timeOut];
        $userChatRecord = UserChatRecord::get($charRecordID, true);
        if(!$userChatRecord || $userChatRecord['userID'] != $userID){
            return $return;
        }
        $tuLingParams = \Yii::$app->params['tuLing'];
        $tuLing = new TuLing($tuLingParams['apiKey']);
        $location = IP::ip2addr(gethostbyname(gethostname()), true, '');
        $response = $tuLing->chat($userChatRecord['message'], $userID, $location);
        if(!$response['text']){
            $return['msg'] = '没有回复文本消息';
            return $return;
        }
        $reply = $response['text'];
        $baiduSpeechParams = \Yii::$app->params['baiduSpeech'];
        $baiduSpeech = new BaiduSpeech($baiduSpeechParams['appID'], $baiduSpeechParams['apiKey'], $baiduSpeechParams['secretKey']);
        $response = $baiduSpeech->combine(\Yii::$app->basePath.'/../storage/combine', $reply, $userID);
        if(!$response['success']){
            $return['msg'] = '合成语音文件失败，失败原因：'.$response['msg'];
            return $return;
        }
        $replyAudio = str_replace(\Yii::$app->basePath.'/..',"", $response['data']);
        $userChatRecord->reply = $reply;
        $userChatRecord->replyAudio = $replyAudio;
        if(!$userChatRecord->save()){
            $return['msg'] = '记录文件失败';
            return $return;
        }
        $return = ['status'=>1, 'msg'=>'操作成功', 'data'=>['chatRecord'=>UserChatRecord::combineCHatRecord($userChatRecord->attributes)]];
        return $return;
    }
}
