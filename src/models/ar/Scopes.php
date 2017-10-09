<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class Scopes extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_scopes}}';
    }

    public function getScopeData()
    {
        return [
            'id' => $this->scope,
            'isDefault' => $this->is_default,
            'definition' => $this->definition,
        ];
    }
}
