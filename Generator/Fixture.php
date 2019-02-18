<?php

namespace MGDSoft\FixturesGeneratorBundle\Generator;

class Fixture extends AbstractFixtureGenerator
{
    /**
     * @param $properties
     * @param \ReflectionClass $entityReflection
     * @return mixed
     */
    public function getClassStringFixture($properties, \ReflectionClass $entityReflection, $className, $nameSpaceFixture)
    {
        $this->properties       = $properties;
        $this->entityReflection = $entityReflection;
        $this->nameSpaceFixture = $nameSpaceFixture;
        $this->nameSpaceBaseForDependecies = $nameSpaceFixture;
        $this->className = $className;

        return $this->getClassStringFixtureCommon();
    }

}