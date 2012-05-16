<?php
namespace LanguageDetect\Storage;
    /**
     * Created by JetBrains PhpStorm.
     * User: blacky
     * Date: 08.04.12
     * Time: 14:59
     * To change this template use File | Settings | File Templates.
     */
//TODO throw custom exceptions
class Mongo implements StorageInterface
{

    /**
     * @var \MongoDB
     */
    private $_db;

    /**
     * @param string $dbName
     * @param string $host
     * @param string $username
     * @param string $password
     */
    public function __construct($dbName = 'ngrams', $host = 'localhost', $username = '', $password = '')
    {
        if ($username !== '') {
            $mongo = new \Mongo("mongodb://${username}:${password}@${host}");
        }
        else {
            $mongo = new \Mongo("mongodb://${host}");
        }
        $this->_db = $mongo->selectDB($dbName);
    }

    /**
     * Save given ngrams in mongodb
     *
     * @param array  $array       Array of ngrams with parasm to save
     * @param string $storageName Language name
     */
    public function saveToStorage(&$array, $storageName)
    {
        $collection = $this->_db->selectCollection($storageName);
        $collection->ensureIndex(array('ngram'=> 1), array('unique'=> 1));
        $collection->batchInsert($array);
    }

    /**
     * @return array
     */
    public function getAvailable()
    {
        $available = array();
        $collections = $this->_db->listCollections();
        foreach ($collections as $collection) {
            $available[] = $collection->getName();
        }

        return $available;
    }

    public function findInStorage($ngramsArray, $storageName)
    {
        $available = $this->getAvailable();
        if (!in_array($storageName,$available)) {
            //TODO throw custom exceptions
            throw new \Exception("Language '${storageName}' not available");
        }
        $results = array();
        $collection = $this->_db->selectCollection($storageName);
        $cursor = $collection->find(array('ngram' => array('$in' => $ngramsArray)));
        foreach ($cursor as $entry) {
         $results[$entry['ngram']] = array(
             'ngram'             => $entry['ngram'],
             'count'             => $entry['count'],
             'rank'              => $entry['rank'],
             'frequency'         => $entry['frequency'],
             'overall_frequency' => $entry['overall_frequency'],
         );
        }
        return $results;
    }
}