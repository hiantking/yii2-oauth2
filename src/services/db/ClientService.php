<?php
/**
 * ClientService.php
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

use sweelix\oauth2\server\models\ar\Clients;
use sweelix\oauth2\server\exceptions\DuplicateIndexException;
use sweelix\oauth2\server\exceptions\DuplicateKeyException;
use sweelix\oauth2\server\interfaces\ClientModelInterface;
use sweelix\oauth2\server\interfaces\ClientServiceInterface;
use yii\db\Exception as DatabaseException;
use Yii;
use yii\helpers\Inflector;

/**
 * This is the client service for db
 *  database structure
 *    * oauth2:clients:<cid> : hash (Client)
 *    * oauth2:clients:<cid>:users : set
 *    * oauth2:users:<uid>:clients : set
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class ClientService extends BaseService implements ClientServiceInterface
{
    /**
     * @inheritdoc
     */
    public function save(ClientModelInterface $client, $attributes)
    {
        if ($client->getIsNewRecord()) {
            $result = $this->insert($client, $attributes);
        } else {
            $result = $this->update($client, $attributes);
        }
        return $result;
    }

    /**
     * Save Client
     * @param ClientModelInterface $client
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     * @since 1.0.0
     */
    protected function insert(ClientModelInterface $client, $attributes)
    {
        $result = false;
        if (!$client->beforeSave(true)) {
            return $result;
        }
        $clientId = $client->getKey();
        $clentAr = Clients::findOne($clientId);
        if ($clentAr !== null) {
            throw new DuplicateKeyException('Duplicate ClentId "'.$clientId.'"');
        }

        $clentAr = new Clients;
        $this->loadClientAr($clentAr, $client, $attributes);
        //TODO: use EXEC/MULTI to avoid errors
        try {
            $result = $clentAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while inserting entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }
        $values = $clentAr->getClientData();
        $changedAttributes = array_fill_keys(array_keys($values), null);
        $client->setOldAttributes($values);
        $client->afterSave(true, $changedAttributes);
        return $result;
    }


    /**
     * Update Client
     * @param ClientModelInterface $client
     * @param null|array $attributes attributes to save
     * @return bool
     * @throws DatabaseException
     * @throws DuplicateIndexException
     * @throws DuplicateKeyException
     */
    protected function update(ClientModelInterface $client, $attributes)
    {
        if (!$client->beforeSave(false)) {
            return false;
        }

        $clientId = $client->getKey();
        $clientAr = Clients::findOne($clientId);
        $this->setAttributesDefinitions($client->attributesDefinition());

        $this->loadClientAr($clientAr, $client, $attributes);
        try {
            $clientAr->save(false);
        } catch (DatabaseException $e) {
            // @codeCoverageIgnoreStart
            // we have a REDIS exception, we should not discard
            Yii::trace('Error while updating entity', __METHOD__);
            throw $e;
            // @codeCoverageIgnoreEnd
        }

        $values = $clientAr->getClientData();
        $changedAttributes = [];
        foreach ($values as $name => $value) {
            $oldAttributes = $client->getOldAttributes();
            $changedAttributes[$name] = isset($oldAttributes[$name]) ? $oldAttributes[$name] : null;
            $client->setOldAttribute($name, $value);
        }
        $client->afterSave(false, $changedAttributes);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findOne($key)
    {
        $record = null;
        $clientAr = Clients::findOne($key);
        if ($clientAr !== null) {
            $record = $this->toClientModel($clientAr);
            $record->afterFind();
        }
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function delete(ClientModelInterface $client)
    {
        $result = false;
        if ($client->beforeDelete()) {
            $id = $client->getOldKey();
            $clientKey = $this->getClientKey($id);
            $clientUsersListKey = $this->getClientUsersListKey($id);


            // before cleaning the client, drop all access tokens and refresh tokens
            $token = Yii::createObject('sweelix\oauth2\server\interfaces\RefreshTokenModelInterface');
            $tokenClass = get_class($token);
            $tokenClass::deleteAllByClientId($id);

            $token = Yii::createObject('sweelix\oauth2\server\interfaces\AccessTokenModelInterface');
            $tokenClass = get_class($token);
            $tokenClass::deleteAllByClientId($id);

            $clientAr = Clients::findOne($id);
            if($clientAr){
                $clientAr->delete();
            }
            $client->setIsNewRecord(true);
            $client->afterDelete();
            $result = true;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasUser(ClientModelInterface $client, $userId)
    {
        $clientId = $client->getKey();
        return Clients::find()->where(['user_id'=>$userId,'client_id'=>$clientId])->exists();
    }

    /**
     * @inheritdoc
     */
    public function addUser(ClientModelInterface $client, $userId)
    {
        $clientId = $client->getKey();
        $clientAr = Clients::findOne($clientId);
        if($clientAr == null){
            throw new DatabaseException("Not Found Data ClientId:" . $clientId);
        }
        $clientAr->user_id = $userId;
        $clientAr->save(false);
        //TODO: check if we should send back false or not
        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeUser(ClientModelInterface $client, $userId)
    {
        $clientId = $client->getKey();
        $$clientAr = Clients::findOne($clientId);
        if($clientAr == null){
            throw new DatabaseException("Not Found Data ClientId:" . $clientId);
        }
        $clientAr->user_id = 0;
        $clientAr->save(false);
        //TODO: check if we should send back false or not
        return true;
    }

    /**
     * @inheritdoc
     */
    public function findAllByUserId($userId)
    {
        $clientsArList = Clients::find()->where(['user_id'=>$userId])->all();
        $clients = [];
        foreach($clientsArList as $clientsAr) {
            $result = $this->toClientModel($clientAr);;
            if ($result instanceof ClientModelInterface) {
                $clients[] = $result;
            }
        }
        return $clients;
    }

    public function toClientModel($clientAr){
        $clientData = $clientAr->getClientData();
        $record = Yii::createObject('sweelix\oauth2\server\interfaces\ClientModelInterface');
        /** @var ClientModelInterface $record */
        $properties = $record->attributesDefinition();
        $this->setAttributesDefinitions($properties);
        $attributes = [];
        foreach ($clientData as $key=>$value) {
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

    public function loadClientAr($clientAr, $client, $names){
        $values = $client->getDirtyAttributes($names);        
        $values['clientId'] = $values['id'];
        $values['userId'] = $values['userId']?:0;
        unset($values['id']);
        $this->setAttributesDefinitions($client->attributesDefinition());
        foreach ($values as $key => $value)
        {
            if ($value !== null) {
                $attributeName = Inflector::underscore($key);
                $clientAr->setAttribute($attributeName, $this->convertToDatabase($key, $value));
            }
        }
        return $clientAr;
    }
}
