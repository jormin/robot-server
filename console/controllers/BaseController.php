<?php
namespace console\controllers;

use yii\web\Controller;

/**
 * Class BaseController
 * @package console\controllers
 */
class BaseController extends Controller
{
    // 关闭CSRF
    public $enableCsrfValidation = false;
    // 所有操作
    public $actions = [
        'test' => [
            'index' => '测试'
        ],
        'user' => [
            'reset-password' => '重置用户密码',
            'reset-developer' => '重置开发者账号'
        ],
        'danger' => [
            'reset-password' => '重置用户密码',
            'generate-document' => '重新生成隐患电子档案'
        ]
    ];

    protected $controllerID, $actionID;

    /**
     * 操作前处理
     *
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)){
            return false;
        }
        $this->controllerID = $action->controller->id;
        $this->actionID = $action->id;
        if(!array_key_exists($this->controllerID, $this->actions) || !array_key_exists($this->actionID, $this->actions[$this->controllerID])){
            return true;
        }
        $this->log('----------------'.$this->actions[$action->controller->id][$action->id].'操作开始----------------');
        $this->log('');
        return true;
    }

    /**
     * 操作后处理
     *
     * @param $action
     * @param $result
     * @return mixed
     */
    public function afterAction($action, $result)
    {
        if(!array_key_exists($this->controllerID, $this->actions) || !array_key_exists($this->actionID, $this->actions[$this->controllerID])){
            return true;
        }
        $this->log('');
        $this->log('----------------'.$this->actions[$action->controller->id][$action->id].'操作结束----------------');
        return parent::afterAction($action, $result);
    }

    /**
     * 打印消息日志
     *
     * @param $msg
     */
    public function log($msg){
        if($msg == chr(10)){
            echo $msg;
        }else{
            echo '['.date('Y-m-d H:i:s').']'.$msg.chr(10);
        }
    }

}