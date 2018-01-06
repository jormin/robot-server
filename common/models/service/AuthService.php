<?php
namespace common\models\service;

use common\models\dao\User;
use common\models\dao\UserConfig;
use common\models\dao\UserLoginLog;
use common\models\dao\UserOpen;
use common\models\dao\UserToken;
use common\models\lib\CommonFunction;
use common\models\lib\CommonVar;
use common\models\lib\UserMsg;
use Jormin\IP\IP;

/**
 * Class AuthService
 * @package common\models\service
 */
class AuthService {

    /**
     * 登录
     *
     * @param $code
     * @return array
     */
    public static function login($code){
        $return = ['status'=>0, 'msg'=>UserMsg::$timeOut];
        $config = \Yii::$app->params['wechat']['xcx'];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$config['appID'].'&secret='.$config['appSecret'].'&js_code='.$code.'&grant_type=authorization_code';
        $response = CommonFunction::http('get', $url, true);
        if(array_key_exists('errcode', $response)){
            $return['msg'] = '微信登录失败，失败原因编码：'.$response['errcode'].'，失败说明：'.$response['errmsg'];
            return $return;
        }
        $transaction = \Yii::$app->db->beginTransaction();
        $openID = $response['openid'];
        $sessionKey = $response['session_key'];
        $unionID = $response['unionid'];
        $user = User::getByUnionID($unionID, true);
        $newUser = false;
        if(!$user){
            $user = new User();
            $user->unionID = $unionID;
            $newUser = true;
        }
        $user->sessionKey = $sessionKey;
        if(!$user->save()){
            $transaction->rollBack();
            $return['msg'] = '保存用户信息出错';
            return $return;
        }
        $userID = $user['id'];
        if($newUser){
            $userConfig = new UserConfig();
            $userConfig->userID = $userID;
            $userConfig->config = json_encode(\Yii::$app->params['defaultChatConfig']);
            if(!$user->save()){
                $transaction->rollBack();
                $return['msg'] = '保存用户配置出错';
                return $return;
            }
        }
        $userOpen = UserOpen::getByOpenID($openID);
        if($userOpen && $userOpen['userID'] != $userID){
            $return['msg'] = '该OPENID已绑定别的用户';
            return $return;
        }
        if(!$userOpen){
            $userOpen = new UserOpen();
            $userOpen->userID = $userID;
            $userOpen->openID = $openID;
            if(!$userOpen->save()){
                $transaction->rollBack();
                $return['msg'] = '保存用户OPENID信息出错';
                return $return;
            }
        }
        $token = md5($userID.CommonVar::$encrypt.$user['unionID'].time());
        UserToken::updateAll(['status'=>0], ['userID'=>$userID]);
        $userToken = new UserToken();
        $userToken->userID = $userID;
        $userToken->token = $token;
        if(!$userToken->save()){
            $return['msg'] = UserMsg::$recordUserTokenError;
            $transaction->rollBack();
            return $return;
        }
        $transaction->commit();
        $data = [
            'openID' => $openID,
            'unionID' => $unionID,
            'sessionKey' => $sessionKey,
        ];
        $return = ['status'=>1, 'msg'=>UserMsg::$success, 'data'=>$data];
        return $return;
    }
}