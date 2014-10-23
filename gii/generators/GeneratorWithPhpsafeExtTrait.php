<?php

namespace flexibuild\phpsafe\gii\generators;

use flexibuild\phpsafe\gii\PhpsafeCodeFile;

trait GeneratorWithPhpsafeExtTrait
{
    /**
     * @var string extension of phpsafe files.
     */
    public $phpsafeExt = 'sphp';

    /**
     * @param \yii\gii\CodeFile $file generated file.
     * @return boolean whether current file must be generated as phpsafe file or not.
     */
    abstract protected function isPhpSafe($file);

    /**
     * @inheritdoc
     */
    public function generate()
    {
        /* @var $files \yii\gii\CodeFile[] */
        $files = parent::generate();
        foreach ($files as $ind => $file) {
            if ($this->isPhpSafe($file)) {
                $files[$ind] = new PhpsafeCodeFile(
                    dirname($file->path) . '/'.  pathinfo($file->path, PATHINFO_FILENAME) . ".$this->phpsafeExt",
                    $file->content
                );
            }
        }
        return $files;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['phpsafeExt'], 'filter', 'filter' => 'trim'],
            [['phpsafeExt'], 'required'],
            [['phpsafeExt'], 'match', 'pattern' => '/^[a-z][a-z0-9-_]*$/', 'message' => 'Only a-z, 0-9, dashes (-) and underscope (_) are allowed.'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'phpsafeExt' => 'PhpSafe files extension',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), [
            'phpsafeExt',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'phpsafeExt' => 'This is extension that will be used for generating phpsafe files.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function formView()
    {
        return __DIR__.'/form.php';
    }
}
