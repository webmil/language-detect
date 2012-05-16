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

$learner = new Learner();
$arr = array(
    'norwegian' => 'norwegian.txt',
    'italian' => 'italian.txt',
    'danish' => 'danish.txt',
    'german' => 'german.txt',
    'spanish' => 'spanish.txt',
    'french' => 'french.txt',
    'english' => 'english.txt',
    'swedish' => 'swedish.txt',
    'russian' => 'russian.txt',

);
$learner->learnFromFiles($arr);

$storage = new Mongo();
$learner->saveTo($storage);
