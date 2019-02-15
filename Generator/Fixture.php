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
        $templateString = file_get_contents($this->template);

        $this->properties       = $properties;
        $this->entityReflection = $entityReflection;
        $this->nameSpaceFixture = $nameSpaceFixture;

        $replace = [
            '{NAME_SPACE_FIXTURE}'       => $nameSpaceFixture,
            '{CURRENT_OBJECT}'           => $entityReflection->getName(),
            '{CLASS_NAME_FIXTURE}'       => $className,
            '{CLASS_NAME_ENTITY}'        => $entityReflection->getShortName(),
            '{ARR_DEFAULT_VALUES}'       => $this->getArrDefaultValues(),
            '{FIXTURE_REFERENCE_PREFIX}' => static::getFixtureReferencePrefix($this->entityReflection->getShortName()),
            '{FIXTURE_REFERENCE_ID}'     => '$key',
            '{DEPENDENCIES}'             => $this->getDependencies()
        ];

        return $this->strReplaceAssoc($replace, $templateString);
    }

}