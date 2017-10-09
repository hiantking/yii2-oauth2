<?php
/**
 * JtiService.php
 *
 * PHP version 5.6+
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2017 Philippe Gaultier
 * @license http://www.sweelix.net/license license
 * @version 1.2.0
 * @link http://www.sweelix.net
 * @package sweelix\oauth2\server\services\redis
 */

namespace sweelix\oauth2\server\services\redis;

use sweelix\oauth2\server\exceptions\DuplicateIndexException;
use sweelix\oauth2\server\exceptions\DuplicateKeyException;
use sweelix\oauth2\server\interfaces\JtiModelInterface;
use sweelix\oauth2\server\interfaces\JtiServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;
use Exception;

/**
 * This is the jti service for redis
 *  database structure
 *    * oauth2:jti:<jid> : hash (Jti)
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2017 Philippe Gaultier
 * @license http://www.sweelix.net/license license
 * @version 1.2.0
 * @link http://www.sweelix.net
 * @package sweelix\oauth2\server\services\redis
 * @since 1.0.0
 */
class JtiService extends BaseService implements JtiServiceInterface
{

    /**
     * @param string $jid jti ID
     * @return string access token Key
     * @since 1.0.0
     */
    protected function getJtiKey($jid)
    {
        return $this->namespace . ':' . $jid;
    }

    /**
     * @inheritdoc
     */
    public function save(JtiModelInterface $jti, $attributes)
    {
        if ($jti->getIsNewRecord()) {
            $result = $this->insert($jti, $attributes);
        } else {
            $result = $this->update($jti, $attributes);
        }
        return $result;
    }

    /**
     * Save Jti
     * @param JtiModelInterface $jti
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(JtiModelInterface $jti, $attributes)
    {
        $result = false;
        if (!$accessToken->beforeSave(true)) {
            return $result;
        }
        $accessTokenId = $accessToken->getKey();

        //check if record exists
        $accessTokenAr = Jti::findOne($accessTokenId);
        if ($accessTokenAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$accessTokenId.'"');
        }
        $accessTokenAr = new Jti;
        $this->loadJtiAr($accessTokenAr, $accessToken, $attributes);
        try {
            $accessTokenAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $accessTokenAr->getJtiData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $accessToken->setOldAttributes($values);
        $accessToken->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update Jti
     * @param JtiModelInterface $jti
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(JtiModelInterface $jti, $attributes)
    {
        if (!$accessToken->beforeSave(false)) {
            return false;
        }
        $modelKey = $accessToken->key();
        $accessTokenId = isset($values[$modelKey]) ? $values[$modelKey] : $accessToken->getKey();

        $accessTokenAr = Jti::findOne($accessTokenId);        
        try {
            $this->loadJtiAr($accessTokenAr, $accessToken, $attributes);
            $accessTokenAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $accessTokenAr->getJtiData();
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

        $accessTokenAr = Jti::findOne($key);     
        if ($accessTokenAr !== null) {            
            $record = $this->toJtiModel($accessTokenAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function delete(JtiModelInterface $jti)
    {
        $result = false;
        if ($accessToken->beforeDelete()) {
            
            $id = $accessToken->getOldKey();
            Jti::deleteAll(['client_id'=>$clientId]);
            $accessToken->setIsNewRecord(true);
            $accessToken->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toJtiModel($accessTokenAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\JtiModelInterface');
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

    public function loadJtiAr($accessTokenAr, $accessToken, $names){
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
