<?php
namespace sweelix\oauth2\server\models\ar;

use Yii;

/**
 * PublicKeys
 *
 * @author dejin <ldj@hianto2o.com>
 */
class CypherKeys extends ActiveRecord
{
    /**
     * @inheritdoc
     */
	public static function tableName()
    {
        return '{{%oauth_cypher_keys}}';
    }

    /**
     * 获取AccessTokens Model
     * 
     * @return sweelix\oauth2\server\interfaces 
     */
    public function getCypherKeyData(){
    	return [
            'id' => $this->client_id,
            'publicKey' => $this->public_key,
            'privateKey' => $this->private_key,
            'encryptionAlgorithm' => $this->encryption_algorithm,
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->encryptionAlgorithm === null) {
            $this->encryptionAlgorithm = self::HASH_ALGO;
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function generateKeys($bits = 2048, $type = OPENSSL_KEYTYPE_RSA)
    {
        $opensslHandle = openssl_pkey_new([
            'private_key_bits' => $bits,
            'private_key_type' => $type
        ]);

        openssl_pkey_export($opensslHandle, $privateKey);
        $details = openssl_pkey_get_details($opensslHandle);
        $publicKey = $details['key'];
        openssl_free_key($opensslHandle);
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }
}
