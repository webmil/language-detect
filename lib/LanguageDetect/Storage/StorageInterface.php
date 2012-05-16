<?php
namespace LanguageDetect\Storage;

/**
 * Created by JetBrains PhpStorm.
 * User: blacky
 * Date: 08.04.12
 * Time: 15:13
 * To change this template use File | Settings | File Templates.
 */
interface StorageInterface
{
    /**
     * @abstract
     *
     * @param array  $array       Array of items to save
     * @param string $storageName Language name
     */
    public function saveToStorage(&$array, $storageName);

    /**
     * @abstract
     * return array of aviliable langiages in storage
     */
    public function getAvailable();

    public function findInStorage($ngramsArray, $storageName);
}
