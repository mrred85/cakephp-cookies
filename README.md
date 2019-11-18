# CakePHP Cookies Component

CookiesComponent is a replacement for default and depricated **CookieComponent** from CakePHP. The CookiesComponent is a wrapper around the native PHP `setcookie()` method. It makes it easier to manipulate cookies, and encrypt or decrypt cookie data.

## Install Cookies
- Copy the `src > Controller > Component > CookiesComponent.php` to your **Component** folder.
- Create `Utility` folder in `src` if doesn't exists.
- Copy the `src > Utility > CookiesEncryptionTrait.php` to your **Utility** folder.
- Load the component in the controller with `$this->loadComponent('Cookies');` command.

### Requirements
- PHP >= 7.1.x
- CakePHP >= 3.6.x

## Cookie name
- Simple name: `mycookie`
- Array dot notation: `mycookie.othercookie`

## Encrypt and Decrypt
Cookie value can be encrypted and decrypted. By default both values are false.
```php
function read(string $name, bool $decrypt = false): mixed
function write(string $name, mixed $value, bool $encrypt = false): void
```

Use encrypt parameter. Write a cookie with encrypted data value.
```php
$this->Cookies
    ->setConfig('expire', '+1 day')
    ->write('mycookie', time(), true);
```

Use decrypt parameter. Read an encrypted cookie data value.
```php
echo $this->Cookies->read('mycookie', true);
```

## Remove
Remove a cookie value from the cookie array data value, but will not delete the cookie.
```php
$this->Cookies->remove('mycookie.othercookie');
```

## Delete
Delete the cookie completely.
```php
$this->Cookies->delete('mycookie');
```

## Example
Load `Cookies` component and create a cookie, check if exists and read the cookie value.
```php
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * @package App\Controller
 * @property \App\Controller\Component\CookiesComponent $Cookies
 */
class AppController extends Controller
{
    // Your code
    public function initialize()
    {
        $this->loadComponent('Cookies');
    }

    /**
     * @inheritdoc
     * @param Event $event Event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(Event $event)
    {
        // Global settings
        $this->Cookies->setConfig('expire', 0);
        $this->Cookies->setConfig('path', '/');
        $this->Cookies->setConfig('secure', false);
    }

    public function index()
    {
        // Get Cookies config object
        $this->set('Cookies', $this->Cookies->getConfig(null, true));

        // Write a cookie
        $this->Cookies
            ->setConfig('expire', '+1 day')
            ->write('mycookie', time());

        // Check if the cookie exists
        $cookieExists = $this->Cookies->check('mycookie');
        if ($cookieExists) {
            // Read cookie value
            echo $this->Cookies->read('mycookie');
        }
    }
    // Your code
}
```

Enjoy ;)
