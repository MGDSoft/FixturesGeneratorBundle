<?php
// todo delete this file
namespace MGDSoft\LoaderFixtures;

use App\Entity\User;
use MGDSoft\FixturesGeneratorBundle\LoaderFixtures\AbstractFixture;

abstract class Fixture extends AbstractFixture
{
    protected function loadRows()
    {
        $this->loadRow("name");
        $this->om->flush();
    }

    protected function loadRow($name = "name")
    {
        $obj = new User();

        $obj
            ->setName($name)
        ;

        $this->om->persist($obj);
        $this->addReference("user-".$obj->getId(), $obj);
    }

}