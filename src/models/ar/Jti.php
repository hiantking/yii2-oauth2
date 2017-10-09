<?php
/**
 * Jti.php
 *
 * PHP version 5.6+
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2017 Philippe Gaultier
 * @license http://www.sweelix.net/license license
 * @version 1.2.0
 * @link http://www.sweelix.net
 * @since 1.0.0
 */

namespace sweelix\oauth2\server\models\ar;

use sweelix\oauth2\server\interfaces\JtiModelInterface;
use Yii;

/**
 * This is the jti model
 *
 * @author Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2017 Philippe Gaultier
 * @license http://www.sweelix.net/license license
 * @version 1.2.0
 * @link http://www.sweelix.net
 * @package sweelix\oauth2\server\models
 * @since 1.0.0
 *
 * @property string $id
 * @property string $clientId
 * @property string $subject
 * @property string $audience
 * @property string $expires
 * @property string $jti
 */
class Jti extends ActiveRecord
{

    return '{{%oauth_jti}}';
}
