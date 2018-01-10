<?php

namespace common\models\service;

use common\models\dao\UserChatRecord;
use common\models\lib\UserMsg;
use Jormin\BaiduSpeech\BaiduSpeech;
use Jormin\IP\IP;
use Jormin\TuLing\TuLing;

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
        if(!$userChatRecord){
            return $return;
        }
        $tuLingParams = \Yii::$app->params['tuLing'];
        $tuLing = new TuLing($tuLingParams['apiKey']);
        $location = IP::ip2addr(gethostbyname(gethostname()), true, '');
        $response = $tuLing->chat($userChatRecord['message'], $userID, $location);
        $replyCode = $response['code'];
        if(in_array($replyCode, [4001, 4002, 4004, 4007])){
            $return['msg'] = '聊天异常,原因:'.$response['text'];
            return $return;
        }
        if(!$response['text']){
            $return['msg'] = '没有回复文本消息';
            return $return;
        }
        $reply = $response['text'];
        $originData = $response;
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
        $userChatRecord->replyCode = $replyCode;
        $userChatRecord->originData = json_encode($originData);
        if(!$userChatRecord->save()){
            p($userChatRecord->errors);
            $return['msg'] = '记录文件失败';
            return $return;
        }
        $userChatRecord->originData = json_decode($userChatRecord->originData, true);
        $return = ['status'=>1, 'msg'=>'操作成功', 'data'=>['chatRecord'=>UserChatRecord::combineCHatRecord($userChatRecord->attributes)]];
        return $return;
    }
}
