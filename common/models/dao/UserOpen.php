<?php

namespace common\models\dao;

use common\models\lib\Cache;
use Yii;

/**
 * This is the model class for table "robot_user_open".
 *
 * @property int $id 用户ID
 * @property int $userID 用户ID
 * @property string $openID 微信OpenID
 * @property int $createTime 创建时间
 * @property int $updateTime 更新时间
 *
 * @property User $user
 */
class UserOpen extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'robot_user_open';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userID', 'openID'], 'required'],
            [['userID', 'createTime', 'updateTime'], 'integer'],
            [['openID'], 'string', 'max' => 100],
            [['userID'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userID' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '用户ID',
            'userID' => '用户ID',
            'openID' => '微信OpenID',
            'createTime' => '创建时间',
            'updateTime' => '更新时间',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userID']);
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
     * 保存后清理缓存
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        Cache::clear('USER_OPEN_OPENID_'.$this->openID);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 根据OPENID查询记录
     *
     * @param $openID
     * @param bool $isModel
     * @return array|mixed|null|\yii\db\ActiveRecord
     */
    public static function getByOpenID($openID, $isModel=false){
        if($isModel){
            return self::find()->where(['openID'=>$openID])->one();
        }else{
            $cacheName = 'USER_OPEN_OPENID_'.$openID;
            $cache = Cache::get($cacheName);
            if($cache === false){
                $cache = self::find()->where(['openID'=>$openID])->asArray()->one();
                Cache::set($cacheName, $cache);
            }
            return $cache;
        }
    }
}
