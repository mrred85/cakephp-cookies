<?php
/**
 * Cookies Encryption Trait
 *
 * @link https://github.com/mrred85/cakephp-cookies
 * @copyright 2016 - present Victor Rosu. All rights reserved.
 * @license Licensed under the MIT License.
 */

namespace App\Utility;

use Cake\Utility\Security;

/**
 * @package App\Utility
 */
trait CookiesEncryptionTrait
{
    /**
     * Cookie encryption internal salt.
     *
     * @var string
     */
    private $internalSalt;

    /**
     * Encryption key for Cookies Encryption Trait
     *
     * @return string|null
     */
    abstract protected function _getEncryptionKey();

    /**
     * Set internal salt
     *
     * @return void
     */
    protected function setInternalSalt()
    {
        $this->internalSalt = substr(Security::getSalt(), 1, 9);
    }

    /**
     * Encrypt cookie value (AES method)
     *
     * @param mixed $value Cookie value
     * @param string|null $hMacSalt Cookie encryption salt
     * @return string
     */
    protected function _encrypt($value, $hMacSalt = null)
    {
        $key = $this->_getEncryptionKey();
        if ($key) {
            $value = Security::encrypt($value, $key, $hMacSalt);
            $value = $this->internalSalt . base64_encode($value);
        }

        return $value;
    }

    /**
     * Decrypt cookie value (AES method)
     *
     * @param mixed $value Cookie value
     * @param string|null $hMacSalt Cookie encryption salt
     * @return mixed
     */
    protected function _decrypt($value, $hMacSalt = null)
    {
        $key = $this->_getEncryptionKey();
        if ($key) {
            $value = substr($value, strlen($this->internalSalt), strlen($value));
            $value = base64_decode($value);
            $value = Security::decrypt($value, $key, $hMacSalt);
        }

        return $value;
    }
}
