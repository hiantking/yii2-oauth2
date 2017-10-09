<?php
/**
 * ScopeService.php
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
use sweelix\oauth2\server\interfaces\ScopeModelInterface;
use sweelix\oauth2\server\models\ar\Scopes;
use sweelix\oauth2\server\interfaces\ScopeServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;

/**
 * This is the scope service for db
 *  database structure
 *    * oauth2:scopes:<sid> : hash (Scope)
 *    * oauth2:scopes:keys : set scopeIds
 *    * oauth2:scopes:defaultkeys : set default scopeIds
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class ScopeService extends BaseService implements ScopeServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(ScopeModelInterface $scope, $attributes)
    {
        if ($scope->getIsNewRecord()) {
            $result = $this->insert($scope, $attributes);
        } else {
            $result = $this->update($scope, $attributes);
        }
        return $result;
    }

    /**
     * Save Scope
     * @param ScopeModelInterface $scope
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(ScopeModelInterface $scope, $attributes)
    {
        $result = false;
        if (!$scope->beforeSave(true)) {
            return $result;
        }
        $scopeCode = $scope->getKey();
        $scopeAr = Scope::findOne($scopeCode);
        if ($scopeAr !== null) {
            throw new DuplicateKeyException('Duplicate key "'.$scopeCode.'"');
        }
        $scopeAr = new Scopes;
        $this->loadScopeAr($scopeAr, $scope, $attributes);
        try {
            $scopeAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $scopeAr->getScopeData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $scope->setOldAttributes($values);
        $scope->afterSave(true, $changedAttributes);
        $result = true;
        return $result;
    }


    /**
     * Update ScopeModelInterface
     * @param Scope $scope
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(ScopeModelInterface $scope, $attributes)
    {
        if (!$scope->beforeSave(false)) {
            return false;
        }

        $values = $scope->getDirtyAttributes($attributes);
        $modelKey = $scope->key();
        $scopeId = isset($values[$modelKey]) ? $values[$modelKey] : $scope->getKey();
        $scopeAr = Scopes::findOne($scopeId);        
        try {
            $this->loadRefreshTokenAr($refreshTokenAr, $refreshToken, $attributes);
            $scopeAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        
        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $scope->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $scope->setOldAttribute($name, $value);
        }
        $scope->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;
        $scopeAr = Scopes::find()->where(['scope'=>$key])->one();     
        if ($scopeAr !== null) {            
            $record = $this->toScopeModel($scopeAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function findAvailableScopeIds()
    {
        $scopeArList = Scopes::find()->all();
        return array_column($scopeArList, 'scope');
    }

    /**
     * @inheritdoc
     */
    public function findDefaultScopeIds($clientId = null)
    {
        $scopeArList = Scopes::find()->where(['is_default'=>1])->all();
        return array_column($scopeArList, 'scope');
    }

    /**
     * @inheritdoc
     */
    public function delete(ScopeModelInterface $scope)
    {
        $result = false;
        if ($scope->beforeDelete()) {
            $id = $scope->getOldKey();
            Scopes::deleteAll(['scope'=>$id]);
            $scope->setIsNewRecord(true);
            $scope->afterDelete();
            $result = true;
        }
        return $result;
    }

    public function toScopeModel($scopeAr){        
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\ScopeModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);        
        $scopeData = $scopeAr->getClientData();
        $attributes = [];
        foreach ($scopeData as $key=>$value) {
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

    public function loadScopeAr($scopeAr, $scope, $names){
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
                $scopeAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $scopeAr;
    }
    
}
