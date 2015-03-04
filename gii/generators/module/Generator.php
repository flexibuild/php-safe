<?php

namespace flexibuild\phpsafe\gii\generators\module;

use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;

class Generator extends \yii\gii\generators\module\Generator
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
