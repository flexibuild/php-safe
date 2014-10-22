<?php

namespace flexibuild\phpsafe;

use Yii;
use flexibuild\phpsafe\helpers\FileHelper;

use yii\base\ViewRenderer as BaseViewRenderer;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\Exception;

use yii\caching\Cache;
use yii\caching\FileCache;
use yii\caching\FileDependency as YiiFileDependency;
use flexibuild\phpsafe\caching\FileDependency;

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
     * @var string Yii styled config for cache dependency object.
     */
    public $cacheDependencyConfig = 'flexibuild\phpsafe\caching\FileDependency';

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
                'cachePath' => rtrim($this->compiledPath, '\/').'/cache',
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
        if (false === $hash = $this->loadHashFromCache($file)) {
            $compiledFilePath = $this->compileFile($file, false);
        }  else {
            $compiledFilePath = $this->getCompiledFilePath($hash);
        }
        return $view->renderPhpFile($compiledFilePath, $params);
    }

    /**
     * Compiles file.
     * @param string $file path to phpsafe file.
     * @param bool $checkCache whether method must check hash. If true and cache
     * consits hash for this file it will not recompiled. If false method will
     * always recompile file.
     * @return string compiled file path.
     * @throws InvalidParamException if file does not exists.
     * @throws Exception if cannot create dir.
     */
    public function compileFile($file, $checkCache = true)
    {
        if ($checkCache && false !== $hash = $this->loadHashFromCache($file)) {
            return $this->getCompiledFilePath($hash);
        }

        if (!($realFilePath = realpath($file)) || !($sourceContent = @file_get_contents($realFilePath))) {
            throw new InvalidParamException("File '$file' was not found.");
        }

        Yii::beginProfile($profileToken = "Compile file $file.", __METHOD__);
        $compilerConfig = array_merge($this->compilerConfig, ['compilingFilePath' => $realFilePath]);
        $compiler = Compiler::createFromCode($sourceContent, $compilerConfig);
        $content = $compiler->getCompiledCode();
        Yii::endProfile($profileToken, __METHOD__);

        while (file_exists($compiledFile = $this->getCompiledFilePath($hash = Yii::$app->security->generateRandomString(12))));
        if (!FileHelper::createDirectory($dir = dirname($compiledFile), $this->mkDirMode, true)) {
            $mode = '0'.base_convert($this->mkDirMode, 10, 8);
            throw new Exception("Cannot create directory '$dir' with $mode mode.");
        }
        file_put_contents($compiledFile, $content);

        $this->saveHashToCache($file, $hash);
        return $this->getCompiledFilePath($hash);
    }

    /**
     * Tries to load compiled file from cache.
     * @param string $file the view file name.
     * @return mixed string generated hash for compiled file name or
     * false if did not found in cache.
     */
    protected function loadHashFromCache($file)
    {
        if ($this->cacheComponent === false) {
            return false;
        }

        if (false !== $hash = $this->cacheComponent->get($this->getCacheKey($file))) {
            return file_exists($this->getCompiledFilePath($hash)) ? $hash : false;
        }
        return false;
    }

    /**
     * Tries to save compiled hash to cache.
     * @param string $file the view file.
     * @param string $hash generated hash for compiled file name that will save.
     * @return boolean whether the value is successfully stored into cache.
     */
    protected function saveHashToCache($file, $hash)
    {
        if ($this->cacheComponent === false) {
            return false;
        }

        $dependency = $this->cacheDependencyConfig;
        if (!is_object($dependency) && $dependency !== null) {
            $dependency = Yii::createObject($dependency);
        }
        if ($dependency instanceof YiiFileDependency) {
            $dependency->fileName = $file;
        }
        if ($dependency instanceof FileDependency) {
            $dependency->compiledFileName = $this->getCompiledFilePath($hash);
        }

        return $this->cacheComponent->set($this->getCacheKey($file), $hash, 0, $dependency);
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
     * @param string $hash generated hash for file.
     * @return string the full path to compiled file.
     */
    public function getCompiledFilePath($hash)
    {
        return rtrim(Yii::getAlias($this->compiledPath), '\/').'/'.substr($hash, 0, 2).'/'.substr($hash, 2).'.php';
    }
}
