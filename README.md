php-safe
========

Yii2 template engine.
Php-Safe compiler generates php code like source with next features:
 * `<?= 'smth' ?>`, `<?php echo 'smth' ?>` converts to something like `<?php echo \yii\helpers\Html::encode('smth') ?>` (safe converting);
 * `<?php print 'raw' ?>` converts to something like `<?php print 'raw' ?>` (unsafe converting).

Usage
-----

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        //....
        'view' => [
            'renderers' => [
                'sphp' => [
                    'class' => 'flexibuild\phpsafe\ViewRenderer',
                    //the directory or path alias pointing to where Php-Safe engine compiled files will be stored.
                    //'compiledPath' => '@runtime/flexibuild/PhpSafe/compiled',
                    //'fileMode' => null,
                    //'mkDirMode' => 0777,
                    //the name of Yii application cache component for caching rendered files.
                    //'cacheComponent' => 'cache',
                    //'compilerConfig' => [
                        // see flexibuild\phpsafe\Compiler for more info
                    //],
                ],
            ],
        ],
        //....
    ],
    //....
];
```

Than you can create view file with extension `.sphp` and write simple php code.
All `echo` structures in your view file (e.g. `<?=`, `<?php echo...`) will be converted to safe echo.
If you need to echo raw html you may use `print` syntax.

Notes
-----

 * Php-Safe engine use [`token_get_all()`](http://php.net/manual/en/function.token-get-all.php) method for parsing code. It's pretty fast.

 * Php-Safe engine will not parse functions, classes, interfaces & traits structures.
That also means php-safe engine will not works in body of anonymous functions.

 * Php-Safe engine will parse only code of your `.sphp` view file. It doesn't know anything about your other code.

 * If your php configured to use short tags and/or asp tags, php-safe will also parse this tags.

 * You may configure your IDE to parse `.sphp` files like `.php` files for more convenience.

 * By default php-safe engine will compile views on the first rendering only.

 * Be carefull: code like `<?= print('smth') ?>` will be converted to smth like `<?php echo \yii\helpers\Html::encode(print('smth')) ?>`.
After executing this code prints raw 'smth' and echoes safe '1', because php engine will echo result of print function (that always return 1).

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist "flexibuild/php-safe *"
```

or add

```
"flexibuild/php-safe": "*"
```

to the require section of your composer.json.

Gii generators
--------------

This extension allows you to configure your gii module for using standard yii
generators with phpsafe files. For example, you may add it in you main-local file:
```php
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'controller' => 'flexibuild\phpsafe\gii\generators\controller\Generator',
            'crud' => 'flexibuild\phpsafe\gii\generators\crud\Generator',
            'form' => 'flexibuild\phpsafe\gii\generators\form\Generator',
            'module' => 'flexibuild\phpsafe\gii\generators\module\Generator',
        ],
    ];
```

Console commands
----------------

This extension allows you to exec the following console commands:

* `yii phpsafe/flush-all` Flushes all compiled by phpsafe engine files.

* `yii phpsafe/compile-all` Compiles all phpsafe files to php files.


For using console commands you must configure console with renderers like in web configuration.
You must add phpsafe console controller in your console controller map configuration:

```php
return [
    //....
    'controllerMap' => [
        //....
        'phpsafe' => 'flexibuild\phpsafe\console\PhpsafeController',
        //....
    ],
    //....
    'components' => [
        //....
        'view' => [
            'renderers' => [
                'sphp' => [
                    'class' => 'flexibuild\phpsafe\ViewRenderer',
                    // ... and other parameters exactly as in the web configuration
                ],
            ],
        ],
        //....
    ],
    //....
];
```

Note:

* Extension has default config for yii simple base application. For yii-advanced application
you must configure directories so console will may "see" it.
* For better debugging we suggest you to use \flexibuild\phpsafe\web\ErrorHandler in your
web application, or to add 'use \flexibuild\phpsafe\web\ErrorHandlerTrait;' in own error handler.
