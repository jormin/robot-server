<?php
namespace console\controllers;
use common\models\service\UserService;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;
use FFMpeg\Format\Video\WMV;
use Jormin\BaiduSpeech\BaiduSpeech;
use Jormin\IP\IP;
use Jormin\TuLing\TuLing;

/**
 * Class TestController
 * @package console\controllers
 */
class TestController extends BaseController
{

    /**
     * 测试
     */
    public function actionIndex(){

    }

    /**
     * 聊天测试
     *
     * @param $userID
     * @param $charRecordID
     */
    public function actionChat($userID, $charRecordID){
        $response = UserService::chat($userID, $charRecordID);
        cp($response);
    }

    public function actionConvert()
    {
//        $file = '/home/vagrant/code/1515312606.m4a';
//        $outFile = '/home/vagrant/code/1515314199.wav';
//        $ffmpeg = FFMpeg::create();
//        $audio = $ffmpeg->open($file);
//        $audio->save(new Wav(), $outFile);
//        $outFile = '/data/wwwroot/robot/storage/upload/2018/01/07/1515312606.wav';
        $outFiles = ['/home/vagrant/code/1515314324.wav', '/data/wwwroot/robot/storage/upload/2018/01/07/1515314324.wav', '/data/wwwroot/robot/storage/upload/2018/01/07/8k.wav', '/data/wwwroot/robot/storage/upload/2018/01/07/16k.wav'];
        foreach ($outFiles as $outFile){
            $this->log('识别语音文件:'.$outFile);
            $baiduSpeechParams = \Yii::$app->params['baiduSpeech'];
            $baiduSpeech = new BaiduSpeech($baiduSpeechParams['appID'], $baiduSpeechParams['apiKey'], $baiduSpeechParams['secretKey']);
            $response = $baiduSpeech->recognize($outFile, null, null, 1);
            var_dump($response);
            if(!$response['success']){
                return null;
            }
            $userMessage = current($response['data']);
            $tuLingParams = \Yii::$app->params['tuLing'];
            $tuLing = new TuLing($tuLingParams['apiKey']);
            $location = IP::ip2addr(gethostbyname(gethostname()), true, '');
            $response = $tuLing->chat($userMessage, 1, $location);
            var_dump($response);
            if(!$response['text']){
                return null;
            }
            $reply = $response['text'];
            $response = $baiduSpeech->combine(\Yii::$app->basePath.'/../storage/combine/', $reply, 1);
            var_dump($response);
            if(!$response['success']){
                return null;
            }
        }
    }

}