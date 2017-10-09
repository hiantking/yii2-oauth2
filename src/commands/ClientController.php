<?php
/**
 * ClientController.php
 *
 * PHP version 5.6+
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2017 Philippe Gaultier
 * @license http://www.sweelix.net/license license
 * @version 1.2.0
 * @link http://www.sweelix.net
 * @package sweelix\oauth2\server\commands
 */

namespace sweelix\oauth2\server\commands;

use sweelix\oauth2\server\models\Client;
use yii\console\Controller;
use Yii;

/**
 * Manage oauth clients
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2017 Philippe Gaultier
 * @license http://www.sweelix.net/license license
 * @version 1.2.0
 * @link http://www.sweelix.net
 * @package sweelix\oauth2\server\commands
 * @since 1.0.0
 */
class ClientController extends Controller
{
    public $clientId;
    public $redirectUri;
    public $grantTypes;
    public $scopes;
    public $userId;
    public $name;
    public $isPublic;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return [
            'clientId',
            // Generated 'secret',
            'redirectUri',
            'grantTypes',
            'scopes',
            'userId',
            'name',
            'isPublic'
        ];
    }
    /**
     * Create new Oauth client
     * @return int
     * @since 1.0.0
     */
    public function actionCreate()
    {
        $client = Yii::createObject('sweelix\oauth2\server\interfaces\ClientModelInterface');
        /* @var \sweelix\oauth2\server\interfaces\ClientModelInterface $client */
        $client->id = empty($this->clientId) ? $this->generateSerialNumber() : $this->clientId;
        $client->secret = $this->getRandomString();
        $client->name = $this->name;
        $redirectUri = empty($this->redirectUri) ? null : explode(',', $this->redirectUri);
        $client->redirectUri = $redirectUri;
        $client->userId = $this->userId;
        $client->isPublic = (bool)$this->isPublic;
        $client->scopes = empty($this->scope) ? null : explode(',', $this->scopes);
        $client->grantTypes = empty($this->grantTypes) ? null : explode(',', $this->grantTypes);
        if ($client->save() === true) {
            $this->stdout('Client created :'."\n");
            $this->stdout(' - id: ' . $client->id . "\n");
            $this->stdout(' - secret: ' . $client->secret . "\n");
            $this->stdout(' - name: ' . $client->name . "\n");
            $this->stdout(' - redirectUri: ' . implode(',', $client->redirectUri) . "\n");
            return Controller::EXIT_CODE_NORMAL;
        } else {
            $this->stdout('Client cannot be created.'."\n");
            return Controller::EXIT_CODE_ERROR;
        }
    }

    public function actionUpdate($id)
    {
        $client = Client::findOne($id);
        if ($client !== null) {
            $client->redirectUri = $this->redirectUri;
            $client->name = $this->name;
            $client->userId = $this->userId;
            $client->isPublic = (bool)$this->isPublic;
            $client->scopes = empty($this->scope) ? null : explode(',', $this->scopes);
            $client->grantTypes = empty($this->grantTypes) ? null : explode(',', $this->grantTypes);
            if ($client->save() === true) {
                $this->stdout('Client updated :' . "\n");
                $this->stdout(' - id: ' . $client->id . "\n");
                $this->stdout(' - secret: ' . $client->secret . "\n");
                $this->stdout(' - name: ' . $client->name . "\n");
                $this->stdout(' - redirectUri: ' . implode(',', $client->redirectUri) . "\n");
                return Controller::EXIT_CODE_NORMAL;
            } else {
                $this->stdout('Client cannot be updated.'."\n");
                return Controller::EXIT_CODE_ERROR;
            }
        } else {
            $this->stdout('Client '.$id.' does not exist'."\n");
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Generate random string
     * @param int $length
     * @return string
     * @since 1.0.0
     */
    protected function getRandomString($length = 40)
    {
        $bytes = (int) $length/2;
        return bin2hex(openssl_random_pseudo_bytes($bytes));
    }

    /**
     * 生成序号.
     * @param string|null $key 扩展值.
     * @return string 序号串.
     */
    protected function generateSerialNumber(){
        $curMicroTime = microtime();        
        list($micro,$curTime) = explode(' ', $curMicroTime);
        $yearFirstSecondTime = strtotime(date("Y-01-01", $curTime));
        $secondTime = $curTime - $yearFirstSecondTime + $micro;        
        $year = date("Y", $curTime);
        $secondTime = ($secondTime * 30000) . '.';
        $key = strchr($secondTime,'.',true);
        $key = str_pad($key, 12,'0', STR_PAD_RIGHT);
        return $year . $key;
    }
}
