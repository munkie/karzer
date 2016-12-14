<?php

namespace Karzer\Util;

class Reflection
{

    /**
     * @param object $object
     * @param string $propertyName
     * @param string|null $objectClass
     *
     * @return mixed
     */
    public static function getObjectValue($object, $propertyName, $objectClass = null)
    {
        $property = static::getObjectProperty($object, $propertyName, $objectClass);

        return $property->getValue($object);
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed $value
     * @param string|null $objectClass
     */
    public static function setObjectValue($object, $propertyName, $value, $objectClass = null)
    {
        $property = static::getObjectProperty($object, $propertyName, $objectClass);

        $property->setValue($object, $value);
    }

    /**
     * @param $object
     * @param $propertyName
     * @param null $objectClass
     * @return \ReflectionProperty
     */
    private static function getObjectProperty($object, $propertyName, $objectClass = null)
    {
        $objectClass = $objectClass ?: get_class($object);
        $objectReflection = new \ReflectionClass($objectClass);
        $property = $objectReflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property;
    }
}
