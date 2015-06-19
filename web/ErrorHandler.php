<?php

namespace flexibuild\phpsafe\web;

/**
 * This ErrorHandler will display source file and line without compiled.
 *
 * @author SeynovAM <sejnovalexey@gmail.com>
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    use ErrorHandlerTrait;
}
