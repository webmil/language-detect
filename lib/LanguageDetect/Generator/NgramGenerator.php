<?php
namespace LanguageDetect\Generator;
/**
 * Created by JetBrains PhpStorm.
 * User: blacky
 * Date: 05.04.12
 * Time: 11:01
 * To change this template use File | Settings | File Templates.
 */
class NgramGenerator
{
    private $_ngrams = array();
    private $_totalNgramsCount = 0;
    private $_ngramSize;
    private $_ngramsWithFrequency = array();
    private $_maxScoreBeforeNormalization;

    /**
     * @param int $size
     */
    public function __construct($size = 3)
    {
        mb_internal_encoding('UTF-8');
        $this->_ngramSize = $size;
    }

    /**
     * @param string $filename
     * @param bool   $normalize
     *
     * @return \LanguageDetect\Generator\NgramGenerator
     */
    public function readFromFile($filename, $normalize = true)
    {
        $handle = fopen($filename, "r");
        $this->_ngrams = array();
        while (!feof($handle)) {
            $contents = fread($handle, 8096);
            $ngramsPart = $this->_createNgramPart($contents);
            $this->_totalNgramsCount += count($ngramsPart);
            $this->_finalizeParts($this->_ngrams, $this->_reduce($ngramsPart));
        }
        fclose($handle);
        $this->_sort();
        $this->_removeSingleNgrams();

        $this->_countNgramsFrequency($normalize);

        unset($handle,$contents,$ngramsPart);
        return $this;
    }

    /**
     * @param string $string
     * @param bool   $normalize
     *
     * @return \LanguageDetect\Generator\NgramGenerator
     */
    public function createFromString($string, $normalize = true)
    {
        $ngramsPart = $this->_createNgramPart($string);
        $this->_totalNgramsCount += count($ngramsPart);
        $this->_finalizeParts($this->_ngrams, $this->_reduce($ngramsPart));
        $this->_sort();

        $this->_countNgramsFrequency($normalize);

        return $this;
    }

    /**
     * @param $string
     *
     * @return \LanguageDetect\Generator\NgramGenerator
     */
    public function createBasicFromStrig($string)
    {
        $ngramsPart = $this->_createNgramPart($string);
        $this->_totalNgramsCount += count($ngramsPart);
        $this->_reduce($ngramsPart);
        $this->_finalizeParts($this->_ngrams, $ngramsPart);

        return $this;
    }

    /**
     * adds overall_frequency field to n-grams array
     *
     * @param int $totalNgramsCount total n-grams count in all available languages
     *
     * @return array
     */
    public function getWithOverallFrequency($totalNgramsCount)
    {
        foreach ($this->_ngramsWithFrequency as &$ngram) {
            $ngram['overall_frequency'] = $ngram['count'] / $totalNgramsCount;
        }

        return $this->getNgramsWithFrequency();
    }

    /**
     * @return array
     */
    public function getNgrams()
    {
        return $this->_ngrams;
    }

    /**
     * @return int
     */
    public function getTotalNgramsCount()
    {
        return $this->_totalNgramsCount;
    }

    /**
     * @return array
     */
    public function getNgramsWithFrequency()
    {

        return $this->_ngramsWithFrequency;
    }


    /**
     * @return mixed
     */
    public function getMaxScoreBeforeNormalization()
    {
        return $this->_maxScoreBeforeNormalization;
    }

    /**
     * @param $overallCount
     */
    public function addOveralFrequency($overallCount)
    {
        foreach ($this->_ngramsWithFrequency as $ngram => $values) {
            $this->_ngramsWithFrequency[$ngram]['overall_frequency'] = $values['count'] / $overallCount;

            unset($ngram,$values);
        }
    }

    /**
     * @param $maxFrequency
     */
    public function normalizeOverallFrequency($maxFrequency)
    {

        foreach ($this->_ngramsWithFrequency as $ngram => $values) {
            $this->_ngramsWithFrequency[$ngram]['overall_frequency'] =
                ($this->_ngramsWithFrequency[$ngram]['overall_frequency'] / $maxFrequency) + 1;
            unset($ngram,$values);
        }
    }

    /**
     * @param $text
     *
     * @return array
     */
    private function _createNgramPart(&$text)
    {
        $text = mb_strtolower($text);

        $text = preg_replace(array('/\s{2,}/u', '/[\t\n]/u'), ' ', $text);

        $text = preg_replace(array('/\,|\,|\‘|\“|\„|\*|\$|\"|\_|\\|\(|\)|\/|\d/u'), '', $text);


        $lenTxt = mb_strlen($text) - ($this->_ngramSize - 1);
        $ngrams = array();

        for ($n = 0; $n < $lenTxt; $n++) {
            $current = mb_substr($text, $n, $this->_ngramSize);
            //create ngrams only with at least 1 word character
            if (preg_match('/\p{L}+/u', $current)) {
                $ngrams[] = array(
                    'ngram'   => $current,
                    'count'   => 1);
            }
        }
        return $ngrams;
    }

    /**
     * @return bool
     */
    private function _sort()
    {
        return arsort($this->_ngrams);
    }


    /**
     * @param array $ar1
     * @param array $ar2
     */
    private function _finalizeParts(&$ar1, &$ar2)
    {
        foreach ($ar2 as $key => $value)
        {
            if (isset($ar1[$key])) {
                $ar1[$key] += $value;
            }
            else {
                $ar1[$key] = $value;
            }
        }

    }

    /**
     * @param int $minCount
     */
    private function _removeSingleNgrams($minCount = 1)
    {
        //TODO remove
        $this->_ngrams = array_slice($this->_ngrams, 0, 10000);

        $this->_totalNgramsCount = 0;
        foreach ($this->_ngrams as $key => $value) {
            if ($value <= $minCount) {
                unset($this->_ngrams[$key]);
            }
            else {
                $this->_totalNgramsCount += $value;
            }
        }
    }

    /**
     * @param $array
     *
     * @return array
     */
    private function _reduce(&$array)
    {
        $result = array();
        foreach ($array as $elem) {
            if (!isset($result[$elem['ngram']])) {
                $result[$elem['ngram']] = 0;
            }
            $result[$elem['ngram']] += $elem['count'];
        }
        $array = $result;

        return $array;
    }


    /**
     * @param bool $normalize
     */
    private function _countNgramsFrequency($normalize = true)
    {
        $n = 1;
        reset($this->_ngrams);
        //for normalization
        $maxScore = current($this->_ngrams);
        $max = $maxScore / $this->_totalNgramsCount;
        foreach ($this->_ngrams as $ngram => $count) {
            //frequency with normalization
            $currentCount = $count / $this->_totalNgramsCount;

            if ($normalize === true) {
                $currentCount = ($currentCount / $max) + 1;
            }
            $this->_ngramsWithFrequency[] = array(
                'ngram'     => $ngram,
                'count'     => $count,
                'rank'      => $n++,
                'frequency' => $currentCount
            );
        }

        $this->_maxScoreBeforeNormalization = $max;
    }
}
