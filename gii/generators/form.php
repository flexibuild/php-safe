<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator flexibuild\phpsafe\gii\generators\GeneratorWithPhpsafeExtTrait */

$class = new ReflectionClass($generator);
while (!file_exists($parentFormView = dirname($class->getFileName()).'/form.php')) {
    if (!($class = $class->getParentClass())) {
        throw new \RuntimeException('Unknown error occured when searching form.php file. One of the parent generator classes must have form.php view file.');
    }
}
echo $this->renderFile($parentFormView, [
    'generator' => $generator,
    'form' => $form,
]);

echo $form->field($generator, 'phpsafeExt');
