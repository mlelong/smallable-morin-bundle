<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 25/03/15
 * Time: 18:24
 */

namespace Smallable\Logistics\MorinBundle\Reader;


class TextReader
{


    public function read($filePath, $fileMap)
    {
        $handle = fopen($filePath, "r");
        $lineIndex = 0;
        $aReturn = array();
        while (($textLine = fgets($handle)) !== false) {

            if (count($fileMap->getLines()))
                $oLine = current($fileMap->getLines());
            else {
                //search Line Type
                //@todo
            }
            foreach ($oLine->getFields() as $oField) {
                $value = substr($textLine, $oField->getPosition()-1, $oField->getLength());
                if($oField->getType() == 'sku')
                    $value = $this->MorinToSkuConvert($value);
                $value = trim($value);
                $aReturn[$lineIndex][$oField->getName()] = array('field' => $oField, 'value' => $value);
            }
            $lineIndex++;

        }
        return $aReturn;
    }

    private function MorinToSkuConvert($sku)
    {
        $sku = trim($sku);
        $sku = substr($sku, 2);
        return str_replace('/', '-', $sku);
    }

}