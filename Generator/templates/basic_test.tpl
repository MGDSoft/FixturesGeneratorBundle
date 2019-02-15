<?php

namespace {NAME_SPACE_FIXTURE};

use {CLASS_NAMESPACE_EXTENDEND};
use MGDSoft\FixturesGeneratorBundle\LoaderFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class {CLASS_NAME_FIXTURE} extends {CLASS_NAME_EXTENDEND} implements DependentFixtureInterface
{
    protected function loadRows()
    {
        $this->loadRow('1', []);
    }

    public function getDependencies()
    {
        return {DEPENDENCIES};
    }
}