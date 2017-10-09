<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use sweelix\oauth2\server\Module;

/**
 * Oauth2 ActiveRecord基础类
 *
 * @author dejin <ldj@hianto2o.com>
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function getDb()
    {
        $dbName = Module::getInstance()->db;
        return Yii::$app->$dbName;
    }
}