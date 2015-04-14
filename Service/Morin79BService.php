<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 19/03/15
 * Time: 14:59
 */

namespace Smallable\Logistics\MorinBundle\Service;


use Smallable\Produit\ProduitBundle\Entity\Declinaison;
use Smallable\Produit\ProduitBundle\Entity\Lang;


class Morin79BService extends MorinExportService implements MorinExportInterface
{

    protected $fileName;
    protected $fileMap;
    protected $oContainer;
    protected $oWriter;


    public function init()
    {
        $this->fileName = '79B';
        parent::init();
    }


    public function fetchData()
    {
        $iCreationStateId = $this->oContainer->get('sml.configuration_manager')->getByName('SUPPLY_ORDER_CREATION_STATE')->getValue();
        $query = $this->oContainer->get('doctrine')->getManager()->createQuery("SELECT SO,SD FROM Smallable\Produit\ProduitBundle\Entity\SupplyOrder SO
        JOIN SO.supplyOrderDetails SD
        JOIN SD.declinaison D
        JOIN D.product P
        WHERE SO.supplyOrderState  = ".$iCreationStateId." AND P.exported = 1 AND SO.exportRequest = 1");
        $aResult = $query->getResult();
        return $aResult;
    }

    public function transformData($source)
    {
        $aData = array();

        foreach ($source as $oSupplyOrder) {
            foreach ($oSupplyOrder->getSupplyOrderDetails() as $iLineIndex => $object) {
                foreach ($this->fileMap->getLines() as  $lineType) {
                    foreach ($lineType->getFields() as $oField) {
                        $aField = $this->transformField($object,$oField);
                        $aField = array_merge($aField,array('line' =>$iLineIndex));
                        $aData[] = $aField;
                    }

                }
            }
        }

        return $aData;
    }


    public function writeData($aData)
    {
        return parent::writeData($aData);
    }

    protected function terminate($aData)
    {

        $aids = array();
        foreach ($aData as $oSupplyOrder) $aids[] = $oSupplyOrder->getId();
        if (empty($aids))
            return false;
        $iTransmissionStateId = $this->oContainer->get('sml.configuration_manager')->getByName('SUPPLY_ORDER_TRANSMISSION_STATE')->getValue();
        $query = $this->oContainer->get('doctrine')->getManager()->createQuery("UPDATE Smallable\Produit\ProduitBundle\Entity\SupplyOrder SO SET SO.supplyOrderState = ".$iTransmissionStateId.",
        SO.exportedDate = CURRENT_TIMESTAMP(),SO.exportRequest = 0 WHERE  SO.id IN (" . implode(",", $aids) . ")");
        $query->execute();

    }


}