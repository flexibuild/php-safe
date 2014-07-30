<?php
/* @var $html \flexibuild\phpsafe\Html */
// other comment;
?>
<?php print(\yii\helpers\Html::encode(implode('', [ "Must be GT here: >\n" 
])))?>
<?php print(\yii\helpers\Html::encode(implode('', [ 'LT: <'
])));  'Simple expression without outputing'; print(\yii\helpers\Html::encode(implode('', [ "\n"
])));  print( PHP_EOL 
)?>
<?php print(\yii\helpers\Html::encode(implode('', [ "\nCheck\n", "\ncomma separated\n", 'expressions'
])));  ?>

<?php print(\yii\helpers\Html::encode(implode('', [ implode("\n", [1, '>']) 
])))?>
<?php print(\yii\helpers\Html::encode(implode('', [ "\n".implode("\n", [2, '<'])."\n\n" 
])))?>


<?php print(\yii\helpers\Html::encode(implode('', [ "before> "
])));  print( ('>middle<')." <after - danger \n" 
)?>
<?php print(\yii\helpers\Html::encode(implode('', [ "before> "
])));  print(\yii\helpers\Html::encode(implode('', [('>middle<')." <after\n" 
])))?>


<? if (true): echo ("GT here: >\n") ?>
    Div tag with double non-safe GT:
    <div>
        > <?php print( '>' 
)?>
    </div>
<? else: ?>
<? endif; ?>
<?php print(\yii\helpers\Html::encode(implode('', [print("Bad example, only for testing: <>\n\n")
])))?>

<?php print(\yii\helpers\Html::encode(implode('', ["Test without spaces\n\n"
])))?>

<?php print(\yii\helpers\Html::encode(implode('', [ 'Test', ' echo', ' comma'.' separated', true ? ' expression'."\n" : ''
])));  ?>
<?php print( 'Test print '.PHP_EOL 
)?>
<?php print(("Test print with brackets > \n")
);  print(\yii\helpers\Html::encode(implode('', [("this encoded <>")
])));  print( "\n\n" 
)?>

<?php function () {
    echo "test anonym function";
    echo eval('echo and evals in functions');
    echo eval((('more brackets')));
    echo function () {}; // function and comment in function
}; print(\yii\helpers\Html::encode(implode('', [ "Echo after function\n" 
])))?>

<?php /*
 * 
 * Big comment
 * 
 */ ?>
<?php eval(ltrim(\flexibuild\phpsafe\Compiler::createFromCode('<?php '.('echo "\n\n<this also must be encoded>\n\n";'.' echo 5 + 10;'), [
    'processEval' => true,
    'openTagsLexems' => [
        372,
        373,
    ],
    'echoLexems' => [
        316,
        266,
    ],
    'unsafeEchoLexems' => [
        266,
    ],
    'clearComments' => false,
])->getCompiledCode(), '<?ph')); ?>
<?php eval(ltrim(\flexibuild\phpsafe\Compiler::createFromCode('<?php '.('echo "\n\n"; print (\'<this not encoded>\'); echo "\n\n";'.'?>raw <> html<?php echo "\n";' // test with comment
), [
    'processEval' => true,
    'openTagsLexems' => [
        372,
        373,
    ],
    'echoLexems' => [
        316,
        266,
    ],
    'unsafeEchoLexems' => [
        266,
    ],
    'clearComments' => false,
])->getCompiledCode(), '<?ph')); ?>
<?php print(\yii\helpers\Html::encode(implode('', [eval(ltrim(\flexibuild\phpsafe\Compiler::createFromCode('<?php '.('echo "this encoded <> \n\n"; print ("This not encoded <>"); return "\n\n<this encoded>\n\n";'), [
    'processEval' => true,
    'openTagsLexems' => [
        372,
        373,
    ],
    'echoLexems' => [
        316,
        266,
    ],
    'unsafeEchoLexems' => [
        266,
    ],
    'clearComments' => false,
])->getCompiledCode(), '<?ph'))
])));  print(\yii\helpers\Html::encode(implode('', [ "\n\n" 
])))?>
<?php print(\yii\helpers\Html::encode(implode('', [ "test with comment\n\n" // test comment 
])))?>

<?php print(\yii\helpers\Html::encode(implode('', [ <<<'STR'
LT: <
GT: <
STR
]))); 
?>

<?php print( <<<STR
Non safe GT: >
STR

)?>
<?php print( (<<<STR
Non safe GT: >
STR
)
)?>

<?php print(\yii\helpers\Html::encode(implode('', [ ('Echo with brackets, safe GT: >') 
])))?>


<?php print(($asd = 15) 
)?>

<?php print(\yii\helpers\Html::encode(implode('', [ 'qwre'.'asd>asd', 5+6 # test 
])))?>

    <% echo 'test asp tags' %>

    <%= "\n\n\n test echo asp tag" %>

<? echo 'end (without close ?> tag)';
