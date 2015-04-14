<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 23/03/15
 * Time: 11:21
 */

namespace Smallable\Logistics\MorinBundle\Resources\Normalizer;


use Smallable\Logistics\MorinBundle\XmlObject\Line;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\scalar;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

class MorinMapNormalizer extends SerializerAwareNormalizer implements DenormalizerInterface
{


    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {

        if (is_array($data) || is_object($data) && $data instanceof \ArrayAccess) {
            $normalizedData = $data;
        } elseif (is_object($data)) {
            $normalizedData = array();

            foreach ($data as $attribute => $value) {
                $normalizedData[$attribute] = $value;
            }
        } else {
            $normalizedData = array();
        }

        $reflectionClass = new \ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if ($constructor) {
            $constructorParameters = $constructor->getParameters();

            $params = array();
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = lcfirst($this->formatAttribute($constructorParameter->name));

                if (isset($normalizedData[$paramName])) {
                    $params[] = $normalizedData[$paramName];
                    // don't run set for a parameter passed to the constructor
                    unset($normalizedData[$paramName]);
                } elseif ($constructorParameter->isOptional()) {
                    $params[] = $constructorParameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        'Cannot create an instance of ' . $class .
                        ' from serialized data because its constructor requires ' .
                        'parameter "' . $constructorParameter->name .
                        '" to be present.');
                }
            }

            $object = $reflectionClass->newInstanceArgs($params);
        } else {
            $object = new $class();
        }


        foreach ($normalizedData as $attribute => $value) {

            $setter = 'set' . ucfirst($attribute);

            if (method_exists($object, $setter)) {
                $object->$setter($value);
            }

            if ($attribute == 'line') {

                if (array_key_exists(0, $value)) {
                    foreach ($value as $attribute => $value) {
                        $line = $this->denormalize($value, 'Smallable\Logistics\MorinBundle\XmlObject\Line');
                        $object->addLine($line);
                        $line->setFile($object);
                    }
                } else {
                    $line = $this->denormalize($value, 'Smallable\Logistics\MorinBundle\XmlObject\Line');
                    $object->addLine($line);
                    $line->setFile($object);
                }

            }
            if ($attribute == 'field') {
                foreach ($value as $attribute => $value) {
                    $field = $this->denormalize($value, 'Smallable\Logistics\MorinBundle\XmlObject\Field');
                    $object->addField($field);
                    $field->setLine($object);
                }
            }


        }

        return $object;
    }


    public function supportsDenormalization($data, $type, $format = null)
    {

        return true;
    }

}