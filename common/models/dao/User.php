<?php

namespace common\models\dao;

use common\models\lib\Cache;

/**
 * This is the model class for table "robot_user".
 *
 * @property int $id 用户ID
 * @property string $unionID 微信UnionID
 * @property string $nickName 昵称
 * @property string $avatarUrl 头像
 * @property int $gender 性别
 * @property string $country 国家
 * @property string $province 省份
 * @property string $city 城市
 * @property string $language 语言
 * @property string $sessionKey 用户当前登录用的微信SessionKey
 * @property int $createTime 创建时间
 * @property int $updateTime 更新时间
 *
 * @property Attachment[] $attachments
 * @property UserOpen[] $userOpens
 * @property UserConfig $userConfig
 */
class User extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'robot_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['gender', 'createTime', 'updateTime'], 'integer'],
            [['unionID'], 'string', 'max' => 100],
            [['nickName', 'avatarUrl', 'country', 'province', 'city', 'language', 'sessionKey'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '用户ID',
            'unionID' => '微信UnionID',
            'nickName' => '昵称',
            'avatarUrl' => '头像',
            'gender' => '性别',
            'country' => '国家',
            'province' => '省份',
            'city' => '城市',
            'language' => '语言',
            'sessionKey' => '用户当前登录用的微信SessionKey',
            'createTime' => '创建时间',
            'updateTime' => '更新时间',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAttachments()
    {
        return $this->hasMany(Attachment::className(), ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserOpens()
    {
        return $this->hasMany(UserOpen::className(), ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserConfig()
    {
        return $this->hasMany(UserConfig::className(), ['userID' => 'id']);
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
        Cache::clear('USER_'.$this->id);
        Cache::clear('USER_UNION_ID_'.$this->unionID);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 查找用户
     *
     * @param $id
     * @param bool $isModel
     * @return array|null|\common\models\dao\User
     */
    public static function get($id, $isModel=false){
        if($isModel){
            return self::find()->where(['id'=>$id])->one();
        }else{
            $cacheName = 'USER_'.$id;
            $cache = Cache::get($cacheName);
            if($cache === false){
                $cache = self::find()->where(['id'=>$id])->asArray()->one();
                Cache::set($cacheName, $cache);
            }
            return $cache;
        }
    }

    /**
     * 根据UnionID查找用户
     *
     * @param $unionID
     * @param bool $isModel
     * @return array|mixed|null|\yii\db\ActiveRecord
     */
    public static function getByUnionID($unionID, $isModel=false){
        if($isModel){
            return self::find()->where(['unionID'=>$unionID])->one();
        }else{
            $cacheName = 'USER_UNION_ID_'.$unionID;
            $cache = Cache::get($cacheName);
            if($cache === false){
                $cache = self::find()->where(['unionID'=>$unionID])->asArray()->one();
                Cache::set($cacheName, $cache);
            }
            return $cache;
        }
    }
}
