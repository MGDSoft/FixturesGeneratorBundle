<?php

namespace MGDSoft\FixturesGeneratorBundle\Generator;

use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Guesser\Data;

class FixtureTest extends AbstractFixtureGenerator
{
    const prefixNewFixture = 'LoadTest';
    const suffixNewFixture = 'Fixture';

    /**
     * @param $properties
     * @param \ReflectionClass $entityReflection
     * @return mixed
     */
    public function getClassStringFixture($properties, \ReflectionClass $entityReflection, $className, $nameSpaceFixture, $classShortNameExtendend, $classNameExtendend)
    {
        $templateString = file_get_contents($this->template);

        $this->properties       = $properties;
        $this->entityReflection = $entityReflection;
        $this->nameSpaceFixture = $nameSpaceFixture;
        $this->nameSpaceBaseForDependecies = $nameSpaceFixture;

        $this->calculateDependencies();

        $replace = [
            '{NAME_SPACE_FIXTURE}'        => $nameSpaceFixture,
            '{CLASS_NAMESPACE_EXTENDEND}' => $classNameExtendend,
            '{CLASS_NAME_FIXTURE}'        => $className,
            '{CLASS_NAME_EXTENDEND}'      => $classShortNameExtendend,
            '{DEPENDENCIES}'              => $this->generateDependenciesString(),
            '{COMMENT_INTERFACE}'         => $this->generateCommentInterfaceString(),
        ];

        return $this->strReplaceAssoc($replace, $templateString);
    }

}