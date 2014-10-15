<?php

namespace flexibuild\phpsafe\caching;

use yii\base\InvalidParamException;
use yii\caching\FileDependency as BaseFileDependency;

use flexibuild\phpsafe\helpers\FileHelper;

/**
 * Overrided class of BaseFileDependency. Used for optimizing: remove old
 * and unused compiled php files.
 *
 * @author SeynovAM
 */
class FileDependency extends BaseFileDependency
{
    public $compiledFileName;

    /**
     * @inheritdoc
     */
    public function getHasChanged($cache)
    {
        $result = parent::getHasChanged($cache);
        if ($result && $this->compiledFileName) {
            @unlink($this->compiledFileName);
            FileHelper::removeDirIfEmpty(dirname($this->compiledFileName));
        }
        return $result;
    }
}
