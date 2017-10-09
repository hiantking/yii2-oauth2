<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class Jwt extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_jwt}}';
    }

    public function getJwtData()
    {
        return [
            'id' => $this->client_id,
            'clientId' => $this->client_id,
            'subject' => $this->subject,
            'publicKey' => $this->public_key,
        ];
    }
}
