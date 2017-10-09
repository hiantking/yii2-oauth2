<?php
/**
 * RefreshTokenService.php
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

use sweelix\oauth2\server\models\ar\RefreshTokens;
use sweelix\oauth2\server\exceptions\DuplicateIndexException;
use sweelix\oauth2\server\exceptions\DuplicateKeyException;
use sweelix\oauth2\server\interfaces\RefreshTokenModelInterface;
use sweelix\oauth2\server\interfaces\RefreshTokenServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;

/**
 * This is the refresh token service for db
 *  database structure
 *    * oauth2:refreshTokens:<rid> : hash (RefreshToken)
 *    * oauth2:users:<uid>:refreshTokens : set (RefreshTokens for user)
 *    * oauth2:clients:<cid>:refreshTokens : set (RefreshTokens for client)
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class RefreshTokenService extends BaseService implements RefreshTokenServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(RefreshTokenModelInterface $refreshToken, $attributes)
    {
        if ($refreshToken->getIsNewRecord()) {
            $result = $this->insert($refreshToken, $attributes);
        } else {
            $result = $this->update($refreshToken, $attributes);
        }
        return $result;
    }

    /**
     * Save Refresh Token
     * @param RefreshTokenModelInterface $refreshToken
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(RefreshTokenModelInterface $refreshToken, $attributes)
    {
        $result = false;
        if (!$refreshToken->beforeSave(true)) {
            return $result;
        }
        $refreshTokenId = $refreshToken->getKey();
        //check if record exists
        $refreshTokenAr = RefreshToken::findOne($refreshTokenId);
        if ($refreshTokenAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$refreshTokenId.'"');
        }
        $refreshTokenAr = new RefreshTokens;

        try {
            $refreshTokenAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $refreshTokenAr->getRefreshTokenData();        
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $refreshToken->setOldAttributes($values);
        $refreshToken->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update Refresh Token
     * @param RefreshTokenModelInterface $refreshToken
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(RefreshTokenModelInterface $refreshToken, $attributes)
    {
        if (!$refreshToken->beforeSave(false)) {
            return false;
        }

        $modelKey = $refreshToken->key();
        $refreshTokenId = isset($values[$modelKey]) ? $values[$modelKey] : $refreshToken->getKey();
        $refreshTokenAr = RefreshTokens::findOne($refreshTokenId);        
        try {
            $this->loadRefreshTokenAr($refreshTokenAr, $refreshToken, $attributes);
            $refreshTokenAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $refreshTokenAr->getRefreshTokenData();

        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $refreshToken->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $refreshToken->setOldAttribute($name, $value);
        }
        $refreshToken->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;
        $refreshTokenAr = RefreshTokens::findOne($key);     
        if ($refreshTokenAr !== null) {            
            $record = $this->toRefreshTokenModel($refreshTokenAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function findAllByUserId($userId)
    {
        $refreshTokens = [];
        $clientArList = Clients::find()->where(['user_id'=>$userId])-asArray()->all();
        $clientIds = array_column($clientArList,'client_id');
        $refreshTokenArList = RefreshTokens::find()->where(['in','client_id',$clientIds])->all();  
        foreach($refreshTokenArList as $refreshTokenAr) {
            $refreshTokens[] = $this->toRefreshTokenModel($refreshTokenAr);
        }
        return $refreshTokens;
    }

    /**
     * @inheritdoc
     */
    public function deleteAllByUserId($userId)
    {
        $clientArList = Clients::find()->where(['user_id'=>$userId])-asArray()->all();
        $clientIds = array_column($clientArList,'client_id');
        RefreshTokens::deleteAll(['in','client_id',$clientIds]);  
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findAllByClientId($clientId)
    {
        $refreshTokenArList = RefreshTokens::find()->where(['client_id'=>$clientId])->all();  
        foreach($refreshTokenArList as $refreshTokenAr) {
            $refreshTokens[] = $this->toRefreshTokenModel($refreshTokenAr);
        }
        return $refreshTokens;
    }

    /**
     * @inheritdoc
     */
    public function deleteAllByClientId($clientId)
    {
        RefreshTokens::deleteAll(['client_id'=>$clientId]);
        return true;
    }


    /**
     * @inheritdoc
     */
    public function delete(RefreshTokenModelInterface $refreshToken)
    {
        $result = false;
        if ($refreshToken->beforeDelete()) {
            $id = $refreshToken->getOldKey();
            RefreshTokens::deleteAll(['client_id'=>$clientId]);
            $refreshToken->setIsNewRecord(true);
            $refreshToken->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toRefreshTokenModel($refreshTokenAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\RefreshTokenModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);        
        $refreshTokenData = $refreshTokenAr->getClientData();
        $attributes = [];
        foreach ($refreshTokenData as $key=>$value) {
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

    public function loadRefreshTokenAr($refreshTokenAr, $refreshToken, $names){
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
                $refreshTokenAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $refreshTokenAr;
    }

}
