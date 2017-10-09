<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class Users extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%users}}';
    }

    
}
