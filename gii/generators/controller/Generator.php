<?php

namespace flexibuild\phpsafe\gii\generators\controller;

use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;

use yii\helpers\FileHelper;

class Generator extends \yii\gii\generators\controller\Generator
{
    use GeneratorWithPhpsafeExtTrait;

    /**
     * @inheritdoc
     * @param \yii\gii\CodeFile $file
     */
    protected function isPhpSafe($file)
    {
        $viewFileDir = FileHelper::normalizePath("{$this->viewPath}/$this->controllerID");
        return FileHelper::normalizePath(dirname($file->path)) === $viewFileDir;
    }
}
