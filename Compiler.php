<?php

namespace flexibuild\phpsafe;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\base\InvalidValueException;
use yii\helpers\VarDumper;

/**
 * Compiler of php-safe template engine.
 * Main idea: echo & short echo tags - safe output, print - raw output.
 *
 * @author SeynovAM <sejnovalexey@gmail.com>
 * 
 * @property-read string $code
 * @property-read string $compiledCode compiled from `$code`.
 * @property-read array $tokens tokens parsed from `$code`.
 * 
 * @property-read string $beforeEchoPhpCode code that compiler adds before safe echo.
 * @property-read string $afterEchoPhpCode code that compiler adds after safe echo.
 * 
 * @property string $initHtmlCode compiler adds that code at the beginning of compiled code.
 * It returns code with creating html code by default.
 * 
 * @property-read array|null $linesMap map between source and compiled files.
 * Null will be returned if map was not collected.
 */
class Compiler extends Component
{
    /**
     * @var array
     */
    public $openTagsLexems = [
        T_OPEN_TAG,
        T_OPEN_TAG_WITH_ECHO,
    ];

    /**
     * @var array
     */
    public $echoLexems = [
        T_ECHO,
        T_PRINT,
    ];

    public $unsafeEchoLexems = [
        T_PRINT,
    ];

    /**
     * @var boolean whether compiler will clear comments or not.
     * `!YII_DEBUG` by default.
     */
    public $clearComments /* = defined('YII_DEBUG') ? !YII_DEBUG : false */;

    /**
     * @var boolean whether compiler will add safe mode for eval() or not.
     */
    public $processEval = true;

    /**
     * @var string path to compiling file.
     */
    public $compilingFilePath = 'Unknown';

    /**
     * @var boolean whether need to create map between source and compiled files.
     * YII_DEBUG by default.
     */
    public $collectMap /* = defined('YII_DEBUG') ? YII_DEBUG : false */;

    /**
     * @var string php code for parsing.
     */
    private $_code;

    /**
     * @var string compiled code.
     */
    private $_compiledCode;

    /**
     * @var array parsed tokens.
     */
    private $_tokens;

    /**
     * @var array|null|boolean lines map between source and compiled files.
     */
    private $_linesMap = false;

    /**
     * @var integer keeps last line number in result code (for collecting lines map).
     */
    private $_lastResultLine;

    /**
     * @var integer keeps last line number in source code (for collecting lines map).
     */
    private $_lastSourceLine;

    /**
     * Creates and returns new instance of Compiler.
     * @param string $code
     * @param array $config
     * @return \flexibuild\phpsafe\Compiler
     */
    static public function createFromCode($code, $config = [])
    {
        return new static($code, $config);
    }

    /**
     * Creates compiler object for `$code`.
     * @param string $code
     * @param array $config
     */
    public function __construct($code, $config = [])
    {
        $this->_code = $code;
        $this->clearComments = defined('YII_DEBUG') ? !YII_DEBUG : false;
        $this->collectMap = defined('YII_DEBUG') ? YII_DEBUG : false;
        parent::__construct($config);
    }

    /**
     * @return string php code for parsing.
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return array parsed tokens.
     */
    public function getTokens()
    {
        if ($this->_tokens !== null) {
            return $this->_tokens;
        }

        Yii::beginProfile($token = 'Parse code with token_get_all().', __METHOD__);
        $this->_tokens = token_get_all($this->getCode());
        Yii::endProfile($token, __METHOD__);

        return $this->_tokens;
    }

    /**
     * @return string compiled code.
     */
    public function getCompiledCode()
    {
        if ($this->_compiledCode !== null) {
            return $this->_compiledCode;
        }

        Yii::beginProfile($token = 'Process tokens with phpsafe compiler.', __METHOD__);

        $this->_linesMap = $this->collectMap ? [[0, 0]] : null;
        $this->_lastResultLine = $this->_lastSourceLine = $this->collectMap ? 1 : null;
        $this->_compiledCode = '';

        $this->processTokens();

        Yii::endProfile($token, __METHOD__);

        return $this->_compiledCode;
    }

    /**
     * @return array|null map between source and compiled files.
     * Null will be returned if map was not collected.
     */
    public function getLinesMap()
    {
        if ($this->_linesMap !== false) {
            return $this->_linesMap;
        } elseif (!$this->collectMap) {
            return null;
        } else {
            $this->getCompiledCode();
            return $this->_linesMap;
        }
    }

    /**
     * @param mixed $echoToken
     * @return string code that compiler adds before echo.
     */
    public function getBeforeEchoPhpCode($echoToken)
    {
        /**
         * Note: square brackets are needed for code like:
         * <?php echo 'smth', 'comma', 'separated'; ?>
         */
        $result = $this->tokenInLexems($echoToken, $this->unsafeEchoLexems, true)
            ? 'print('
            : 'print(\yii\helpers\Html::encode(';
        if ($this->tokenHasSameLexem($echoToken, T_ECHO)) {
            $result .= 'implode(\'\', [';
        }
        return $result;
    }

    /**
     * @param mixed $echoToken
     * @return string code that compiler adds after echo.
     */
    public function getAfterEchoPhpCode($echoToken)
    {
        /** Note: new line is needed for heredoc & nowdoc strings.
         * Also it's needed for expressions like <?= 'something' // some comment ?>
         */
        $result = "\n";
        if ($this->tokenHasSameLexem($echoToken, T_ECHO)) {
            $result .= '])'; // close array and implode
        }
        if (!$this->tokenInLexems($echoToken, $this->unsafeEchoLexems, true)) {
            $result .= ')'; // close Html::encode
        }
        $result .= ')'; // close print

        return $result;
    }

    /**
     * Returns true if `$token` has the same lexem as `$lexem`, false otherwise.
     * @param mixed $token
     * @param string|int $lexem
     * @return boolean
     */
    protected function tokenHasSameLexem($token, $lexem)
    {
        if (is_string($lexem)) {
            return $lexem === $token;
        }
        if (is_int($lexem) && is_array($token)) {
            return $lexem === $token[0];
        }
        return false;
    }

    /**
     * Returns true if `$token` is one of the lexem from `$lexems`, false otherwise.
     * @param mixed $token
     * @param array $lexems
     * @return boolean
     */
    protected function tokenInLexems($token, $lexems)
    {
        foreach ((array) $lexems as $lexem) {
            if ($this->tokenHasSameLexem($token, $lexem)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Skips lexems from `$tokens` until it reaches one of the `$lexems`.
     * @param array $tokens php code tokens.
     * @param mixed $lexems array of lexems or one string|int lexem.
     * @param integer $offset
     * @return mixed integer offset on stop or false.
     */
    protected function skipUntilLexems($tokens, $lexems, $offset = 0)
    {
        $lexems = (array) $lexems;
        $count = count($tokens);
        while ($offset++ < $count) {
            if ($this->tokenInLexems($tokens[$offset - 1], $lexems)) {
                break;
            }
        }
        return --$offset < $count ? $offset : false;
    }

    /**
     * Skips lexems from `$tokens` until it reaches one of the close brace.
     * @param array $tokens php code tokens.
     * @param integer $offset
     * @param string $openBrace open brace for possibility skip brackets. Open brace by default.
     * @param string $closeBrace close brace for possibility skip brackets. Close brace by default.
     * @return mixed integer offset on stop or false.
     */
    protected function skipBraces($tokens, $offset = 0, $openBrace = '{', $closeBrace = '}')
    {
        $counter = 0;

        $openLexems = [$openBrace];
        if ($openBrace === '{') {
            $openLexems[] = T_CURLY_OPEN;
            $openLexems[] = T_DOLLAR_OPEN_CURLY_BRACES;
            $openLexems[] = T_STRING_VARNAME;
        }
        $skipUntilLexems = array_merge($openLexems, [$closeBrace]);

        while (false !== $offset = $this->skipUntilLexems($tokens, $skipUntilLexems, $offset)) {
            if ($this->tokenInLexems($tokens[$offset], $openLexems)) {
                ++$counter;
                ++$offset;
            } elseif ($this->tokenHasSameLexem($tokens[$offset], $closeBrace)) {
                --$counter;
                if ($counter === 0) {
                    return $offset;
                } elseif ($counter < 0) {
                    return false;
                }
                ++$offset;
            }
        }
        return false;
    }

    /**
     * Parses tokens.
     */
    protected function processTokens()
    {
        $tokens = $this->getTokens();

        $offset = 0;
        while ((false !== $oldOffset = $offset) && (false !== $offset = $this->skipUntilLexems($tokens, $this->openTagsLexems, $offset))) {
            $this->processTokenToString($tokens, $oldOffset, $offset - 1);

            $offset = $this->skipUntilLexems($tokens, T_CLOSE_TAG, $oldOffset = $offset);
            if ($offset === false) {
                $this->processPhpCodeWithOpenTag(array_slice($tokens, $oldOffset + 1), $tokens[$oldOffset]);
            } else {
                $this->processPhpCodeWithOpenTag(array_slice($tokens, $oldOffset + 1, $offset - 1 - $oldOffset), $tokens[$oldOffset]);

                $closeTag = ' ' . str_replace('%', '?', $tokens[$offset][1]);
                $this->_compiledCode .= $closeTag;
                $this->processLinesMap($closeTag, $tokens[$offset]);

                ++$offset;
            }
        }

        if ($oldOffset !== false) {
            $this->processTokenToString($tokens, $oldOffset);
        }
    }

    /**
     * Parses php code (non-html coode) with open tag.
     * @param array $phpCodeTokens
     * @param array $openTagToken
     * @throws \yii\base\InvalidParamException
     */
    protected function processPhpCodeWithOpenTag($phpCodeTokens, $openTagToken)
    {
        list($openTag, $tagAsStr, $line) = $openTagToken;
        if (!in_array($openTag, $this->openTagsLexems, true)) {
            if (false === $name = @token_name($openTag)) {
                throw new InvalidParamException("Incorrect open tag '$name' for ".__METHOD__.'.');
            } else {
                throw new InvalidParamException("Incorrect open tag '#$openTag' for ".__METHOD__.'.');
            }
        }

        if ($openTag === T_OPEN_TAG_WITH_ECHO) {
            $this->processPhpCodeWithOpenTag(array_merge([
                [T_ECHO, 'echo', $line],
            ], $phpCodeTokens), [T_OPEN_TAG, '<?php ', $line]);
            return;
        }

        $standardOpenTag = '<?php'.ltrim($tagAsStr, '<%?ph');
        $this->processLinesMap($standardOpenTag, $openTagToken);
        $this->_compiledCode .= $standardOpenTag;

        $this->processPhpCode($phpCodeTokens);
    }

    /**
     * Compile php (non-html) code.
     * @param array $phpCodeTokens
     * @throws InvalidValueException
     */
    protected function processPhpCode($phpCodeTokens)
    {
        $offset = 0;
        $entitiesWithBodyLexems = array_merge([
            T_CLASS,
            T_FUNCTION,
            T_INTERFACE,
            T_TRAIT,
        ]);
        $skipUntilLexems = array_merge($this->echoLexems, $this->processEval ? [
            T_EVAL,
        ] : [], $entitiesWithBodyLexems);

        while ((false !== $oldOffset = $offset) && (false !== $offset = $this->skipUntilLexems($phpCodeTokens, $skipUntilLexems, $offset))) {
            $this->processTokenToString($phpCodeTokens, $oldOffset, $offset - 1);

            if ($this->tokenInLexems($phpCodeTokens[$offset], $this->echoLexems)) {
                $this->processEchoExpression($phpCodeTokens, $offset);

            } elseif ($this->processEval && $this->tokenHasSameLexem($phpCodeTokens[$offset], T_EVAL)) {
                $this->processEvals($phpCodeTokens, $offset);

            } elseif ($this->tokenInLexems($phpCodeTokens[$offset], $entitiesWithBodyLexems)) {
                $this->processEntityWithBody($phpCodeTokens, $offset);

            } else {
                throw new InvalidValueException('Unknown internal skip lexems error.');
            }
        }

        if ($oldOffset !== false) {
            $this->processTokenToString($phpCodeTokens, $oldOffset);
        }
    }

    /**
     * Process tokens for replacing echo & print expression.
     * @param array $tokens First token is echo or print.
     * @param integer $offset 0 by default.
     * After process set `$offset` on the first lexem after echo expression.
     * @throws InvalidParamException
     * @throws InvalidValueException
     */
    protected function processEchoExpression($tokens, &$offset = 0)
    {
        if (!isset($tokens[$offset]) || !$this->tokenInLexems($tokens[$offset], $this->echoLexems)) {
            throw new InvalidParamException('Incorrect param $tokens in '.__METHOD__.'.');
        }

        $skipUntilLexemsForEcho = array_merge([
            T_FUNCTION,
            ';',
        ], $this->processEval ? [
            T_EVAL
        ] : []);

        $echoToken = $tokens[$offset];
        $beforeEchoCode = $this->getBeforeEchoPhpCode($echoToken);
        $this->processLinesMap($beforeEchoCode, $echoToken);
        $this->_compiledCode .= $beforeEchoCode;

        $isBreaked = false;
        ++$offset;

        while ((false !== $oldOffset = $offset) && (false !== $offset = $this->skipUntilLexems($tokens, $skipUntilLexemsForEcho, $offset))) {
            $this->processTokenToString($tokens, $oldOffset, $offset - 1);

            if ($this->tokenHasSameLexem($tokens[$offset], ';')) {
                $isBreaked = true;
                $oldOffset = ++$offset + 1;
                break;

            } elseif ($this->processEval && $this->tokenHasSameLexem($tokens[$offset], T_EVAL)) {
                $this->processEvals($tokens, $offset);
                continue;

            } elseif ($this->tokenHasSameLexem($tokens[$offset], T_FUNCTION)) {
                $this->processEntityWithBody($tokens, $offset);

            } else {
                throw new InvalidValueException('Unknown internal skip lexems error.');
            }
        }

        if ($oldOffset !== false) {
            $this->processTokenToString($tokens, $oldOffset, $offset === false ? null : $offset - 1);
        }

        $afterEchoCode = $this->getAfterEchoPhpCode($echoToken);
        if ($isBreaked) {
            $afterEchoCode .= '; ';
        }
        $this->processLinesMap($afterEchoCode);
        $this->_compiledCode .= $afterEchoCode;
    }

    /**
     * Process tokens for skipping functions, classes, interfaces & traits.
     * @param array $tokens First token is on of: function|class|interface|trait.
     * @param integer $offset 0 by default.
     * After process set `$offset` on the first lexem after body expression.
     * @throws InvalidParamException
     */
    protected function processEntityWithBody($tokens, &$offset = 0)
    {
        if (!isset($tokens[$offset]) || !$this->tokenInLexems($tokens[$offset], [T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT])) {
            throw new InvalidParamException('Incorrect param $tokens in '.__METHOD__.'.');
        }

        $oldOffset = $offset;
        if (false === $offset = $this->skipBraces($tokens, $offset)) {
            $this->processTokenToString($tokens, $oldOffset, null);
        } else {
            $this->processTokenToString($tokens, $oldOffset, $offset);
            ++$offset;
        }
    }

    /**
     * Parses php eval expression and returns string code.
     * @param array $phpCodeTokens first token is eval.
     * @param integer $offset 0 by default.
     * After process set `$offset` on the first lexem after eval expression.
     * @throws InvalidParamException
     * @throws \LogicException on parse error.
     */
    protected function processEvals($phpCodeTokens, &$offset = 0)
    {
        if (!isset($phpCodeTokens[$offset]) || !$this->tokenHasSameLexem($phpCodeTokens[$offset], T_EVAL)) {
            throw new InvalidParamException('Incorrect param $phpCodeTokens in '.__METHOD__.'.');
        }

        $beginEvalCode = "eval(ltrim(\\" . ltrim(get_class($this), '\\');
        $beginEvalCode .= "::createFromCode('<?php ' . ";
        $this->processLinesMap($beginEvalCode, $phpCodeTokens[$offset]);
        $this->_compiledCode .= $beginEvalCode;

        if (false === $offset = $this->skipBraces($phpCodeTokens, ($oldOffset = $offset) + 1, '(', ')')) {
            throw new \LogicException("Cannot find open bracket '(' after eval keyword in $this->compilingFilePath on the line: {$phpCodeTokens[$oldOffset][2]}.");
        }

        $lineOffset = $offset + 2;
        while (--$lineOffset > 0 && !is_array($phpCodeTokens[$lineOffset])); // search last token with line number
        $line = $lineOffset > 0 ? $phpCodeTokens[$lineOffset][2] : 0;

        $this->processTokenToString($phpCodeTokens, $oldOffset + 1, $offset);

        $endEvalCode = ', ' . VarDumper::export([
            'processEval' => true,
            'openTagsLexems' => $this->openTagsLexems,
            'echoLexems' => $this->echoLexems,
            'unsafeEchoLexems' => $this->unsafeEchoLexems,
            'clearComments' => $this->clearComments,
            'collectMap' => $this->collectMap,
            'compilingFilePath' => "$this->compilingFilePath($line) : eval()'d code",
        ]);
        $endEvalCode .= ')'; // close createFromCode()
        $endEvalCode .= '->getCompiledCode()';
        $endEvalCode .= ", '<?ph')"; // close ltrim()
        $endEvalCode .= ')'; // close eval()

        $this->_compiledCode .= $endEvalCode;
        $this->processLinesMap($endEvalCode);

        ++$offset;
    }

    /**
     * Concat all lexems from `$tokens` from `$from` to `$to` ofssets.
     * @param array $tokens
     * @param integer $from from first item by default.
     * @param integer $to by default count($tokens).
     */
    protected function processTokenToString($tokens, $from = 0, $to = null)
    {
        if ($to === null) {
            $to = count($tokens) - 1;
        } else {
            $to = min($to, count($tokens) - 1);
        }

        for ($i = max($from, 0); $i <= $to; ++$i) {
            $str = $this->tokenToString($tokens[$i]);
            $this->processLinesMap($str, $tokens[$i]);
            $this->_compiledCode .= $str;
        }
    }

    /**
     * @param mixed $token parsed by token_get_all() function.
     * @return string
     */
    protected function tokenToString($token)
    {
        if (is_string($token)) {
            return $token;
        }

        if ($this->clearComments && $this->isComment($token)) {
            return '';
        }

        // check magic constants
        switch (true) {
            case $this->tokenHasSameLexem($token, T_DIR):
                return var_export(dirname($this->compilingFilePath), true);
            case $this->tokenHasSameLexem($token, T_FILE):
                return var_export($this->compilingFilePath, true);
            case $this->tokenHasSameLexem($token, T_LINE):
                return var_export($token[2], true);
        }

        return $token[1];
    }

    /**
     * @param mixed $token
     * @return bool whether passed `$token` is a php comment or not.
     */
    protected function isComment($token)
    {
        if (is_string($token)) {
            return false;
        }
        switch (true) {
            case $this->tokenHasSameLexem($token, T_COMMENT): // no break
            case defined('T_ML_COMMENT') && $this->tokenHasSameLexem($token, T_ML_COMMENT): // no break
            case defined('T_DOC_COMMENT') && $this->tokenHasSameLexem($token, T_DOC_COMMENT):
                return true;
        }
        return false;
    }

    /**
     * @param string $resultCode
     * @param mixed $token
     */
    protected function processLinesMap($resultCode, $token = null)
    {
        if (!$this->collectMap) {
            return;
        }

        $resultLine = $this->_lastResultLine;
        $this->_lastResultLine += substr_count($resultCode, "\n");

        if (is_string($token)) {
            $sourceLine = $this->_lastSourceLine;
            $this->_lastSourceLine += substr_count($token, "\n");
        } elseif (is_array($token)) {
            $this->_lastSourceLine = $sourceLine = $token[2];
        } else {
            $sourceLine = $this->_lastSourceLine;
        }

        $addLines = [$resultLine, $sourceLine];
        $count = count($this->_linesMap);
        if ($count > 0 && $this->_linesMap[$count - 1] === $addLines) {
            return;
        }

        $this->_linesMap[] = $addLines;
    }
}
