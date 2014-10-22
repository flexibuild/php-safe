<?php

namespace console\controllers;

use yii\console\Controller;
use flexibuild\phpsafe\Compiler;

class TestController extends Controller
{
    public function actionRun($filename = 'test.php')
    {
        $file = realpath(__DIR__."/../$filename");
        $compiler = new Compiler(file_get_contents($file), [
            'compilingFilePath' => $file,
        ]);
        file_put_contents(__DIR__.'/../compiled.php', $compiler->getCompiledCode());
    }

    public function actionRender($filename = 'compiled.php')
    {
        echo $this->renderFile(__DIR__."/../$filename");
    }

    public function actionCheck($outCompiled = '/compiled.html', $outPrepared = '/prepared.html')
    {
        $this->actionRun();
        $compiledContent = $this->renderFile(__DIR__.'/../compiled.php');
        $preparedContent = $this->renderFile(__DIR__.'/../prepared.php');

        if ($compiledContent === $preparedContent) {
            $this->stdout("Test OK\n");
        } else {
            $this->stderr("Test failed!\n");
        }

        if ($this->confirm("Save test outputs to '$outCompiled' & '$outPrepared' files?")) {
            file_put_contents($outCompiled, $compiledContent);
            file_put_contents($outPrepared, $preparedContent);
        }
    }
}
