<?php

namespace {NAME_SPACE_FIXTURE};

use {CURRENT_OBJECT};
use {ABSTRACT_FIXTURE_NAMESPACE};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

abstract class {CLASS_NAME_FIXTURE} extends {ABSTRACT_FIXTURE_SHORT_NAME} {COMMENT_INTERFACE} implements DependentFixtureInterface
{
{AUTOCOMPLETE_ARRAY_OPTIONS}
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