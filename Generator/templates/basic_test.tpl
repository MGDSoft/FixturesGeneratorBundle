<?php

namespace {NAME_SPACE_FIXTURE};

use {ABSTRACT_FIXTURE_NAMESPACE};
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class {CLASS_NAME_FIXTURE} extends {ABSTRACT_FIXTURE_SHORT_NAME} {COMMENT_INTERFACE} implements DependentFixtureInterface
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