<?php
namespace api\controllers;

use common\models\dao\User;
use common\models\dao\UserToken;
use common\models\lib\UserMsg;
use common\models\lib\Validate;
use yii\web\Controller;

/**
 * Class BaseController
 * @package common\controllers
 */
class BaseController extends Controller
{

    public $enableCsrfValidation = false;

    protected $params, $userID, $userInfo;

    /**
     * 预处理
     *
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)){
            return false;
        }
        if(\Yii::$app->request->isGet){
            $params = \Yii::$app->request->get();
        }else{
            $params = \Yii::$app->request->post();
        }
        $params = $this->paramTrim($params);
        if(!empty($params['token'])){
            $userToken = UserToken::findOne(['token'=>$params['token']]);
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
            unset($params['token']);
        }
        $this->params = $params;
        return true;
    }

    /**
     * 请求错误
     */
    protected function error(){
        die(json_encode(['status'=>0, 'msg'=>UserMsg::$timeOut])) ;
    }

    /**
     * 返回请求结果
     *
     * @param $data
     */
    protected function result($data){
        die(json_encode($data)) ;
    }

    /**
     * 操作成功
     *
     * @param $data
     * @param string $msg
     */
    protected function success($msg, $data=null){
        !$msg && $msg=UserMsg::$success;
        $response = ['status'=>1, 'data'=>$data, 'msg'=>$msg];
        $this->result($response);
    }

    /**
     * 操作失败
     *
     * @param $msg
     * @param null $data
     */
    protected function fail($msg, $data=null){
        !$msg && $msg=UserMsg::$fail;
        $response = ['status'=>0, 'msg'=>$msg, 'data'=>$data];
        $this->result($response);
    }

    /**
     * 需要登录
     *
     * @param $msg
     */
    protected function authFail($msg=null){
        !$msg && $msg=UserMsg::$userNotLogin;
        $response = ['status'=>-1, 'msg'=>$msg];
        $this->result($response);
    }

    /**
     * 针对Service函数返回格式自动处理
     *
     * @param $return
     */
    protected function autoResult($return){
        $data = array_key_exists('data', $return) ? $return['data'] : null;
        if($return['status'] == 1){
            $this->success($return['msg'], $data);
        }else{
            $this->fail($return['msg'], $data);
        }
    }

    /**
     * Trim参数值
     *
     * @param $param
     * @return array|string
     */
    private function paramTrim($param){
        if (empty($param) || !is_array($param))
            return trim($param);
        $result = [];
        foreach ($param as $k=>$v){
            $trim = trim($v);
            isset($trim) && $result[$k] = $trim;
        }
        return $result;
    }

    /**
     * 获取请求参数
     *
     * @param $key
     * @return null
     */
    protected function getParam($key){
        $value = array_key_exists($key, $this->params) ? $this->params[$key] : null;
        if($key == 'page' && !is_null($value)){
            $value -= 1;
        }
        return $value;
    }
}