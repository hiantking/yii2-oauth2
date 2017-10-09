<?php
/**
 * CypherKeyService.php
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

use sweelix\oauth2\server\exceptions\DuplicateIndexException;
use sweelix\oauth2\server\exceptions\DuplicateKeyException;
use sweelix\oauth2\server\models\ar\CypherKeys as CypherKeyAr;
use sweelix\oauth2\server\models\CypherKey;
use sweelix\oauth2\server\interfaces\CypherKeyServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;

/**
 * This is the cypher key service for db
 *  database structure
 *    * oauth2:cypherKeys:<aid> : hash (CypherKey)
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class CypherKeyService extends BaseService implements CypherKeyServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(CypherKey $cypherKey, $attributes)
    {
        if ($cypherKey->getIsNewRecord()) {
            $result = $this->insert($cypherKey, $attributes);
        } else {
            $result = $this->update($cypherKey, $attributes);
        }
        return $result;
    }

    /**
     * Save Cypher Key
     * @param CypherKey $cypherKey
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(CypherKey $cypherKey, $attributes)
    {
       $result = false;
        if (!$cypherKey->beforeSave(true)) {
            return $result;
        }
        $cypherKeyId = $cypherKey->getKey();

        //check if record exists
        $cypherKeyAr = CypherKeyAr::findOne($cypherKeyId);
        if ($cypherKeyAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$cypherKeyId.'"');
        }
        $cypherKeyAr = new CypherKeyAr;
        $this->loadCypherKeyAr($cypherKeyAr, $cypherKey, $attributes);
        try {
            $cypherKeyAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $cypherKeyAr->getCypherKeyData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $cypherKey->setOldAttributes($values);
        $cypherKey->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update Cypher Key
     * @param CypherKey $cypherKey
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(CypherKey $cypherKey, $attributes)
    {
        if (!$cypherKey->beforeSave(false)) {
            return false;
        }
        $modelKey = $cypherKey->key();
        $cypherKeyId = isset($values[$modelKey]) ? $values[$modelKey] : $cypherKey->getKey();

        $cypherKeyAr = CypherKeyAr::findOne($cypherKeyId);        
        try {
            $this->loadCypherKeyAr($cypherKeyAr, $cypherKey, $attributes);
            $cypherKeyAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $cypherKeyAr->getCypherKeyData();
        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $cypherKey->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $cypherKey->setOldAttribute($name, $value);
        }
        $cypherKey->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;

        $cypherKeyAr = CypherKeyAr::findOne($key);     
        if ($cypherKeyAr !== null) {            
            $record = $this->toCypherKeyModel($cypherKeyAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function delete(CypherKey $cypherKey)
    {
        $result = false;
        if ($cypherKey->beforeDelete()) {
            
            $id = $cypherKey->getOldKey();
            CypherKeyAr::deleteAll(['client_id'=>$clientId]);
            $cypherKey->setIsNewRecord(true);
            $cypherKey->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toCypherKeyModel($cypherKeyAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\CypherKeyModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);        
        $cypherKeyData = $cypherKeyAr->getClientData();
        $attributes = [];
        foreach ($cypherKeyData as $key=>$value) {
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

    public function loadCypherKeyAr($cypherKeyAr, $cypherKey, $names){
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
                $cypherKeyAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $cypherKeyAr;
    }
}
