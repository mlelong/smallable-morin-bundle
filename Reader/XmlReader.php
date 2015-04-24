<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 25/03/15
 * Time: 18:24
 */

namespace Smallable\Logistics\MorinBundle\Reader;


class XmlReader
{

    public function read($filePath)
    {

        $content = file_get_contents($filePath);
        return new \SimpleXMLElement($content);
    }
}