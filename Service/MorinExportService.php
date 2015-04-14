<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 19/03/15
 * Time: 14:50
 */

namespace Smallable\Logistics\MorinBundle\Service;


use Smallable\Logistics\MorinBundle\Resources\Normalizer\MorinMapNormalizer;
use Smallable\Logistics\MorinBundle\Serializer\MorinMapDecoder;
use Smallable\Logistics\MorinBundle\Writer\TextWriter;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

class MorinExportService
{

    protected $oContainer;
    protected $fileName;
    protected $fileMap;
    protected $oWriter;

    function __construct($Container)
    {
        $this->oContainer = $Container;
    }

    public function process()
    {
        $this->init();
        $data = $this->fetchData();
        $aTransformedData = $this->transformData($data);
        $this->writeData($aTransformedData);
        $this->terminate($data);
    }

    protected function init()
    {
        $fileLocator = new FileLocator($this->oContainer->get('kernel'));
        $path = $fileLocator->locate('@MorinBundle/Resources/xml/Morin' . $this->fileName . '.xml');
        $data = file_get_contents($path);
        $encoders = array(new XmlEncoder());
        $normalizers = array(new MorinMapNormalizer());
        $serializer = new Serializer($normalizers, $encoders);
        $this->fileMap = $serializer->deserialize($data, 'Smallable\Logistics\MorinBundle\XmlObject\File', 'xml');

        if ($this->fileMap->getType() == 'raw') {
            $this->oWriter = new TextWriter($this->oContainer, $this->fileMap);
        }

    }


    protected function writeData($aData)
    {
        foreach ($aData as $value) {
            $this->oWriter->write($value);
        }
        return $this->oWriter->flush();
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

    protected function transformField($object, $oField)
    {
        $value = '';

        if ($oField->getObject()) {
            if ($oField->getObject() == 'Product') {
                $object = $object->getProduct();
            }
        }


        if ($oField->getIsMethod()) {
            $value = $this->dispatch($oField->getIsMethod(), $object);
        } elseif ($oField->getSource()) {
            if (is_array($oField->getSource())) {
                $value = $object;
                foreach ($oField->getSource() as $attribute) {
                    $methodName = 'get' . ucfirst($attribute);
                    $value = $value->$methodName();
                }

            } else {
                $methodName = 'get' . ucfirst($oField->getSource());
                $value = $object->$methodName();
            }
        } elseif ($oField->getDefaultValue())
            $value = $oField->getDefaultValue();

        if (is_object($value) && ! ($value instanceof \DateTime) )
            $value = (String)$value;
        if ($oField->getType() == 'float') {
            $value = number_format($value, 1);
        }
        elseif ($oField->getType() == 'integer') {
            $value = round($value);
        }
        elseif ($oField->getType() == 'date') {
            $value = $value->format('Ymd');
        }


        return array('field' => $oField, 'value' => $value);
    }
} 