<?php

namespace MGDSoft\FixturesGeneratorBundle\Generator;

class FixtureLib extends AbstractFixtureGenerator
{
    /**
     * @param $properties
     * @param \ReflectionClass $entityReflection
     * @return mixed
     */
    public function getClassStringFixture(
        $properties,
        \ReflectionClass $entityReflection,
        $className,
        $nameSpaceFixture,
        $nameSpaceBaseForDependecies
    ) {
        $this->properties       = $properties;
        $this->entityReflection = $entityReflection;
        $this->nameSpaceFixture = $nameSpaceFixture;
        $this->nameSpaceBaseForDependecies = $nameSpaceBaseForDependecies;
        $this->className = $className;

        return $this->getClassStringFixtureCommon();
    }

}