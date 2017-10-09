<?php
/**
 * AuthCodeService.php
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

use sweelix\oauth2\server\models\ar\AuthorizationCodes;
use sweelix\oauth2\server\exceptions\DuplicateIndexException;
use sweelix\oauth2\server\exceptions\DuplicateKeyException;
use sweelix\oauth2\server\interfaces\AuthCodeModelInterface;
use sweelix\oauth2\server\interfaces\AuthCodeServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;

/**
 * This is the auth code service for db
 *  database structure
 *    * oauth2:authCodes:<aid> : hash (AuthCode)
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class AuthCodeService extends BaseService implements AuthCodeServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(AuthCodeModelInterface $authCode, $attributes)
    {
        if ($authCode->getIsNewRecord()) {
            $result = $this->insert($authCode, $attributes);
        } else {
            $result = $this->update($authCode, $attributes);
        }
        return $result;
    }

    /**
     * Save Auth Code
     * @param AuthCodeModelInterface $authCode
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(AuthCodeModelInterface $authCode, $attributes)
    {
        $result = false;
        if (!$authCode->beforeSave(true)) {
            return $result;
        }
        $code = $authCode->getKey();

        //check if record exists
        $authCodeAr = AuthorizationCodes::findOne($code);
        if ($authCodeAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$code.'"');
        }
        $authCodeAr = new AuthorizationCodes;
        $this->loadAuthCodeAr($authCodeAr, $authCode, $attributes);
        try {
            $authCodeAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $authCodeAr->getAuthCodeData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $authCode->setOldAttributes($values);
        $authCode->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update Auth Code
     * @param AuthCodeModelInterface $authCode
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(AuthCodeModelInterface $authCode, $attributes)
    {
        if (!$authCode->beforeSave(false)) {
            return false;
        }
        $modelKey = $authCode->key();
        $code = isset($values[$modelKey]) ? $values[$modelKey] : $authCode->getKey();

        $authCodeAr = AuthorizationCodes::findOne($code);        
        try {
            $this->loadAuthCodeAr($authCodeAr, $authCode, $attributes);
            $authCodeAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $authCodeAr->getAuthCodeData();
        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $authCode->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $authCode->setOldAttribute($name, $value);
        }
        $authCode->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;

        $authCodeAr = AuthorizationCodes::findOne($key);     
        if ($authCodeAr !== null) {            
            $record = $this->toAuthCodeModel($authCodeAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function delete(AuthCodeModelInterface $authCode)
    {
        $result = false;
        if ($authCode->beforeDelete()) {
            
            $code = $authCode->getOldKey();
            AuthorizationCodes::deleteAll(['authorization_code'=>$code]);
            $authCode->setIsNewRecord(true);
            $authCode->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toAuthCodeModel($authCodeAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\AuthCodeModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);        
        $authCodeData = $authCodeAr->getClientData();
        $attributes = [];
        foreach ($authCodeData as $key=>$value) {
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

    public function loadAuthCodeAr($authCodeAr, $authCode, $names){
        $values = $authCode->getDirtyAttributes($names);        
        $values['authorization_code'] = $values['id'];
        $values['userId'] = $values['userId']?:0;
        unset($values['id']);
        var_dump($values);exit;
        $this->setAttributesDefinitions($authCode->attributesDefinition());
        foreach ($values as $key => $value)
        {
            if (($key === 'expiry') && ($value > 0)) {
                $expire = $value;
            }
            if ($value !== null) {
                $attributeName = Inflector::underscore($key);
                $authCodeAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $authCodeAr;
    }
}
