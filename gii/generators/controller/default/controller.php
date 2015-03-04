<?php
/**
 * This is the template for generating a controller class file.
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator flexibuild\phpsafe\gii\generators\controller\Generator */

echo "<?php\n";
?>

<?php if (!empty($generator->ns)): ?>
namespace <?= $generator->ns ?>;
<?php endif; ?>
namespace <?= $generator->controllerNamespace ?>;

class <?= StringHelper::basename($generator->controllerClass) ?> extends <?= '\\' . trim($generator->baseClass, '\\') . "\n" ?>
{
<?php foreach ($generator->actionIDs as $action): ?>
    public function action<?= Inflector::id2camel($action) ?>()
    {
        return $this->render('<?= $action ?>.<?= $generator->phpsafeExt ?>');
    }

<?php endforeach; ?>
}
