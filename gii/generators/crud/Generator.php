<?php

namespace flexibuild\phpsafe\gii\generators\crud;

use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;
use yii\helpers\FileHelper;
use yii\gii\CodeFile;
use yii\gii\generators\crud\Generator as BaseGenerator;

class Generator extends BaseGenerator
{
    use GeneratorWithPhpsafeExtTrait;

    /**
     * @inheritdoc
     * @param \yii\gii\CodeFile $file
     */
    protected function isPhpSafe($file)
    {
        $viewPath = FileHelper::normalizePath($this->getViewPath());
        return FileHelper::normalizePath(dirname($file->path)) === $viewPath;
    }
}
