<?php

namespace MGDSoft\FixturesGeneratorBundle\Guesser;

use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Generator\Fixture;

class Data
{
    public function createRandomValue(PropertyDetails $property )
    {
        if ($property->isAssociationMapping()) {
            $property->setDefaultValueIsScalar(false);

            $referencePrefix = Fixture::getFixtureReferencePrefix(
                (new \ReflectionClass($property->getAssociationMappingsClass()))->getShortName()
            );

            return '$this->getReference("'.$referencePrefix.'-1")';
        }

        return $this->createRandomValueSimple($property->getType(), $property->getName());
    }

    public function createRandomValueSimple($type, $defaultNameString)
    {
        switch ($type)
        {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return 10;
            case 'decimal':
            case 'float':
                return 10.3;
            case 'string':
                return $defaultNameString;
            case 'text':
                return $defaultNameString;
            case 'boolean':
                return true;
            case 'date':
            case 'datetime':
            case 'datetimetz':
            case 'datetimetz_immutable':
            case 'dateinterval':
            case 'time':
                return 'new \DateTime()';
            case 'array':
            case 'simple_array':
            case 'json':
            case 'json_array':

                if ($defaultNameString === 'roles') {
                    return '["ROLE_SUPER_ADMIN"]';
                }

                return "['$defaultNameString']";

            case 'object':
                return ''; // todo
            case 'guid':
            case 'blob':
                return ''; // todo


            default:
                throw new \Exception("Unexpected type '$type' ");
        }
    }
}