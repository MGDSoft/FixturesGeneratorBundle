<?php

namespace MGDSoft\FixturesGeneratorBundle\Extractor;

use Doctrine\ORM\EntityManagerInterface;
use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Extractor\Entity;
use MGDSoft\FixturesGeneratorBundle\Generator\AbstractFixtureGenerator;
use MGDSoft\FixturesGeneratorBundle\Guesser\Data;


class Property
{
    const NOT_SCALAR_TYPES = ['date', 'datetime', 'datetimetz', 'datetimetz_immutable', 'dateinterval', 'time', 'array', 'simple_array', 'json', 'json_array'];
    protected $em;
    /** @var \ReflectionClass */
    protected $entityReflection;
    protected $callableToAskEntity;
    protected $entityExtractor;
    protected $dataGuesser;

    public function __construct(EntityManagerInterface $em, Entity $entityExtractor, Data $dataGuesser)
    {
        $this->em = $em;
        $this->entityExtractor = $entityExtractor;
        $this->dataGuesser = $dataGuesser;
    }

    public function getPropertiesFromEntity(\ReflectionClass $entityReflection, \Closure $callableToAskEntity = null)
    {
        $this->callableToAskEntity = $callableToAskEntity;
        $this->entityReflection    = $entityReflection;

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
        $propertyReflection = $this->getPropertyReflection($property);
        $required           = $this->isRequired($property, $propertyReflection);
        $defaultValue       = $this->getDefaultValue($property);
        $skipSetValue       = $this->isSkipValue($property, $propertyReflection);

        $type = $this->getType($property, $propertyReflection);
        $fieldName = $property['fieldName'];
        $defaultValueGenerated = $defaultValue;

        if (!$defaultValue) {
            $defaultValueGenerated = $this->dataGuesser->createRandomValueSimple($type, $fieldName);
        }

        return new PropertyDetails(
            $fieldName,
            $defaultValue,
            $defaultValueGenerated,
            $this->getType($property, $propertyReflection),
            $required,
            $skipSetValue,
            false,
            $property,
            $this->isValueScalar($property, $propertyReflection)
        );
    }

    protected function createPropertyAssociationMapping(array $property)
    {
        $propertyReflection = $this->getPropertyReflection($property);
        $required           = $this->isRequiredAssocMapping($property, $propertyReflection);
        $defaultValue       = null;
        $skipSetValue       = false;

        $type = $this->getType($property, $propertyReflection);
        $fieldName = $property['fieldName'];
        $defaultValueGenerated = $defaultValue;

        $targetEntity = $this->getTargetEntity($property);

        $referencePrefix = AbstractFixtureGenerator::getFixtureReferencePrefix(
            (new \ReflectionClass($targetEntity))->getShortName()
        );

        $defaultValueGenerated = '$this->getReference("'.$referencePrefix.'-1")';

        return new PropertyDetails(
            $fieldName,
            $defaultValue,
            $defaultValueGenerated,
            $type,
            $required,
            $skipSetValue,
            false,
            $property,
            false,
            $targetEntity
        );
    }

    protected function getDefaultValue($property)
    {
        $reflectionClass = $this->getReflectionClassFromProperty($property);
        return $reflectionClass->getDefaultProperties()[$property['fieldName']];
    }

    protected function getType($property, \ReflectionProperty $propertyReflection)
    {
        if (isset($property['targetEntity'])) {
            return 'object';
        }
        return $property['type'];
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

        return (isset($property['joinColumns'][0]['nullable']) && $property['joinColumns'][0]['nullable'] == false) ? true : false;
    }

    protected function isSkipValue($property, \ReflectionProperty $propertyReflection)
    {
        return (isset($property['id']) && $property['id']) ? true : false;
    }

    protected function isValueScalar($property, \ReflectionProperty $propertyReflection)
    {
        return !in_array($property['type'], static::NOT_SCALAR_TYPES);
    }

    protected function getReflectionClassFromProperty($property): \ReflectionClass
    {
        $reflectionClass = $this->entityReflection;
        if (isset($property['declared'])) {
            $reflectionClass = new \ReflectionClass($property['declared']);
        }

        return $reflectionClass;
    }

    protected function getPropertyReflection($property)
    {
        $reflectionClass = $this->getReflectionClassFromProperty($property);
        return $reflectionClass->getProperty($property['fieldName']);
    }

    protected function getTargetEntity(array $property)
    {
        $nameSpaceEntity = $property['targetEntity'];
        $classReflection = new \ReflectionClass($nameSpaceEntity);
        if ($this->callableToAskEntity && ($classReflection->isAbstract() || $classReflection->isTrait())){
            $classOptions = $this->entityExtractor->guessChildEntities($classReflection, true);
            $propertyName = $this->entityReflection->getName().'::'.$property['fieldName'];
            return ($this->callableToAskEntity)($propertyName, $nameSpaceEntity, $classOptions);
        } else {
            return $nameSpaceEntity;
        }
    }

    /**
     * @param $propertyName
     * @param PropertyDetails[] $propertyDetails
     * @return PropertyDetails|null
     */
    public function findPropertyByArray($propertyName, array $propertyDetails)
    {
        foreach ($propertyDetails as $propertyDetail) {
            if ($propertyDetail->getName()===$propertyName) {
                return $propertyDetail;
            }
        }

        return null;
    }

}