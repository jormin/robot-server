<?php
namespace api\controllers;
use common\models\dao\User;
use common\models\dao\UserToken;
use common\models\lib\UserMsg;

/**
 * Class AuthController
 * @package common\controllers
 */
class AuthController extends BaseController
{
    protected $userID, $userInfo;

    /**
     * 预处理
     *
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if(\Yii::$app->controller->id == 'attachment' && \Yii::$app->controller->action->id == 'download'){
            return true;
        }
        parent::beforeAction($action);
        if(!$this->userToken){
            $this->authFail();
        }
        $userToken = UserToken::findOne(['token'=>$this->userToken]);
        if(!$userToken){
            $this->authFail();
        }
        $currentTime = time();
        if($userToken->status != 1 || $currentTime > $userToken->expireTime){
            $this->authFail(UserMsg::$tokenExpire);
        }
        // 当Token有效期剩余最后10分钟时自动延长
        if($userToken->expireTime - $currentTime < 600){
            $userToken->expireTime += 3600;
            if(!$userToken->save()){
                $this->fail(UserMsg::$extendUserTokenError);
            }
        }
        $user = User::find()->where(['id'=>$userToken['userID']])->asArray()->one();
        if(!$user){
            $this->authFail();
        }
        $this->userID = $user['id'];
        $this->userInfo = $user;
        return true;
    }
}