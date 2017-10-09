<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class AuthorizationCodes extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_authorization_codes}}';
    }

    public function getScopeData()
    {
        return [
            'id' => $this->authorization_code,
            'clientId' => $this->client_id,
            'userId' => $this->user_id,
            'redirectUri' => $this->redirect_uri,
            'expiry' => $this->expires,
            'scopes' => $this->scope,
            'tokenId' => $this->token_id,
        ];
    }
}
