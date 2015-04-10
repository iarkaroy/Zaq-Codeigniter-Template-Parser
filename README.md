Zaq: Codeigniter Template Parser Engine
=======================================

Zaq is a PHP based template parser engine developed to work with Codeigniter. This library has been developed for developers to integrate php codes in views easily. Using this library will also allow the view file to be more readable. View files in Codeigniter (or in any other framework following MVC) always contains both html and php codes which make them a bit harder to read. This problem can be eradicated by using a parser engine which makes the view files a lot more easier to work with.

Codeigniter, by default, comes with an optional template parser. But, unfortunately, that one does not provide sufficient pseudo markup to work with. Zaq, without doubt, is able to provide much more flexibility while building view files with pseudo markup to replace php codes.

Let's take a look at Zaq's insight and usage.

<br>

## Installation

1. [**Download Zaq**](https://github.com/iarkaroy/Zaq-Codeigniter-Template-Parser/archive/master.zip)

2. Copy `libraries/Zaq.php` to your `application/libraries/` folder

3. Copy `config/zaq.php` to your `application/config/` folder.

4. Create the folder if not exists: `application/cache`

5. Set `application/cache` writable.

<br>

## Initialization

Like other libraries in CodeIgniter, the Zaq library class is initialized in your controller using the `$this->load->library()` method:

    $this->load->library('zaq');

Or you can autoload the library in `autoload.php`

Once loaded, the Zaq library object will be available using: `$this->zaq`

<br>

For usage instructions and more, visit [How to use Zaq: Codeigniter Template Parser Engine](http://www.arkaroy.net/blog/how-to-use-zaq-codeigniter-template-parser/)
