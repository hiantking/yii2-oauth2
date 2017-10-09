<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class AccessTokens extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_access_tokens}}';
    }

    /**
     * 获取AccessTokens Model
     * 
     * @return sweelix\oauth2\server\interfaces 
     */
    public function getAccessTokenData(){
    	return [
            'id' => $this->access_token,
            'clientId' => $this->client_id,
            'userId' => $this->user_id,
            'expiry' => $this->expiry,
            'scopes' => $this->scopes
        ];
    }

    
}
