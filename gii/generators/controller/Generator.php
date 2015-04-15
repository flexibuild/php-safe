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
        $actions = $this->getActionIDs();
        $viewFile = $this->getViewFile(reset($actions) ?: 'index');
        $viewFileDir = FileHelper::normalizePath(dirname($viewFile));
        return FileHelper::normalizePath(dirname($file->path)) === $viewFileDir;
    }
}
