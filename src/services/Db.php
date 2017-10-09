<?php
/**
 * Mysql.php
 *
 * PHP version 5.6+
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services
 */

namespace sweelix\oauth2\server\services;

use sweelix\oauth2\server\interfaces\ServiceBootstrapInterface;
use sweelix\oauth2\server\services\db\AccessTokenService;
use sweelix\oauth2\server\services\db\AuthCodeService;
use sweelix\oauth2\server\services\db\ClientService;
use sweelix\oauth2\server\services\db\CypherKeyService;
use sweelix\oauth2\server\services\db\JtiService;
use sweelix\oauth2\server\services\db\JwtService;
use sweelix\oauth2\server\services\db\RefreshTokenService;
use sweelix\oauth2\server\services\db\ScopeService;
use Yii;

/**
 * This is the service loader for db
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services
 * @since 2.0
 */
class Db implements ServiceBootstrapInterface
{
    /**
     * @inheritdoc
     */
    public static function register($app)
    {
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\AccessTokenServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\AccessTokenServiceInterface', [
                'class' => AccessTokenService::className(),
                'namespace' => 'oauth2:accessTokens',
                'userNamespace' => 'oauth2:users',
                'clientNamespace' => 'oauth2:clients',
            ]);
        }
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\AuthCodeServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\AuthCodeServiceInterface', [
                'class' => AuthCodeService::className(),
                'namespace' => 'oauth2:authCodes',
            ]);
        }
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\ClientServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\ClientServiceInterface', [
                'class' => ClientService::className(),
                'namespace' => 'oauth2:clients'
            ]);
        }
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\CypherKeyServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\CypherKeyServiceInterface', [
                'class' => CypherKeyService::className(),
                'namespace' => 'oauth2:cypherKeys',
            ]);
        }
        /*if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\JtiServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\JtiServiceInterface', [
                'class' => JtiService::className(),
                'namespace' => 'oauth2:jti',
            ]);
        }*/
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\JwtServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\JwtServiceInterface', [
                'class' => JwtService::className(),
                'namespace' => 'oauth2:jwt',
            ]);
        }
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\RefreshTokenServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\RefreshTokenServiceInterface', [
                'class' => RefreshTokenService::className(),
                'namespace' => 'oauth2:refreshTokens',
                'userNamespace' => 'oauth2:users',
                'clientNamespace' => 'oauth2:clients',
            ]);
        }
        if (Yii::$container->hasSingleton('sweelix\oauth2\server\interfaces\ScopeServiceInterface') === false) {
            Yii::$container->setSingleton('sweelix\oauth2\server\interfaces\ScopeServiceInterface', [
                'class' => ScopeService::className(),
                'namespace' => 'oauth2:scopes',
            ]);
        }
    }
}
