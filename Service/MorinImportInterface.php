<?php
/**
 * Created by PhpStorm.
 * User: m.lelong
 * Date: 21/04/15
 * Time: 17:50
 */

namespace Smallable\Logistics\MorinBundle\Service;


interface MorinImportInterface {

    public function process();
    public function init();
    public function getFiles();
    public function fetchData($aFiles);
    public function updateData($aData);
    public function moveFiles($aData);
    public function terminate($aData, $aFiles);

}