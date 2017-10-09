<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class RefreshTokens extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_refresh_tokens}}';
    }

    public function getRefreshTokenData()
    {
        return [
            'id' => $this->refresh_token,
            'clientId' => $this->client_id,
            'userId' => $this->user_id,
            'expiry' => $this->expiry,
            'scopes' => $this->scopes,
        ];
    }
    
}
