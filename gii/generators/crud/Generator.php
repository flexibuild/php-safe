<?php

namespace flexibuild\phpsafe\gii\generators\crud;

use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;
use yii\helpers\FileHelper;

class Generator extends \yii\gii\generators\crud\Generator
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
