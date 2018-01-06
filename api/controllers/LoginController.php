<?php

namespace ucenter\controllers;

use common\controllers\BaseController;
use common\models\service\AuthService;

/**
 * Class LoginController
 *
 * @package App\Http\Controllers\Api
 */
class LoginController extends BaseController
{
    /**
     * 登录
     */
    public function actionIndex(){
        $code = $this->getParam('code');
        $return = AuthService::login($code);
        $this->autoResult($return);
    }
}
