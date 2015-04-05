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


Parse View Syntax
-----------------

```php
$this->zaq->parse( $view, $data = array(), $return = FALSE );
```


Usage
-----

#### Intended Code
```php
<?php if ( $products ) : ?>
    <ul>
    <?php foreach ( $products as $product ) : ?>
        <li><a href="<?php echo $product['link'] ; ?>"><?php echo $product['title'] ; ?> (<?php echo $product['price'] ; ?>)</a></li>
    <?php endforeach ; ?>
    </ul>
<?php else : ?>
    <span>No product found.</span>
<?php endif ; ?>
```

#### Zaq Code
```
{if products}
    <ul>
    {foreach products as product}
        <li><a href="{product[link]}">{product[title]} ({product[price]})</a></li>
    {/foreach}
    </ul>
{else}
    <span>No product found.</span>
{/if}
```

#### Intended Code
```php
<ul>
<?php foreach ( $options as $item => $value ) : ?>
    <li><?php echo $item ; ?> => <?php echo $value ; ?></li>
<?php endforeach ; ?>
</ul>
```

#### Zaq Code
```
<ul>
{foreach options as item = value}
    <li>{item} => {value}</li>
{/foreach}
</ul>
```

#### More...
````
<?php echo date ( 'Y-m-d H:i:s' , $now ) ; ?>					{date('Y-m-d H:i:s', now)}

<?php echo time ( ) ; ?>							{time()}

<?php echo $fname . $lname ; ?>							{fname . lname}

<?php echo $books -> get_by_author( $author ) -> first() -> title ; ?>		{books->get_by_author(author)->first()->title}
````