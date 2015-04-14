<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 19/03/15
 * Time: 16:59
 */

namespace Smallable\Logistics\MorinBundle\XmlObject;



class File {

    private $type;
    private $interfaceName;
    private $length;


    private $lines;

    public function setInterfaceName($interfaceName)
    {
        $this->interfaceName = $interfaceName;
    }

    /**
     * @return mixed
     */
    public function getInterfaceName()
    {
        return $this->interfaceName;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $lines
     */
    public function addLine($line)
    {
        $this->lines[] = $line;
    }

    /**
     * @return mixed
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @param mixed $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }






} 