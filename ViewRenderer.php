<?php

namespace flexibuild\phpsafe;

use Yii;
use yii\base\ViewRenderer as BaseViewRenderer;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\caching\Cache;
use yii\caching\FileCache;
use yii\caching\FileDependency;

/**
 * ViewRenderer component for yii2 application.
 * 
 * @author SeynovAM <sejnovalexey@gmail.com>
 */
class ViewRenderer extends BaseViewRenderer
{
    /**
     * @var string the directory or path alias pointing to where Php-Safe engine
     * compiled files will be stored.
     */
    public $compiledPath = '@runtime/phpsafe/compiled';

    /**
     * @var integer make directory that will be passed into [[FileHelper::createDirectory()]].
     */
    public $mkDirMode = 0755;

    /**
     * @var mixed string|yii\caching\Cache|null|false. The name of Yii application
     * cache component for caching rendered files. Or instance of yii\caching\Cache class.
     * Null or array meaning create and use yii\caching\FileCache component,
     * array will be used as FileCache config (null by default).
     * False meaning disabling caching.
     */
    public $cacheComponent;

    /**
     * @var string the prefix of cache key for file.
     */
    public $cacheKeyPrefix = 'phpsafe_';

    /**
     * @var array key => value pairs that will be passed as php-safe compiler config.
     */
    public $compilerConfig = [];

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     * @throws InvalidConfigException if cacheComponent is incorrect.
     */
    public function init()
    {
        parent::init();
        $this->initCacheComponent();
    }

    /**
     * Initialization of cache component.
     * @throws InvalidConfigException if cacheComponent is incorrect.
     */
    protected function initCacheComponent()
    {
        if ($this->cacheComponent === null || is_array($this->cacheComponent)) {
            $this->cacheComponent = Yii::createObject(array_merge([
                'class' => FileCache::className(),
                'cachePath' => $this->compiledPath,
                'dirMode' => $this->mkDirMode,
            ], $this->cacheComponent ?: []));
        } elseif (is_string($this->cacheComponent)) {
            $this->cacheComponent = Yii::$app->get($this->cacheComponent);
        }

        if ($this->cacheComponent !== false && !($this->cacheComponent instanceof Cache)) {
            throw new InvalidConfigException('Incorrect value of '.__CLASS__.'::$cacheComponent param. Calculated value of this param must be an instance of '.Cache::className().'.');
        }
    }

    /**
     * Renders a view file.
     *
     * This method is invoked by [[View]] whenever it tries to render a view.
     * Child classes must implement this method to render the given view file.
     *
     * @param \yii\base\View $view the view object used for rendering the file.
     * @param string $file the view file.
     * @param array $params the parameters to be passed to the view file.
     *
     * @return string the rendering result
     */
    public function render($view, $file, $params)
    {
        if (false === $compiledFile = $this->loadContentFromCache($file)) {
            $compiler = Compiler::createFromCode(file_get_contents($file), $this->compilerConfig);
            $content = $compiler->getCompiledCode();

            while (file_exists($this->getCompiledFilePath($compiledFile = Yii::$app->security->generateRandomString(12).'.php')));
            if (!FileHelper::createDirectory($dir = Yii::getAlias($this->compiledPath), $this->mkDirMode)) {
                $mode = '0'.base_convert($this->mkDirMode, 10, 8);
                throw new Exception("Cannot create directory '$dir' with $mode mode.");
            }
            file_put_contents($this->getCompiledFilePath($compiledFile), $content);

            $this->saveContentToCache($file, $compiledFile);
        }
        return $view->renderPhpFile($this->getCompiledFilePath($compiledFile), $params);
    }

    /**
     * Tries to load compiled code from cache.
     * @param string $file the view file.
     * @return mixed string compiled file name or false if did not found in cache.
     */
    protected function loadContentFromCache($file)
    {
        if ($this->cacheComponent === false) {
            return false;
        }

        if (false !== $compiledFile = $this->cacheComponent->get($this->getCacheKey($file))) {
            return file_exists($this->getCompiledFilePath($compiledFile)) ? $compiledFile : false;
        }
        return false;
    }

    /**
     * Tries to save compiled code to cache.
     * @param string $file the view file.
     * @param string $compiledFileName compiled file name that will save.
     * @return boolean whether the value is successfully stored into cache.
     */
    protected function saveContentToCache($file, $compiledFileName)
    {
        if ($this->cacheComponent === false) {
            return false;
        }

        return $this->cacheComponent->set($this->getCacheKey($file), $compiledFileName, 0, new FileDependency([
            'fileName' => $file,
        ]));
    }

    /**
     * Get unique key that can represent this file uniquely among other files.
     * @param string $file
     * @return string
     */
    public function getCacheKey($file)
    {
        return $this->cacheKeyPrefix.str_replace('\\', '/', $file);
    }

    /**
     * @param string $name file name.
     * @return string the full path to compiled file.
     */
    public function getCompiledFilePath($name)
    {
        return rtrim(Yii::getAlias($this->compiledPath), '\/').'/'.$name;
    }
}
