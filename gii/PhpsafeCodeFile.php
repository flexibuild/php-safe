<?php

namespace flexibuild\phpsafe\gii;

use yii\gii\CodeFile;

/**
 * Class implemented for adding highlightind safe php files.
 */
class PhpsafeCodeFile extends CodeFile
{
    public function preview()
    {
        return highlight_string($this->content, true);
    }
}
