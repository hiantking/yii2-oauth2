<?php
/**
 * JwtService.php
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
use sweelix\oauth2\server\interfaces\JwtModelInterface;
use sweelix\oauth2\server\interfaces\JwtServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;
use Exception;

/**
 * This is the jwt service for db
 *  database structure
 *    * oauth2:jwt:<jid> : hash (Jwt)
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class JwtService extends BaseService implements JwtServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(JwtModelInterface $jwt, $attributes)
    {
        if ($jwt->getIsNewRecord()) {
            $result = $this->insert($jwt, $attributes);
        } else {
            $result = $this->update($jwt, $attributes);
        }
        return $result;
    }

    /**
     * Save Jwt
     * @param JwtModelInterface $jwt
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(JwtModelInterface $jwt, $attributes)
    {
        $result = false;
        if (!$jwt->beforeSave(true)) {
            return $result;
        }
        $jwtId = $jwt->getKey();

        //check if record exists
        $jwtAr = Jwt::findOne($jwtId);
        if ($jwtAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$jwtId.'"');
        }
        $jwtAr = new Jwt;
        $this->loadJwtAr($jwtAr, $jwt, $attributes);
        try {
            $jwtAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $jwtAr->getJwtData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $jwt->setOldAttributes($values);
        $jwt->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update Jwt
     * @param JwtModelInterface $jwt
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(JwtModelInterface $jwt, $attributes)
    {
        if (!$jwt->beforeSave(false)) {
            return false;
        }
        $modelKey = $jwt->key();
        $jwtId = isset($values[$modelKey]) ? $values[$modelKey] : $jwt->getKey();

        $jwtAr = Jwt::findOne($jwtId);        
        try {
            $this->loadJwtAr($jwtAr, $jwt, $attributes);
            $jwtAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $jwtAr->getJwtData();
        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $jwt->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $jwt->setOldAttribute($name, $value);
        }
        $jwt->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;
        $jwtAr = Jwt::findOne($key);     
        if ($jwtAr !== null) {            
            $record = $this->toJwtModel($jwtAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function delete(JwtModelInterface $jwt)
    {
        $result = false;
        if ($jwt->beforeDelete()) {
            $id = $jwt->getOldKey();
            Jwt::deleteAll(['client_id'=>$clientId]);
            $jwt->setIsNewRecord(true);
            $jwt->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toJwtModel($jwtAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\JwtModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);        
        $jwtData = $jwtAr->getClientData();
        $attributes = [];
        foreach ($jwtData as $key=>$value) {
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

    public function loadJwtAr($jwtAr, $jwt, $names){
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
                $jwtAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $jwtAr;
    }

}
