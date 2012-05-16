<?php
namespace LanguageDetect\Generator;
use LanguageDetect\Storage;
/**
 * Created by JetBrains PhpStorm.
 * User: blacky
 * Date: 06.04.12
 * Time: 11:51
 * To change this template use File | Settings | File Templates.
 */
class Learner
{
    private $_ngramSize;
    private $_finalNgrams = array();
    private $_generatorClasses = array();
    private $_normalizationMaxScore;
    private $_totalNgrams;

    /**
     * @param int $size n-gram size
     */
    public function __construct($size = 3)
    {
        $this->_ngramSize = $size;
    }

    /**
     * Array format:
     * ...
     * 'language_name' => 'path/to/file'
     * ...
     *
     * If you need to add new language to set, without rebuilding all other
     * specify previous values for totalNgrams and normalizationMaxScore for correct counting overall frequency
     *
     * @param array   $config
     */
    public function learnFromFiles(array $config)
    {
        $this->_checkConfig($config);
        $totalNgrams = 0;
        $maxScore = 0.0;
        //create n-grams
        foreach ($config as $language => $filename) {
            //TODO add exception if no file exists
            $this->_generatorClasses[$language] = new NgramGenerator($this->_ngramSize);
            $this->_generatorClasses[$language]->readFromFile($filename);
            $totalNgrams += $this->_generatorClasses[$language]->getTotalNgramsCount();
            if ($maxScore < $this->_generatorClasses[$language]->getMaxScoreBeforeNormalization()) {
                $maxScore = $this->_generatorClasses[$language]->getMaxScoreBeforeNormalization();
            }
        }
        $this->_totalNgrams = $totalNgrams;
        $this->_normalizationMaxScore = $maxScore;

        $this->_normalizeGlobal();
        //        echo $totalNgrams."\t".$maxScore."\n";
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $rezArray = array();
        foreach ($this->_generatorClasses as $language => $ngramClass) {
            $rezArray[$language] = $ngramClass->getNgramsWithFrequency();
        }

        return $rezArray;
    }

    public function saveTo(Storage\StorageInterface $storage)
    {
        foreach($this->_generatorClasses as $language => $ngramClass) {
            $storage->saveToStorage($ngramClass->getNgramsWithFrequency(), $language);
            unset($this->_generatorClasses[$language]);
        }
    }

    /**
     * You must store this value if you need to add new languages, without rebuild all set
     *
     * @return float
     */
    public function getNormalizationMaxScore()
    {
        return $this->_normalizationMaxScore;
    }

    /**
     * You must store this value if you need to add new languages, without rebuild all set
     *
     * @return int
     */
    public function getTotalNgrams()
    {
        return $this->_totalNgrams;
    }

    private function _checkConfig($config)
    {
        foreach ($config as $language => $path) {
            if (!file_exists($path)) {
                //TODO throw custom exception
                throw new \Exception('File not exists: '.$path);
            }
        }
    }

    /**
     * Do overall normalization and frequency count
     */
    private function _normalizeGlobal()
    {
        foreach ($this->_generatorClasses as $language=> $ngramClass) {
            $this->_generatorClasses[$language]->addOveralFrequency($this->_totalNgrams);
            $this->_generatorClasses[$language]->normalizeOverallFrequency($this->_normalizationMaxScore);
        }
    }
}
