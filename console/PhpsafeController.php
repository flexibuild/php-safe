<?php

namespace flexibuild\phpsafe\console;

use Yii;
use yii\caching\Cache;
use yii\console\Controller;

use flexibuild\phpsafe\helpers\FileHelper;
use flexibuild\phpsafe\ViewRenderer;

/**
 * Allows you to flush all compiled files or compile all phpsafe views.
 * 
 * @property-read ViewRenderer[] $phpsafeRenderers all phpsafe renderers.
 * 
 * @author SeynovAM <sejnovalexey@gmail.com>
 */
class PhpsafeController extends Controller
{
    /**
     * Compiles all phpsafe files to php files.
     * @param bool $recompile Whether method must recompile file even if it has been already compiled.
     * @param array $except Array of ignored patterns. This value will be passed to except option of `yii\helpers\FileHelper::findFiles()`.
     */
    public function actionCompileAll($recompile = false, array $except = ['/vendor/'])
    {
        $dir = rtrim(Yii::getAlias('@app'), '\/');
        foreach ($this->getPhpsafeRenderers() as $ext => $renderer) {
            $this->stdout("Search all '.$ext' files in '$dir'...\n");
            $files = FileHelper::findFiles($dir, [
                'only' => ["*.$ext"],
                'except' => $except,
            ]);

            $this->stdout(Yii::$app->i18n->format("There {n, plural, =0{are no files} =1{is one file} other{are # files}} in '$dir'.\n", [
                'n' => count($files),
            ], 'en-US'));

            foreach ($files as $file) {
                $this->stdout("Compiling file $file.\n");
                $compiledFilePath = $renderer->compileFile($file, !$recompile);
                $this->stdout("Compiled to '$compiledFilePath'.\n");
            }
        }
        $this->stdout("Done.\n");
    }

    /**
     * Flushes all compiled by phpsafe engine files.
     */
    public function actionFlushAll()
    {
        foreach ($this->getPhpsafeRenderers() as $ext => $renderer) {
            $cacheComponent = $renderer->cacheComponent;
            if ($cacheComponent instanceof Cache) {
                if ($this->confirm("Are you want to flush cache component for '$ext' renderer?", true)) {
                    $this->stdout("Flushing cache component for '$ext' renderer.\n");
                    $cacheComponent->flush();
                }
            }
            $this->unlinkAllCompiledFiles($renderer);
        }
        $this->stdout("Done.\n");
    }

    /**
     * Removes all compiled files in renderer path.
     * @param ViewRenderer $renderer
     */
    protected function unlinkAllCompiledFiles($renderer)
    {
        if (!$this->confirm("Are you want to unlink all php files in '$renderer->compiledPath'?", true)) {
            return;
        }

        $this->stdout("Search all php files in '$renderer->compiledPath'...\n");
        $dir = Yii::getAlias($renderer->compiledPath);
        $files = FileHelper::findFiles($dir, ['only' => ['/*/*.php']]);

        $this->stdout(Yii::$app->i18n->format("There {n, plural, =0{are no files} =1{is one file} other{are # files}} in '$renderer->compiledPath'.\n", [
            'n' => count($files),
        ], 'en-US'));

        foreach ($files as $file) {
            $this->stdout("Unlink $file.\n");
            @unlink($file);
            FileHelper::removeDirIfEmpty(dirname($file));
        }
    }

    /**
     * Finds and returns all founded phpsafe renderers.
     * @return ViewRenderer[] founded renderers.
     * @staticvar ViewRenderer[] cached founded renderers for optimizing.
     */
    public function getPhpsafeRenderers()
    {
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        $result = [];
        foreach (Yii::$app->getView()->renderers as $ext => $renderer) {
            if (is_array($renderer) || is_string($renderer)) {
                $renderer = Yii::createObject($renderer);
            }
            if ($renderer instanceof ViewRenderer) {
                $result[$ext] = $renderer;
            }
        }
        return $result;
    }
}
