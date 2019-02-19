<?php

namespace api\controllers;

use common\models\service\UserService;

class ChatController extends BaseController
{

    /**
     * 语音识别
     */
    public function actionRecognize(){
        $return = UserService::recognize($this->userID);
        $this->autoResult($return);
    }

    /**
     * 聊天
     * @throws \Exception
     */
    public function actionChat(){
        $chatRecordID = $this->getParam('chatRecordID');
        $return = UserService::chat($this->userID, $chatRecordID);
        $this->autoResult($return);
    }
}
