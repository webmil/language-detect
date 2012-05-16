<?php
use LanguageDetect\Generator\NgramGenerator;
use LanguageDetect\Generator\Learner;
use LanguageDetect\Storage\Mongo;
use LanguageDetect\Detector\BaseDetector;

//create autoloader
function __autoload($class)
{
    $parts = str_replace('\\', '/', $class);
    $fileName = dirname(__FILE__) . "/../lib/" . $parts . '.php';
    if (file_exists($fileName)) {
        require $fileName;
    }
}

$text = <<<EOD
Hallo! Das ist ein Text in deutscher Sprache.
Mal sehen, ob die Klasse erkennt, welche Sprache das hier ist.
EOD;

$storage = new Mongo();
$detector = new BaseDetector($storage);
$detectedLanguage = $detector->getTopLanguage($text);


