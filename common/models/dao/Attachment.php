<?php

namespace common\models\dao;

use common\models\lib\Cache;

/**
 * This is the model class for table "rad_attachment".
 *
 * @property integer $id
 * @property integer $userID
 * @property string $name
 * @property string $path
 * @property integer $type
 * @property integer $createTime
 *
 * @property User $user
 */
class Attachment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'robot_attachment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userID', 'name', 'path'], 'required'],
            [['userID', 'type', 'createTime'], 'integer'],
            [['name', 'path'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键ID',
            'userID' => '上传用户',
            'name' => '名称',
            'path' => '路径',
            'type' => '附件类型 0:上传 1:合成',
            'createTime' => '创建时间',
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
            }
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->resetCache();
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 重置缓存
     */
    public function resetCache(){
        Cache::clear('ATTACHMENT_'.$this->id);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userID']);
    }

    /**
     * 查找附件
     *
     * @param $id
     * @param bool $isModel
     * @return array|null|\common\models\dao\DangerProcess
     */
    public static function get($id, $isModel=false){
        if($isModel){
            return self::find()->where(['id'=>$id])->one();
        }else{
            $cacheName = 'ATTACHMENT_'.$id;
            $cache = Cache::get($cacheName);
            if($cache === false){
                $cache = self::find()->where(['id'=>$id])->asArray()->one();
                Cache::set($cacheName, $cache);
            }
            return $cache;
        }
    }
}
