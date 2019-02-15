<?php

namespace MGDSoft\FixturesGeneratorBundle\Extractor;

use Doctrine\ORM\EntityManager;
use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;

class Property
{
    /** @var EntityManager */
    protected $em;

    /** @var  \ReflectionClass */
    protected $entityReflection;

    /**
     * Property constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param \ReflectionClass $entityReflection
     * @return PropertyDetails[]
     */
    public function getPropertiesFromEntity(\ReflectionClass $entityReflection)
    {
        $this->entityReflection = $entityReflection;
        $classMetadata = $this->em->getClassMetadata($entityReflection->getName());

        $result = [];

        /** @var \ReflectionProperty $property */
        foreach ($classMetadata->fieldMappings as $property) {
            $result[]=$this->createPropertySimpleColumn($property);
        }

        /** @var \ReflectionProperty $property */
        foreach ($classMetadata->associationMappings as $property) {
            $result[]=$this->createPropertyAssociationMapping($property);
        }

        return $result;
    }

    protected function createPropertySimpleColumn(array $property)
    {
        $propertyReflection = $this->getPropertyReflection($property['fieldName']);
        $required           = $this->isRequired($property, $propertyReflection);
        $defaultValue       = $this->getDefaultValue($property);
        $skipSetValue       = $this->isSkipValue($property, $propertyReflection);

        return new PropertyDetails(
            $property['fieldName'],
            $defaultValue,
            $property['type'],
            $required,
            $skipSetValue,
            false,
            $property
        );
    }

    protected function createPropertyAssociationMapping(array $property)
    {
        $propertyReflection = $this->getPropertyReflection($property['fieldName']);
        $required           = $this->isRequiredAssocMapping($property, $propertyReflection);
        $defaultValue       = null;
        $skipSetValue       = false;

        return new PropertyDetails(
            $property['fieldName'],
            $defaultValue,
            $property['type'],
            $required,
            $skipSetValue,
            false,
            $property,
            false,
            $property['targetEntity']
        );
    }


    protected function getDefaultValue($property)
    {
        try{
            return $this->entityReflection->getDefaultProperties()[$property['fieldName']];
        }catch (\ErrorException $e) {
            return $this->entityReflection->getParentClass()->getDefaultProperties()[$property['fieldName']];
        }
    }

    protected function isRequired($property, \ReflectionProperty $propertyReflection)
    {
        return (!isset($property['nullable']) || $property['nullable'] == false) ? true : false;
    }

    protected function isRequiredAssocMapping($property, \ReflectionProperty $propertyReflection)
    {
        if (!$property['isOwningSide']) {
            return false;
        }

        return (isset($property['joinColumns']['nullable']) && $property['joinColumns']['nullable'] == false) ? true : false;
    }

    protected function isSkipValue($property, \ReflectionProperty $propertyReflection)
    {
        return (isset($property['id']) && $property['id']) ? true : false;
    }

    private function getPropertyReflection($fieldName)
    {
        try{
            return $this->entityReflection->getProperty($fieldName);
        }catch (\ReflectionException $e) {
            return $this->entityReflection->getParentClass()->getProperty($fieldName);
        }
    }
}