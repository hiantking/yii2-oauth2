<?php
namespace sweelix\oauth2\server\models\ar;

/**
 * clients
 *
 * @author dejin <ldj@hianto2o.com>
 */
class Clients extends ActiveRecord
{	
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_clients}}';
    }

    /**
     * 获取Client Model
     * 
     * @return sweelix\oauth2\server\interfaces 
     */
    public function getClientData(){
    	return array(
            'id'=>$this->client_id,
            'secret'=>$this->secret,
            'name'=>$this->name,
            'redirectUri'=>$this->redirect_uri,
            'grantTypes'=>$this->grant_types,
            'scopes'=>$this->scopes,
            'userId'=>$this->user_id,
            'isPublic'=>$this->is_public,
        );
    }
}
