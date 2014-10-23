<?php

namespace flexibuild\phpsafe\gii\generators\controller;

use flexibuild\phpsafe\gii\CodeFile;
use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;

use yii\helpers\FileHelper;
use yii\gii\generators\controller\Generator as BaseGenerator;

class Generator extends BaseGenerator
{
    use GeneratorWithPhpsafeExtTrait;

    /**
     * @inheritdoc
     * @param \yii\gii\CodeFile $file
     */
    protected function isPhpSafe($file)
    {
        $viewFileDir = FileHelper::normalizePath("{$this->module->viewPath}/$this->controllerID");
        return FileHelper::normalizePath(dirname($file->path)) === $viewFileDir;
    }
}
