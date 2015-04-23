<?php
/**
 * Created by PhpStorm.
 * User: m.lelong
 * Date: 21/04/15
 * Time: 17:50
 */

namespace Smallable\Logistics\MorinBundle\Service;


use Smallable\Logistics\MorinBundle\Resources\Normalizer\MorinMapNormalizer;
use Smallable\Logistics\MorinBundle\Reader\TextReader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Finder\Finder;

class MorinImportService
{

    protected $oContainer;
    protected $fileName;
    protected $fileMap;
    protected $oReader;

    function __construct($Container)
    {
        $this->oContainer = $Container;
    }

    public function process() {

        $this->init();
        $aFiles = $this->getFiles();
        $aData = $this->fetchData($aFiles);
        $this->updateData($aData);
        $this->moveFiles($aFiles);
        $this->terminate();
    }

    public function init() {

        $fileLocator = new FileLocator($this->oContainer->get('kernel'));
        $path = $fileLocator->locate('@MorinBundle/Resources/xml/Morin' . $this->fileName . '.xml');
        $data = file_get_contents($path);
        $encoders = array(new XmlEncoder());
        $normalizers = array(new MorinMapNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $this->fileMap = $serializer->deserialize($data, 'Smallable\Logistics\MorinBundle\XmlObject\File', 'xml');

        if ($this->fileMap->getType() == 'raw') {
            $this->oReader = new TextReader($this->oContainer, $this->fileMap);
        } else {
            //$this->oReader = new XmlReader($this->oContainer, $this->fileMap);
        }
    }

    public function getFiles() {

        $finder = new Finder();
        $aFiles = $finder->in($this->oContainer->get('kernel')->getRootDir() . $this->oContainer->getParameter('morin_directories')['tmp'])->name('*' . $this->fileMap->getInterfaceName() . '*');
        $aReturn = array();
        foreach ($aFiles as $file) {
            if (is_file($file->getRealPath())) {
                $aReturn[$file->getFilename()] = $file->getRealPath();
            }
        }
        return $aReturn;
    }

    public function moveFiles($aFiles) {
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

    public function terminate() {

    }
}