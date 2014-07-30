<?php
use yii\helpers\Html;
// other comment;
?>
<?= Html::encode("Must be GT here: >\n") ?>
<?= Html::encode('LT: <'); 'Simple expression without outputing'; echo "\n"; print PHP_EOL ?>
<?= "\nCheck\n", "\ncomma separated\n", 'expressions'; ?>

<?php echo Html::encode(implode("\n", [1, '>'])) ?>
<?php echo Html::encode("\n".implode("\n", [2, '<'])."\n\n") ?>


<?php echo Html::encode("before> ").('>middle<').(" <after - danger \n") ?>
<?php echo Html::encode("before> ").Html::encode('>middle<').Html::encode(" <after\n") ?>


<? if (true): echo Html::encode("GT here: >\n") ?>
    Div tag with double non-safe GT:
    <div>
        > <?php echo '>' ?>
    </div>
<? else: ?>
<? endif; ?>
<?=Html::encode(print("Bad example, only for testing: <>\n\n"))?>

<?= "Test without spaces\n\n" ?>

<?php echo 'Test', ' echo', ' comma'.' separated', true ? ' expression'."\n" : ''; ?>
<?php print 'Test print '.PHP_EOL ?>
<?php print("Test print with brackets > \n".Html::encode("this encoded <>")."\n\n") ?>

<?php function () {
    echo "test anonym function";
    echo eval('echo and evals in functions');
    echo eval((('more brackets')));
    echo function () {}; // function and comment in function
}; echo "Echo after function\n" ?>

<?php /*
 * 
 * Big comment
 * 
 */ ?>
<?php eval('echo \yii\helpers\Html::encode("\n\n<this also must be encoded>\n\n");'.' echo 5 + 10;'); ?>
<?php eval('echo ("\n\n".\'<this not encoded>\'."\n\n");'.'?>raw <> html<?php echo "\n";' // test with comment
); ?>
<?php echo Html::encode(eval('echo \yii\helpers\Html::encode("this encoded <> \n\n"); echo ("This not encoded <>"); return "\n\n<this encoded>\n\n";')); echo "\n\n" ?>
<?php echo "test with comment\n\n" // test comment ?>

<?php echo Html::encode(<<<'STR'
LT: <
GT: <
STR
)?>

<?php echo (<<<STR
Non safe GT: >
STR
)?>
<?php print (<<<STR
Non safe GT: >
STR
)?>

<?php echo Html::encode('Echo with brackets, safe GT: >') ?>


<?php print($asd = 15) ?>

<?= Html::encode('qwre'.'asd>asd'), 5+6 # test ?>

    <% echo Html::encode('test asp tags') %>

    <%= Html::encode("\n\n\n test echo asp tag") %>

<? echo Html::encode('end (without close ?> tag)');
