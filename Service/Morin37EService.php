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


class Morin37EService extends MorinExportService implements MorinExportInterface
{

    protected $fileName;
    protected $fileMap;
    protected $oContainer;
    protected $oWriter;


    public function init()
    {
        $this->fileName = '37E';
        parent::init();
    }

    public function fetchData()
    {
        return $this->oContainer->get('doctrine')->getRepository('SmallableProduitProduitBundle:Product')->findBy(array('exportRequest' => true));
    }

    public function transformData($source)
    {
        $aData = array();

        foreach ($source as $oProduct) {
            foreach ($oProduct->getDeclinaisons() as $object) {
                foreach ($this->fileMap->getLines() as $iLineIndex => $lineType) {
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

    protected function dispatch($source, Declinaison $oDeclinaison)
    {

        if ($source == 'description_1') {
            return $oDeclinaison->getRefFrsCrossdesk();
        }
        if ($source == 'ean') {
            return ($oDeclinaison->getEan())?$oDeclinaison->getEan():$oDeclinaison->getRefFrs();
        }
        if ($source == 'description_2') {
            $sReturn = $oDeclinaison->getProduct()->getLang(Lang::DEFAULT_LANG)->getName()
                . ' ' . $oDeclinaison->getProductSize()->getLang(Lang::DEFAULT_LANG)->getName()
                . ' ' .$oDeclinaison->getProduct()->getProductColor()->getLang(Lang::DEFAULT_LANG)->getName();

            return $sReturn;
        }
        if ($source == 'description_3') {
            $sReturn = $oDeclinaison->getRefFrs() .
                ' '. strip_tags($oDeclinaison->getProduct()->getDescriptionShortsForExport(Lang::DEFAULT_LANG)) .
                ' ' . strip_tags($oDeclinaison->getProduct()->getTermesForExport(Lang::DEFAULT_LANG));
            return $sReturn;
        }
        if ($source == 'description_4') {
            $sReturn = $oDeclinaison->getProduct()->getLang(Lang::DEFAULT_LANG)->getName()
                . ' ' . $oDeclinaison->getProductSize()->getLang(Lang::DEFAULT_LANG)->getName()
                . ' ' . $oDeclinaison->getProduct()->getProductColor()->getLang(Lang::DEFAULT_LANG)->getName()
                . ' ' . strip_tags($oDeclinaison->getProduct()->getDescriptionShortsForExport(Lang::DEFAULT_LANG)) .
                ' ' . strip_tags($oDeclinaison->getProduct()->getTermesForExport(Lang::DEFAULT_LANG));
            return $sReturn;
        }
        if ($source == 'updateIndicator') {
            return ($oDeclinaison->getProduct()->getExportedDate() ? 'M' : 'C');
        }

    }

    protected function terminate($aData)
    {
        $aids = array();
        foreach ($aData as $oProduct) $aids[] = $oProduct->getId();
        if (empty($aids))
            return false;
        $query = $this->oContainer->get('doctrine')->getManager()->createQuery("UPDATE Smallable\Produit\ProduitBundle\Entity\Product p SET p.exportRequest = 0 ,
        p.exportedDate = CURRENT_TIMESTAMP() ,  p.exported = 1 WHERE p.id IN (" . implode(",", $aids) . ")");
        $query->execute();

    }


}