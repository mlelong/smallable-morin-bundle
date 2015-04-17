<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 19/03/15
 * Time: 16:59
 */

namespace Smallable\Logistics\MorinBundle\XmlObject;


class Line {

    private $fields;
    private $file;
    private $type;


    /**
     * @param mixed $field
     */

    public function addField(Field $field)
    {
        $this->fields[] = $field;
    }

    /**
     * @return mixed
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
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



}