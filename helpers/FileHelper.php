<?php

namespace flexibuild\phpsafe\helpers;

use yii\helpers\FileHelper as YiiFileHelper;

/**
 * Extends yii FileHelper and adds new methods.
 *
 * @author SeynovAM <sejnovalexey@gmail.com>
 */
class FileHelper extends YiiFileHelper
{
    /**
     * Removes directory but only if it is empty.
     * @param string $dir directory that will be checked and removed.
     */
    public static function removeDirIfEmpty($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        if (static::isDirEmpty($dir)) {
            @rmdir($dir);
        }
    }

    /**
     * @param string $dir path to directory.
     * @return boolean whether input dir is empty or not.
     * @throws InvalidParamException if `$dir` is not valid directory.
     */
    public static function isDirEmpty($dir)
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
