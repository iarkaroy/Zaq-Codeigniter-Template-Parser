Zaq: Codeigniter Template Parser Engine
=======================================

Zaq is a PHP based template parser engine developed to work with Codeigniter.

Installation
------------

1. [Download Zaq](http://github.com/iarkaroy/Zaq-Codeigniter-Template-Parser/archive/master.zip).

2. Copy libraries/Zaq.php to your application/libraries/ folder

3. Copy config/zaq.php to your application/config folder.

4. Create the folder if not exists:

	application/cache

5. Set application/cache writable.


Initialization
--------------

```php
$this->load->library('zaq');
```