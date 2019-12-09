<?php
/**
 * Cookies Component
 *
 * @link https://github.com/mrred85/cakephp-cookies
 * @copyright 2016 - present Victor Rosu. All rights reserved.
 * @license Licensed under the MIT License.
 */

namespace App\Controller\Component;

use App\Utility\CookiesEncryptionTrait;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\I18n\Time;
use Cake\Utility\Hash;

/**
 * @package App\Controller\Component
 * @use App\Utility\CookiesEncryptionTrait
 */
class CookiesComponent extends Component
{
    use CookiesEncryptionTrait;

    /**
     * Cookie default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'key' => null,
        'expire' => 0,
        'path' => '',
        'domain' => '',
        'secure' => false,
        'httpOnly' => false
    ];

    /**
     * Constructor hook method.
     *
     * @param array $config Cookies config
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        /**
         * Set cookie internal salt value
         */
        $this->setInternalSalt();

        /**
         * Cookie encryption key will be set in configuration file under Security key.
         * If cookieKey is not set event if you want to encrypt the values will be plain.
         * [
         *   ...
         *   Security => [
         *     'cookieKey' => env('COOKIE_KEY', 'your_security_key')
         *   ]
         *   ...
         * ]
         */
        $this->_defaultConfig['key'] = Configure::read('Security.cookieKey', null);

        if (empty($this->_defaultConfig['path'])) {
            $this->setConfig('path', $this->getController()->getRequest()->getAttribute('webroot'));
        }
        if (empty($this->_defaultConfig['domain'])) {
            $this->setConfig('domain', $this->cookieDomain());
        }
    }

    /**
     * Events supported by this component.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [];
    }

    /**
     * @inheritdoc
     * @param array|string $key Cookie config key
     * @param mixed|null $value Cookie config value
     * @param bool $merge Cookie merge values (inherited, unused)
     * @return $this
     */
    public function setConfig($key, $value = null, $merge = true)
    {
        if (is_array($key)) {
            $this->_defaultConfig = array_merge($this->_defaultConfig, $key);
        } elseif (is_string($key) && isset($this->_defaultConfig[$key])) {
            $this->_defaultConfig[$key] = $value;
        }

        return $this;
    }

    /**
     * @inheritdoc
     * @param string|null $key Config Key
     * @param mixed $default Cookie default (inherited;
     *   If has value and key is set, the key will return the value.
     *   If is true and no key provided will return the cookie config as object
     * )
     * @return mixed
     */
    public function getConfig($key = null, $default = null)
    {
        $config = $this->_defaultConfig;

        if ($key) {
            if (!isset($config[$key])) {
                return $default;
            }

            return $config[$key];
        }

        if ($default === true) {
            return (object)$config;
        }

        return (array)$config;
    }

    /**
     * Encryption key for Cookies Encryption Trait
     *
     * @return string|null
     */
    protected function _getEncryptionKey()
    {
        return $this->_defaultConfig['key'];
    }

    /**
     * List all cookies.
     *
     * @param bool $decrypt Decrypt cookies values
     * @return array
     */
    public function list(bool $decrypt = false)
    {
        $cookies = [];
        foreach ((array)$_COOKIE as $name => $value) {
            if ($decrypt && strpos($value, $this->internalSalt) === 0) {
                $value = $this->cookieRawValue($name, true);
            }
            $cookies[$name] = $value;
        }

        return $cookies;
    }

    /**
     * Check if the cookie exists (by name).
     *
     * @param string $name Cookie name
     * @return bool
     */
    public function check(string $name)
    {
        $parts = $this->cookieNameParts($name);

        $decrypt = (isset($_COOKIE[$parts->name]) && strpos($_COOKIE[$parts->name], $this->internalSalt) === 0);
        $value = $this->read($name, $decrypt);

        return ($value !== null);
    }

    /**
     * Read cookie value(s).
     *
     * @param string $name Cookie name
     * @param bool $decrypt Cookie decrypt value
     * @return mixed|null
     */
    public function read(string $name, bool $decrypt = false)
    {
        $parts = $this->cookieNameParts($name);

        if (isset($_COOKIE[$parts->name])) {
            $value = $this->cookieRawValue($name, $decrypt);
            if (is_array($value)) {
                if (strpos($name, '.') !== false || Hash::check($value, $name)) {
                    $value = Hash::get($value, $name);
                }
            }

            return $value;
        }

        return null;
    }

    /**
     * Write the cookie.
     *
     * @param string $name Cookie name
     * @param mixed $value Cookie value
     * @param bool $encrypt Cookie encrypt value
     * @return void
     */
    public function write(string $name, $value, bool $encrypt = false)
    {
        $parts = $this->cookieNameParts($name);

        $raw = $this->cookieRawValue($parts->name);
        if (count($parts->parts) > 1) {
            $values = (is_array($raw) ? $raw : []);
            $values = Hash::insert($values, $name, $value);
            $value = json_encode($values);
        } else {
            $value = (is_array($raw) ? json_encode($raw) : $value);
        }

        // Encryption
        if ($encrypt) {
            $value = $this->_encrypt($value);
        }

        $this->setCookie($parts->name, $value, $this->_defaultConfig);
    }

    /**
     * Delete the cookie.
     *
     * @param string $name Cookie name
     * @return void
     */
    public function delete(string $name)
    {
        $parts = $this->cookieNameParts($name);

        if (isset($_COOKIE[$parts->name])) {
            $options = $this->_defaultConfig;
            $options['expire'] = '-1 years';
            $this->setCookie($parts->name, null, $options);
        }
    }

    /**
     * Remove cookie value.
     *
     * @param string $name Cookie name
     * @return void
     */
    public function remove(string $name)
    {
        $parts = $this->cookieNameParts($name);

        if (isset($_COOKIE[$parts->name])) {
            // Set new values
            $values = $this->cookieRawValue($name);
            if (is_array($values)) {
                $values = Hash::remove($values, $name);
                $name = $parts->parts;
                array_pop($name);
                $name = implode('.', $name);
                $values = Hash::get($values, $name);

                $encrypt = (strpos($_COOKIE[$parts->name], $this->internalSalt) === 0);
                $this->write($name, $values, $encrypt);
            }
        }
    }

    /**
     * Get application cookie domain (with dot).
     *
     * @return string
     */
    public function cookieDomain()
    {
        $host = $this->getController()->getRequest()->getUri()->getHost();

        return '.' . str_replace('www.', '', $host);
    }

    /**
     * @param string $name Cookie name
     * @param bool $decrypt Cookie decrypt
     * @return mixed|null
     */
    private function cookieRawValue(string $name, bool $decrypt = true)
    {
        $parts = $this->cookieNameParts($name);

        $value = null;
        if (isset($_COOKIE[$parts->name])) {
            $value = $_COOKIE[$parts->name];

            // Decryption
            if ($decrypt && strpos($_COOKIE[$parts->name], $this->internalSalt) === 0) {
                $value = $this->_decrypt($value);
            }

            $result = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $result;
            }
        }

        return $value;
    }

    /**
     * Split cookie name in parts.
     *
     * @param string $name Cookie name
     * @return object
     */
    private function cookieNameParts(string $name)
    {
        $parts = explode('.', $name);

        return (object)[
            'name' => $parts[0],
            'parts' => $parts
        ];
    }

    /**
     * Create the cookie.
     *
     * @param string $name Cookie name
     * @param mixed $value Cookie value
     * @param array $options Cookie options
     * @return void
     */
    private function setCookie(string $name, $value, array $options)
    {
        setcookie(
            $name,
            $value,
            (new Time($options['expire']))->format('U'),
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httpOnly']
        );
    }
}
