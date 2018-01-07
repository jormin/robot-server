<?php
namespace console\controllers;
use FFMpeg\FFMpeg;
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
     * @param $text
     */
    public function actionChat($text){
        $tuLingParams = \Yii::$app->params['tuLing'];
        $tuLing = new TuLing($tuLingParams['apiKey']);
        $location = IP::ip2addr(gethostbyname(gethostname()), true, '');
        $response = $tuLing->chat($text, 1, $location);
        if(!$response['text']){
            $this->log('没有回复文本消息');
            return;
        }
        $baiduSpeechParams = \Yii::$app->params['baiduSpeech'];
        $baiduSpeech = new BaiduSpeech($baiduSpeechParams['appID'], $baiduSpeechParams['apiKey'], $baiduSpeechParams['secretKey']);
        $response = $baiduSpeech->combine(\Yii::$app->basePath.'/../storage/combine/', $response['text'], 1);
        if(!$response['success']){
            $this->log('合成语音文件失败，失败原因：'.$response['msg']);
            return;
        }
        $this->log('合成语音文件成功，文件目录：'.$response['data']);
        $this->log('开始播放：');
        exec('sudo play '.$response['data']);
    }

    public function actionConvert()
    {
        $file = '/home/vagrant/code/1515264154.mp3';
//        $outFile = '/home/vagrant/code/1515264154.wav';
//        $ffmpeg = FFMpeg::create();
//        $audio = $ffmpeg->open($file);
//        $audio->save(new WMV(), $outFile);
        $baiduSpeechParams = \Yii::$app->params['baiduSpeech'];
        $baiduSpeech = new BaiduSpeech($baiduSpeechParams['appID'], $baiduSpeechParams['apiKey'], $baiduSpeechParams['secretKey']);
        $response = $baiduSpeech->recognize($file, null, null, 1);
        var_dump($response);
    }

}