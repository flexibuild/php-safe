<?php

namespace flexibuild\phpsafe\gii\generators\module;

use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;
use yii\gii\CodeFile;
use yii\gii\generators\module\Generator as BaseGenerator;

class Generator extends BaseGenerator
{
    use GeneratorWithPhpsafeExtTrait;

    /**
     * @inheritdoc
     * @param \yii\gii\CodeFile $file
     */
    protected function isPhpSafe($file)
    {
        return preg_match('#[\\\\/]views[\\\\/]default[\\\\/]index\.php$#', $file->path);
    }
}
