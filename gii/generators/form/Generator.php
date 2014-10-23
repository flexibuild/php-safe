<?php

namespace flexibuild\phpsafe\gii\generators\form;

use Yii;
use flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait;
use yii\gii\CodeFile;
use yii\gii\generators\form\Generator as BaseGenerator;

class Generator extends BaseGenerator
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
