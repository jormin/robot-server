<?php

namespace common\models\dao;

use Yii;

/**
 * This is the model class for table "robot_user_config".
 *
 * @property int $id 主键ID
 * @property int $userID 用户ID
 * @property string $config 配置信息
 * @property int $createTime 创建时间
 * @property int $updateTime 更新时间
 *
 * @property User $user
 */
class UserConfig extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'robot_user_config';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userID', 'config'], 'required'],
            [['userID', 'createTime', 'updateTime'], 'integer'],
            [['config'], 'string'],
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
            'config' => '配置信息',
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
}
