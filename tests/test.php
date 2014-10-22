<?php
// other comment;
?>
<?= "Must be GT here: >\n" ?>
<?= 'LT: <'; 'Simple expression without outputing'; echo "\n"; print PHP_EOL ?>
<?= "\nCheck\n", "\ncomma separated\n", 'expressions'; ?>

<?php echo implode("\n", [1, '>']) ?>
<?php echo "\n".implode("\n", [2, '<'])."\n\n" ?>


<?php echo "before> "; print ('>middle<')." <after - danger \n" ?>
<?php echo "before> "; echo('>middle<')." <after\n" ?>


<? if (true): echo ("GT here: >\n") ?>
    Div tag with double non-safe GT:
    <div>
        > <?php print '>' ?>
    </div>
<? else: ?>
<? endif; ?>
<?=print("Bad example, only for testing: <>\n\n")?>

<?="Test without spaces\n\n"?>

<?php echo 'Test', ' echo', ' comma'.' separated', true ? ' expression'."\n" : ''; ?>
<?php print 'Test print '.PHP_EOL ?>
<?php print("Test print with brackets > \n"); echo("this encoded <>"); print "\n\n" ?>

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
<?php eval('echo "\n\n<this also must be encoded>\n\n";'.' echo 5 + 10;'); ?>
<?php eval('echo "\n\n"; print (\'<this not encoded>\'); echo "\n\n";'.'?>raw <> html<?php echo "\n";' // test with comment
); ?>
<?php echo eval('echo "this encoded <> \n\n"; print ("This not encoded <>"); return "\n\n<this encoded>\n\n";'); echo "\n\n" ?>
<?php echo "test with comment\n\n" // test comment ?>

<?php echo <<<'STR'
LT: <
GT: <
STR;
?>

<?php print <<<STR
Non safe GT: >
STR
?>
<?php print (<<<STR
Non safe GT: >
STR
)?>

<?php echo ('Echo with brackets, safe GT: >') ?>


<?php print($asd = 15) ?>

<?= 'qwre'.'asd>asd', 5+6 # test ?>

    <% echo 'test asp tags' %>

    <%= "\n\n\n test echo asp tag" %>

<?php echo __FILE__ ?>
<?php echo __DIR__; ?>
<?php echo __LINE__; ?>
<?php echo __CLASS__; ?>
<?php echo __FUNCTION__; ?>
<?php echo __METHOD__; ?>
<?php echo __TRAIT__; ?>
<?php echo __NAMESPACE__; ?>

<?php 
$nl2br = function ($content) {
    echo nl2br(\yii\helpers\Html::encode($content));
}; ?>

<?php $nl2br('\ntest\ntest\nnl2br\n'); ?>
<?= $nl2br('\ntest\ntest\nnl2br\n'); ?>

<? echo 'end (without close ?> tag)';
