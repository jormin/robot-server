<?php

namespace common\models\dao;

use common\models\lib\Cache;
use Yii;

/**
 * This is the model class for table "robot_user_chat_record".
 *
 * @property int $id 主键ID
 * @property int $userID 用户ID
 * @property string $message 消息内容
 * @property string $reply 回复内容
 * @property string $messageAudio 语音文件
 * @property string $replyAudio
 * @property string $config 配置
 * @property int $createTime 创建时间
 * @property int $updateTime 更新时间
 *
 * @property User $user
 */
class UserChatRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'robot_user_chat_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['message', 'messageAudio', 'config'], 'required'],
            [['userID', 'createTime', 'updateTime'], 'integer'],
            [['message', 'reply', 'messageAudio', 'replyAudio', 'config'], 'string', 'max' => 255],
            [['userID'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userID' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'userID' => '用户ID',
            'message' => '消息内容',
            'reply' => '回复内容',
            'messageAudio' => '语音文件',
            'replyAudio' => 'Reply Audio',
            'config' => '配置',
            'createTime' => '创建时间',
            'updateTime' => '更新时间',
        ];
    }

    /**
     * 保存前预处理
     *
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if(parent::beforeSave($insert)){
            if($this->isNewRecord){
                $this->createTime = $this->updateTime = time();
            }else{
                $this->updateTime = time();
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * 根据ID查找
     *
     * @param $id
     * @param bool $isModel
     * @return array|null|\common\models\dao\UserChatRecord
     */
    public static function get($id, $isModel=false){
        if($isModel){
            return self::find()->where(['id'=>$id])->one();
        }else{
            $cacheName = 'USER_CHAT_RECORD_'.$id;
            $cache = Cache::get($cacheName);
            if($cache === false){
                $cache = self::find()->where(['id'=>$id])->asArray()->one();
                Cache::set($cacheName, $cache);
            }
            return $cache;
        }
    }

    /**
     * 保存后清理缓存
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        Cache::clear('USER_CHAT_RECORD_'.$this->id);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 组合聊天记录信息
     *
     * @param $chatRecord
     * @return mixed
     */
    public static function combineCHatRecord($chatRecord){
        if(!$chatRecord){
            return $chatRecord;
        }
        $chatRecord['messageAudio'] && $chatRecord['messageAudio'] = \Yii::$app->params['attachmentDomain'].'/'.$chatRecord['messageAudio'];
        $chatRecord['replyAudio'] && $chatRecord['replyAudio'] = \Yii::$app->params['attachmentDomain'].'/'.$chatRecord['replyAudio'];
        return $chatRecord;
    }
}
