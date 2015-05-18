<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 23/03/15
 * Time: 16:46
 */

namespace Smallable\Logistics\MorinBundle\Writer;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Smallable\Logistics\MorinBundle\XmlObject\File;

class TextWriter
{

    private $text;
    private $iLineIndex;
    private $filePath;
    private $fileName;


    function __construct($oContainer, File $oFile)
    {
        $fs = new Filesystem();
        $tmpDir = ($oContainer->get('kernel')->getRootDir() . $oContainer->getParameter('morin_directories')['tmp']) . $oFile->getInterfaceName() . '/';

        if (!$fs->exists($tmpDir)) {
            $fs->mkdir($tmpDir);
        }

        $fileName = $oFile->getInterfaceName() . date('YmdHis') . '.txt';
        $this->fileName = $fileName;
        $this->filePath = $tmpDir . $fileName;
        $this->iLineIndex = null;
        $this->text = array();
    }


    public function write($aData)
    {
        if ($aData['line'] !== $this->iLineIndex) {
            $line = str_repeat(' ', (int)$aData['field']->getLine()->getFile()->getlength() - 1);
            $this->iLineIndex = $aData['line'];
            $this->text[] = $line;
        }

        if($aData['field']->getName() == 'lineNumber') {
            $aData['value'] = sprintf('%0' .$aData['field']->getLength(). 's', $this->iLineIndex + 1);
        }

        if (is_a($aData['value'], 'DateTime')) {

            if ($aData['field']->getType() == 'date') {
                $aData['value'] = $aData['value']->format('YMD');
            }
            if ($aData['field']->getType() == 'hour') {
                $aData['value'] = $aData['value']->format('His');
            }
        } else {
            $aData['value'] = strtoupper($aData['value']);

        }

        if ($aData['field']->getPrefix()) {
            $aData['value'] = $aData['field']->getPrefix() . $aData['value'];
        }

        if ($aData['field']->getType() == 'sku') {
            $aData['value'] = $this->SmlbToMorinSkuConvert($aData['value']);
        } else {
            $aData['value'] = $this->stripPunctuation($aData['value']);
        }

        $txt = mb_substr($aData['value'], 0, $aData['field']->getLength());
        if($aData['field']->getAlign() == 'right'){
            $txt = str_pad($txt, $aData['field']->getLength(),' ',STR_PAD_LEFT);
        }else
          $txt = str_pad($txt, $aData['field']->getLength());
        $line = end($this->text);
        $this->text[key($this->text)] = substr_replace($line, $txt, $aData['field']->getPosition() - 1, $aData['field']->getLength());
    }

    public function flush()
    {
        foreach ($this->text as $line) {
            $this->__writeLine($line);
        }
        return array('path' => $this->filePath, 'fileName' => $this->fileName);
    }


    private function __writeLine($line)
    {
        $fp = fopen($this->filePath, 'a');
        fwrite($fp, $line . "\r"."\n");
    }


    public function removeAccents($str)
    {
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'S', 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'y', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'D', 'D', 'D', 'D', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'H', 'H', 'H', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'IJ', 'Ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'OE', 'OE', 'R', 'R', 'R', 'R', 'R', 'R', 'S', 'S', 'S', 'S', 'S', 'S', 'S', 'S', 'T', 't', 'T', 't', 'T', 't', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 'S', 'f', 'O', 'O', 'U', 'U', 'A', 'A', 'I', 'I', 'O', 'O', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'A', 'A', 'AE', 'AE', 'O', 'O', 'Α', 'α', 'Ε', 'ε', 'O', 'O', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
        $str = str_replace($a, $b, $str);
        return Utf8_decode($str);
    }

    public function SmlbToMorinSkuConvert($sku)
    {
        $sku = self::removeAccents($sku);
        return str_replace('-', '/', $sku);
    }

    /*
     * http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page
     */
    public function stripPunctuation($string)
    {
        $string = self::removeAccents($string);
        $urlbrackets = '\[\]\(\)';
        $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
        $urlspaceafter = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
        $urlall = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

        $specialquotes = '\'"\*<>';

        $fullstop = '\x{002E}\x{FE52}\x{FF0E}';
        $comma = '\x{002C}\x{FE50}\x{FF0C}';
        $arabsep = '\x{066B}\x{066C}';
        $numseparators = $fullstop . $comma . $arabsep;

        $numbersign = '\x{0023}\x{FE5F}\x{FF03}';
        $percent = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
        $prime = '\x{2032}\x{2033}\x{2034}\x{2057}';
        $nummodifiers = $numbersign . $percent . $prime;

        return preg_replace(
            array(
                // Remove separator, control, formatting, surrogate,
                // open/close quotes.
                '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
                // Remove other punctuation except special cases
                '/\p{Po}(?<![' . $specialquotes .
                $numseparators . $urlall . $nummodifiers . '])/u',
                // Remove non-URL open/close brackets, except URL brackets.
                '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
                // Remove special quotes, dashes, connectors, number
                // separators, and URL characters followed by a space
                '/[' . $specialquotes . $numseparators . $urlspaceafter .
                '\p{Pd}\p{Pc}]+((?= )|$)/u',
                // Remove special quotes, connectors, and URL characters
                // preceded by a space
                '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
                // Remove dashes preceded by a space, but not followed by a number
                '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
                // Remove consecutive spaces
                '/ +/',
            ),
            ' ',
            $string);
    }
}