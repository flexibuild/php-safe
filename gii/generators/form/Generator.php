<?php

namespace flexibuild\phpsafe\gii\generators\form;

use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;

class Generator extends \yii\gii\generators\form\Generator
{
    use GeneratorWithPhpsafeExtTrait;

    /**
     * @inheritdoc
     */
    protected function isPhpSafe($file)
    {
        return true;
    }
}
