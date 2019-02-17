<?php

namespace {NAME_SPACE_FIXTURE};

use {CURRENT_OBJECT};
use MGDSoft\FixturesGeneratorBundle\LoaderFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

abstract class {CLASS_NAME_FIXTURE} extends AbstractFixture {COMMENT_INTERFACE} implements DependentFixtureInterface
{
    protected function loadRows()
    {
        $this->loadRow('1', []);
        $this->om->flush();
    }

    protected function loadRow($key, array $overrideDefaultValues = [])
    {
        $obj = new {CLASS_NAME_ENTITY}({CLASS_CONSTRUCTOR_ENTITY});

        $defaultValues = $this->getDefaultValues();

        $properties = array_merge($defaultValues, $overrideDefaultValues);

        foreach ($properties as $property => $value) {
            $this->propertyAccessor->setValue($obj, $property, $value);
        }

        $this->om->persist($obj);
        $this->addReference("{FIXTURE_REFERENCE_PREFIX}-".{FIXTURE_REFERENCE_ID}, $obj);
    }

    protected function getDefaultValues()
    {
        return {ARR_DEFAULT_VALUES};
    }

    public function getDependencies()
    {
        return {DEPENDENCIES};
    }
}