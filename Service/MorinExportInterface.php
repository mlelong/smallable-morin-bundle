<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 23/03/15
 * Time: 15:00
 */

namespace Smallable\Logistics\MorinBundle\Service;


interface MorinExportInterface {

    public function process();
    public function init();
    public function fetchData();
    public function transformData($aData);
    public function writeData($aData);
    public function terminate($aData, $aFile);

}