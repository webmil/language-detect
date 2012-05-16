<?php
namespace LanguageDetect\Detector;
use LanguageDetect\Storage;
use LanguageDetect\Generator;

/**
 * Created by JetBrains PhpStorm.
 * User: blacky
 * Date: 08.04.12
 * Time: 17:01
 * To change this template use File | Settings | File Templates.
 */
class BaseDetector
{
    /**
     * @var \LanguageDetect\Storage\StorageInterface
     */
    private $_storage;

    private $_totalSearchNgrams;

    /**
     * @param \LanguageDetect\Storage\StorageInterface $storage
     */
    public function __construct(Storage\StorageInterface $storage)
    {
        $this->_storage = $storage;
    }

    /**
     * @param     $text
     * @param int $ngramSize
     *
     * @return array
     */
    public function detect($text, $ngramSize = 3)
    {
        $rez = array();
        $generator = new Generator\NgramGenerator($ngramSize);
        $ngrams = $generator->createBasicFromStrig($text)->getNgrams();
        $this->_totalSearchNgrams = $generator->getTotalNgramsCount();
        $availableLanguages = $this->_storage->getAvailable();
        foreach ($availableLanguages as $language) {
            $fromDb = $this->_storage->findInStorage(array_keys($ngrams), $language);
            $rez[$language] = $this->_countFrequencies($fromDb, $ngrams);
        }

        return $rez;
    }

    /**
     * @param      $text
     * @param int  $ngramSize
     * @param bool $useOverallFrequency
     *
     * @return int|string
     */
    public function getTopLanguage($text, $ngramSize = 3, $useOverallFrequency = false)
    {
        $field = $useOverallFrequency ? 'overall_frequency' : 'frequency';
        $detected = $this->detect($text, $ngramSize);

        $results = array(
            'language' => 'unknown',
            'score' => 1
        );
        $max = 0;
        foreach ($detected as $language => $values) {
            if ($values[$field] > $max) {
                $max = $values[$field];
                $results['score'] = $max;
                $results['language'] = $language;
            }
        }

        return $results;
    }

    /**
     * @param $dbNgrams
     * @param $searchNgrams
     *
     * @return array
     */
    private function _countFrequencies(&$dbNgrams, &$searchNgrams)
    {
        $totalFrequencies = array(
            'frequency'         => 0.0,
            'overall_frequency' => 0.0,
        );

        foreach ($dbNgrams as $ngram) {
            $searchNcount = $searchNgrams[$ngram['ngram']];

            $totalFrequencies['frequency'] += ($ngram['frequency'] * $searchNcount);
            $totalFrequencies['overall_frequency'] += ($ngram['overall_frequency'] * $searchNcount);
        }
        $this->_normalizeScore($totalFrequencies['frequency']);
        $this->_normalizeScore($totalFrequencies['overall_frequency']);
        return $totalFrequencies;
        //        foreach ($searchNgrams as $ngram => $count) {
        //            if (isset($dbNgrams[$ngram])) {
        //                $frequency = $dbNgrams[$ngram]['frequency'];
        //                $overall_frequency = $dbNgrams[$ngram]['overall_frequency'];
        //            }
        //            else {
        //                $frequency = -(0.1);
        //                $overall_frequency = -(0.1);
        //                //                $frequency = 0;$overall_frequency=0;
        //            }
        //            $totalFrequencies['frequency'] += ($frequency);// * $count);
        //            $totalFrequencies['overall_frequency'] += ($overall_frequency); // * $count);
        //            if ($totalFrequencies['frequency'] < 0) {
        //                $totalFrequencies['frequency'] = 0;
        //            }
        //
        //            if ($totalFrequencies['overall_frequency'] < 0) {
        //                $totalFrequencies['overall_frequency'] = 0;
        //            }
        //        }
        //        $this->_normalizeScore($totalFrequencies['frequency']);
        //        $this->_normalizeScore($totalFrequencies['overall_frequency']);
        //        return $totalFrequencies;
    }

    /**
     * @param $score
     */
    private function _normalizeScore(&$score)
    {
        $maxAvailableScore = $this->_totalSearchNgrams * 2;
        if ($maxAvailableScore == 0) {
            return false;
        }
        $score = $score / $maxAvailableScore;
    }
}