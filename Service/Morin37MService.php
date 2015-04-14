<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 25/03/15
 * Time: 14:41
 */

namespace Smallable\Logistics\MorinBundle\Service;

use Smallable\Logistics\MorinBundle\Reader\TextReader;
use Smallable\Logistics\MorinBundle\Resources\Normalizer\MorinMapNormalizer;
use Smallable\Produit\ProduitBundle\Entity\Stock;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;

class Morin37MService
{

    private $fileMap;
    private $fileName;
    private $oReader;

    function __construct($Container)
    {
        $this->oContainer = $Container;
        $this->fileName = '37M';
    }

    public function process($output)
    {
        $this->init();
        $aFiles = $this->findFiles();
        $this->readFiles($aFiles,$output);
        $this->moveFiles($aFiles);
    }

    public function init()
    {
        $fileLocator = new FileLocator($this->oContainer->get('kernel'));
        $path = $fileLocator->locate('@MorinBundle/Resources/xml/Morin' . $this->fileName . '.xml');
        $data = file_get_contents($path);
        $encoders = array(new XmlEncoder());
        $normalizers = array(new MorinMapNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $this->fileMap = $serializer->deserialize($data, 'Smallable\Logistics\MorinBundle\XmlObject\File', 'xml');
        if ($this->fileMap->getType() == 'raw')
            $this->oReader = new TextReader();

    }


    protected function findFiles()
    {
        $finder = new Finder();
        $aFiles = $finder->in($this->oContainer->get('kernel')->getRootDir() . $this->oContainer->getParameter('morin_directories')['tmp'])->name('*' . $this->fileMap->getInterfaceName() . '*');
        foreach ($aFiles as $file) {
            $aReturn[$file->getFilename()] = $file->getRealPath();
        }
        return $aReturn;
    }


    protected function readFiles($aFiles, $output)
    {
        foreach ($aFiles as $filePath)
            $aReadFiles[] = $this->oReader->read($filePath, $this->fileMap);
        $aStockToModify = array();
        $aSku = array();
        $step = 999;
        $offset = 0;
        foreach ($aReadFiles as $aFile) {
            foreach ($aFile as $aLine) {
                $sMerchandiseAllocation = $aLine['merchandise_allocation']['value'];
                if (empty($aLine['merchandise_allocation']['value']))
                    $sMerchandiseAllocation = '_';
                $aStockToModify[$aLine['sku']['value']][$sMerchandiseAllocation] = (int)$aLine['quantity']['value'];
                $aSku[$aLine['sku']['value']] = $aLine['sku']['value'];
            }
        }
        $oProgressBar = new ProgressBar($output, count($aSku));
        $oProgressBar->setMessage('37 M');
        $aSku = array_values($aSku);
        $oStockManager = $this->oContainer->get('sml.stock_manager');
        $oDeclinaisonRepository = $this->oContainer->get('doctrine')->getRepository('SmallableProduitProduitBundle:Declinaison');
        while ($aRefs = array_slice($aSku, $offset, $step)) {
            $aDeclinaisons = $oDeclinaisonRepository->getDeclinaisonsBySkus($aRefs, true);
            foreach ($aDeclinaisons as $oDeclinaison) {
                $oProgressBar->advance();
                $quantityAvailable = 0;
                $quantityBlocked = 0;
                if (!array_key_exists($oDeclinaison->getReference(), $aStockToModify))
                    continue;
                foreach ($aStockToModify[strtoupper($oDeclinaison->getReference())] as $key => $value) {
                    if ($key == 'DI' || $key == 'PR')
                        $quantityAvailable += (int)$value;
                    if ($key == 'BK')
                        $quantityBlocked = (int)$value;
                }
                if (is_null($oDeclinaison->getStock()) || $oDeclinaison->getStock()->getPhysicalQuantity() != $quantityAvailable)
                    $oStockManager->changeQuantity($quantityAvailable, $oDeclinaison, Stock::PHYSICAL_QUANTITY, false, '37M');
                if (is_null($oDeclinaison->getStock()) || $oDeclinaison->getStock()->getWastedQuantity() != $quantityBlocked)
                    $oStockManager->changeQuantity($quantityBlocked, $oDeclinaison, Stock::WASTED_QUANTITY, false, '37M');
                unset($aStockToModify[$oDeclinaison->getReference()]);
            }
            $this->oContainer->get('doctrine')->getManager()->flush();
            $this->oContainer->get('doctrine')->getManager()->clear();
            $offset += $step;
        }
        $oProgressBar->finish();
        $this->oContainer->get('doctrine')->getManager()->flush();
    }

    public function moveFiles($aFiles)
    {
        $fs = new Filesystem();
        $directory = $this->oContainer->get('kernel')->getRootDir() . $this->oContainer->getParameter('morin_directories')['process'] . DIRECTORY_SEPARATOR . date('Y') .
            DIRECTORY_SEPARATOR . date('M') . DIRECTORY_SEPARATOR . $this->fileMap->getInterfaceName();
        if (!$fs->exists($directory)) {
            $fs->mkdir($directory);
        }
        foreach ($aFiles as $fileName => $filePath) {
            if ($fs->exists($filePath)) {
                $fs->copy($filePath, $directory . DIRECTORY_SEPARATOR . $fileName);
                $fs->remove($filePath);
            }
        }
    }

} 