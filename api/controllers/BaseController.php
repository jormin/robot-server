<?php
namespace common\controllers;

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

    protected $params, $userToken;

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
        if(!\Yii::$app->request->isPost){
            $this->error();
        }
        $post = $this->paramTrim(\Yii::$app->request->post());
        if(!empty($post['token'])){
            $this->userToken = $post['token'];
            unset($post['token']);
        }
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
    protected function success($data, $msg=null){
        !$msg && $msg=UserMsg::$success;
        $data && $data = $this->dealTime($data);
        $response = ['status'=>1, 'data'=>$data, 'msg'=>$msg];
        $this->result($response);
    }

    /**
     * 操作失败
     *
     * @param string $msg
     */
    protected function fail($msg){
        !$msg && $msg=UserMsg::$fail;
        $response = ['status'=>0, 'msg'=>$msg];
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
        if($return['status'] == 1){
            $this->success(array_key_exists('data', $return) ? $return['data'] : null, $return['msg']);
        }else{
            $this->fail($return['msg']);
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

    /**
     * 处理时间
     *
     * @param $data
     * @return mixed
     */
    protected function dealTime($data){
        $timeArr = ['createTime', 'updateTime', 'dealTime', 'confirmTime', 'findTime', 'completeTime', 'retractTime', 'delayTime', 'originTime', 'signTime', 'loginTime', 'expireTime', 'feedbackTime'];
        foreach ($data as $key => $item){
            if(is_array($item)){
                $data[$key] = $this->dealTime($item);
            }else{
                if(in_array($key, $timeArr)){
                    $item && $data[$key] = date('Y-m-d', $item);
                }
            }
        }
        return $data;
    }
}