<?php

namespace common\models\dao;

/**
 * This is the model class for table "rad_user_token".
 *
 * @property integer $id
 * @property integer $userID
 * @property string $token
 * @property integer $status
 * @property integer $createTime
 * @property integer $expireTime
 */
class UserToken extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'robot_user_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userID'], 'required'],
            [['userID', 'createTime', 'expireTime', 'status'], 'integer'],
            [['token'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键id',
            'userID' => '用户id',
            'token' => 'token',
            'status' => 'Token状态',
            'createTime' => '创建时间',
            'expireTime' => '过期时间',
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
                $this->createTime = time();
                $this->expireTime = $this->createTime + 3600;
                $this->status = 1;
            }
            return true;
        }else{
            return false;
        }
    }
}
