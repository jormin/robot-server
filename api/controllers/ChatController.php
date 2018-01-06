<?php

namespace api\controllers;

use common\models\service\UserService;

class ChatController extends BaseController
{

    /**
     * 聊天
     */
    public function actionIndex(){
        $return = UserService::chat($this->userID);
        $this->autoResult($return);
    }
}
