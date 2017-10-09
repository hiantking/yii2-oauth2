<?php
/**
 * AccessTokenService.php
 *
 * PHP version 5.6+
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 */

namespace sweelix\oauth2\server\services\db;

use sweelix\oauth2\server\models\ar\AccessTokens;
use sweelix\oauth2\server\models\ar\Clients;
use sweelix\oauth2\server\exceptions\DuplicateIndexException;
use sweelix\oauth2\server\exceptions\DuplicateKeyException;
use sweelix\oauth2\server\interfaces\AccessTokenModelInterface;
use sweelix\oauth2\server\interfaces\AccessTokenServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;

/**
 * This is the access token service for db
 *  database structure
 *    * oauth2:accessTokens:<aid> : hash (AccessToken)
 *    * oauth2:users:<uid>:accessTokens : set (AccessToken for user)
 *    * oauth2:clients:<cid>:accessTokens : set (AccessToken for client)
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class AccessTokenService extends BaseService implements AccessTokenServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(AccessTokenModelInterface $accessToken, $attributes)
    {
        if ($accessToken->getIsNewRecord()) {
            $result = $this->insert($accessToken, $attributes);
        } else {
            $result = $this->update($accessToken, $attributes);
        }
        return $result;
    }

    /**
     * Save Access Token
     * @param AccessTokenModelInterface $accessToken
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(AccessTokenModelInterface $accessToken, $attributes)
    {
        $result = false;
        if (!$accessToken->beforeSave(true)) {
            return $result;
        }
        $accessTokenId = $accessToken->getKey();

        //check if record exists
        $accessTokenAr = AccessToken::findOne($accessTokenId);
        if ($accessTokenAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$accessTokenId.'"');
        }
        $accessTokenAr = new AccessTokens;
        $this->loadAccessTokenAr($accessTokenAr, $accessToken, $attributes);
        try {
            $accessTokenAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $accessTokenAr->getAccessTokenData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $accessToken->setOldAttributes($values);
        $accessToken->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update Access Token
     * @param AccessTokenModelInterface $accessToken
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(AccessTokenModelInterface $accessToken, $attributes)
    {
        if (!$accessToken->beforeSave(false)) {
            return false;
        }
        $modelKey = $accessToken->key();
        $accessTokenId = isset($values[$modelKey]) ? $values[$modelKey] : $accessToken->getKey();

        $accessTokenAr = AccessTokens::findOne($accessTokenId);        
        try {
            $this->loadAccessTokenAr($accessTokenAr, $accessToken, $attributes);
            $accessTokenAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $accessTokenAr->getAccessTokenData();
        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $accessToken->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $accessToken->setOldAttribute($name, $value);
        }
        $accessToken->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;

        $accessTokenAr = AccessTokens::findOne($key);     
        if ($accessTokenAr !== null) {            
            $record = $this->toAccessTokenModel($accessTokenAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function findAllByUserId($userId)
    {
        $accessTokens = [];
        $clientArList = Clients::find()->where(['user_id'=>$userId])-asArray()->all();
        $clientIds = array_column($clientArList,'client_id');
        $accessTokenArList = AccessTokens::find()->where(['in','client_id',$clientIds])->all();  
        foreach($accessTokenArList as $accessTokenAr) {
            $accessTokens[] = $this->toAccessTokenModel($accessTokenAr);
        }
        return $accessTokens;
    }

    /**
     * @inheritdoc
     */
    public function deleteAllByUserId($userId)
    {
        $clientArList = Clients::find()->where(['user_id'=>$userId])-asArray()->all();
        $clientIds = array_column($clientArList,'client_id');
        AccessTokens::deleteAll(['in','client_id',$clientIds]);  
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findAllByClientId($clientId)
    {
        $accessTokenArList = AccessTokens::find()->where(['client_id'=>$clientId])->all();  
        foreach($accessTokenArList as $accessTokenAr) {
            $accessTokens[] = $this->toAccessTokenModel($accessTokenAr);
        }
        return $accessTokens;
    }

    /**
     * @inheritdoc
     */
    public function deleteAllByClientId($clientId)
    {
        AccessTokens::deleteAll(['client_id'=>$clientId]);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function delete(AccessTokenModelInterface $accessToken)
    {
        $result = false;
        if ($accessToken->beforeDelete()) {
            
            $id = $accessToken->getOldKey();
            AccessTokens::deleteAll(['client_id'=>$clientId]);
            $accessToken->setIsNewRecord(true);
            $accessToken->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toAccessTokenModel($accessTokenAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\AccessTokenModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);        
        $accessTokenData = $accessTokenAr->getClientData();
        $attributes = [];
        foreach ($accessTokenData as $key=>$value) {
            if (isset($properties[$key]) === true) {
                $value = $this->convertToModel($key, $value);
                $record->setAttribute($key, $value);
                $attributes[$key] = $value;
            // @codeCoverageIgnoreStart
            } elseif ($record->canSetProperty($key)) {
                // TODO: find a way to test attribute population
                $record->{$key} = $value;
            }
            // @codeCoverageIgnoreEnd
        }
        if (empty($attributes) === false) {
            $record->setOldAttributes($attributes);
        }
        return $record;
    }

    public function loadAccessTokenAr($accessTokenAr, $accessToken, $names){
        $values = $client->getDirtyAttributes($names);        
        $values['clientId'] = $values['id'];
        $values['userId'] = $values['userId']?:0;
        unset($values['id']);
        $this->setAttributesDefinitions($client->attributesDefinition());
        foreach ($values as $key => $value)
        {
            if (($key === 'expiry') && ($value > 0)) {
                $expire = $value;
            }
            if ($value !== null) {
                $attributeName = Inflector::underscore($key);
                $accessTokenAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $accessTokenAr;
    }

}
