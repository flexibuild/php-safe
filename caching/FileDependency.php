<?php

namespace flexibuild\phpsafe\caching;

use yii\base\InvalidParamException;
use yii\caching\FileDependency as BaseFileDependency;

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
            $this->clearDirIfEmpty(dirname($this->compiledFileName));
        }
        return $result;
    }

    /**
     * Removes directory but only if it is empty.
     * @param string $dir directory that will be checked and removed.
     */
    protected function clearDirIfEmpty($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        if ($this->isDirEmpty($dir)) {
            @rmdir($dir);
        }
    }

    /**
     * @param string $dir path to directory.
     * @return boolean whether input dir is empty or not.
     * @throws InvalidParamException if `$dir` is not valid directory.
     */
    protected function isDirEmpty($dir)
    {
        if (!is_dir($dir)) {
            throw new InvalidParamException("Directory '$dir' is not a valid directory.");
        } elseif (!($handle = @opendir($dir))) {
            return false;
        }

        $isEmpty = true;
        while (false !== $file = readdir($handle)) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $isEmpty = false;
            break;
        }
        closedir($handle);

        return $isEmpty;
    }
}
