<?php

namespace flexibuild\phpsafe\web;

/**
 * ErrorHandlerTrait used for connecting to your web ErrorHandler.
 * After that this error handler will display source file and line without compiled.
 *
 * @author SeynovAM <sejnovalexey@gmail.com>
 */
trait ErrorHandlerTrait
{
    /**
     * Converts php file path to map file path.
     * @param string $file
     * @return string
     */
    protected function phpsafeMapFilePath($file)
    {
        return dirname($file) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . '.map';
    }

    /**
     * @inheritdoc
     */
    public function renderCallStackItem($file, $line, $class, $method, $args, $index)
    {
        if (!$file || !$line || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
            return parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
        }

        $mapFile = $this->phpsafeMapFilePath($file);
        if (!file_exists($mapFile) || !is_file($mapFile)) {
            return parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
        }

        $details = @include($mapFile);
        if (!is_array($details) || !isset($details['source'], $details['map'])) {
            return parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
        }

        $bestLine = null;
        foreach ($details['map'] as $linesMap) {
            list($resultLine, $sourceLine) = $linesMap;
            if ($line >= $resultLine) {
                $bestLine = $sourceLine;
            } else {
                break;
            }
        }

        return $bestLine !== null
            ? parent::renderCallStackItem($details['source'], $bestLine, $class, $method, $args, $index)
            : parent::renderCallStackItem($file, $line, $class, $method, $args, $index);
    }
}
