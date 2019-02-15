<?php

namespace {NAME_SPACE_FIXTURE};

use {CURRENT_OBJECT};
use MGDSoft\FixturesGeneratorBundle\LoaderFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class {CLASS_NAME_FIXTURE} extends AbstractFixture implements DependentFixtureInterface
{
    protected function loadRows()
    {
        $this->loadRow('1', []);
        $this->om->flush();
    }

    protected function loadRow($key, array $overrideDefaultValues = [])
    {
        $obj = new {CLASS_NAME_ENTITY}();

        // Modify what you want
        $defaultValues = {ARR_DEFAULT_VALUES};

        $properties = array_merge($defaultValues, $overrideDefaultValues);

        foreach ($properties as $property => $value) {
            $this->propertyAccessor->setValue($obj, $property, $value);
        }

        $this->om->persist($obj);
        $this->addReference("{FIXTURE_REFERENCE_PREFIX}-".{FIXTURE_REFERENCE_ID}, $obj);
    }

    public function getDependencies()
    {
        return {DEPENDENCIES};
    }
}