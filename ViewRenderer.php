<?php

namespace flexibuild\phpsafe;

use Yii;
use yii\base\ViewRenderer as BaseViewRenderer;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\caching\Cache;
use yii\caching\FileDependency;

class ViewRenderer extends BaseViewRenderer
{
    /**
     * @var string the directory or path alias pointing to where Php-Safe engine
     * compiled files will be stored.
     */
    public $compiledPath = '@runtime/PhpSafe/compiled';

    /**
     * @var integer make directory that will be passed into [[FileHelper::createDirectory()]].
     */
    public $mkDirMode = 0755;

    /**
     * @var string the name of Yii application cache component for caching 
     * rendered files.
     */
    public $cacheComponent = 'cache';

    /**
     * @var string the prefix of cache key for file.
     */
    public $cacheKeyPrefix = 'phpsafe_';

    /**
     * @var array key => value pairs that will be passed as php-safe compiler config.
     */
    public $compilerConfig = [];

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
     * @throws InvalidConfigException if cacheComponent is incorrect.
     */
    protected function loadContentFromCache($file)
    {
        if (!$this->cacheComponent || !($cacheComponent = Yii::$app->get($this->cacheComponent))) {
            return false;
        }
        if (!$cacheComponent instanceof Cache) {
            throw new InvalidConfigException(__CLASS__.'::$cacheComponent param must contain the name of the Yii application component that is instance of '.Cache::className().'.');
        }

        if (false !== $compiledFile = $cacheComponent->get($this->getCacheKey($file))) {
            return file_exists($this->getCompiledFilePath($compiledFile)) ? $compiledFile : false;
        }
        return false;
    }

    /**
     * Tries to save compiled code to cache.
     * @param string $file the view file.
     * @param string $compiledFileName compiled file name that will save.
     * @return boolean whether the value is successfully stored into cache.
     * @throws InvalidConfigException if cacheComponent is incorrect.
     */
    protected function saveContentToCache($file, $compiledFileName)
    {
        if (!$this->cacheComponent || !($cacheComponent = Yii::$app->get($this->cacheComponent))) {
            return false;
        }
        if (!$cacheComponent instanceof Cache) {
            throw new InvalidConfigException(__CLASS__.'::$cacheComponent param must contain the name of the Yii application component that is instance of '.Cache::className().'.');
        }

        return $cacheComponent->set($this->getCacheKey($file), $compiledFileName, 0, new FileDependency([
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
