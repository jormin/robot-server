<?php

namespace api\controllers;

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
        $encryptedData = $this->getParam('encryptedData');
        $iv = $this->getParam('iv');
        $return = AuthService::login($code, $encryptedData, $iv);
        $this->autoResult($return);
    }
}
