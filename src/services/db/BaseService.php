<?php
/**
 * BearerService.php
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

use sweelix\oauth2\server\interfaces\BaseModelInterface;
use sweelix\oauth2\server\models\BaseModel;
use sweelix\oauth2\server\Module;
use sweelix\oauth2\server\traits\db\TypeConverter;
use yii\base\Object;
use yii\di\Instance;
use yii\helpers\Json;
use Yii;

/**
 * This is the base service for db
 *
 * @author King Lin <dejin@aliyun.com>
 * @copyright 2010-2017 King Lin
 * @license http://www.apache.org/licenses/LICENSE-2.0 license
 * @version 2.0.0
 * @link http://www.hianto2o.com
 * @package sweelix\oauth2\server\services\db
 * @since 2.0.0
 */
class BaseService extends Object
{
    use TypeConverter;

    /**
     * @var string namespace used for key generation
     */
    public $namespace = '';

    /**
     * @var Connection|array|string the Redis DB connection object or the application component ID of the DB connection.
     */
    protected $db;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $dbName = Module::getInstance()->db;
        $this->db = Yii::$app->$dbName;
    }

    /**
     * Compute etag based on model attributes
     * @param BaseModelInterface $model
     * @return string
     * @since 1.0.0
     */
    protected function computeEtag(BaseModelInterface $model)
    {
        return $this->encodeAttributes($model->attributes);
    }

    /**
     * Encode attributes array
     *
     * @param array $attributes
     *
     * @return string
     * @since  1.0.0
     */
    protected function encodeAttributes(Array $attributes)
    {
        $data = Json::encode($attributes);
        $etag = '"' . rtrim(base64_encode(sha1($data, true)), '=') . '"';
        return $etag;
    }

}
