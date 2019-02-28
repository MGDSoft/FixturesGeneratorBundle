<?php

namespace MGDSoft\FixturesGeneratorBundle\LoaderFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractFixture extends Fixture implements ContainerAwareInterface
{
    /** @var  ObjectManager */
    protected $om;

    /** @var  PropertyAccessor */
    protected $propertyAccessor;

    /** @var  ContainerInterface */
    protected $container;

    const SYMBOL_TO_CUT_NAME_AND_GET_HIS_KEY = '|';

    public function load(ObjectManager $manager)
    {
        $this->om = $manager;
        $this->propertyAccessor = new PropertyAccessor();
        $this->loadRows();
        $this->om->flush();
    }

    abstract protected function loadRows();

    protected function addValues($properties)
    {
        foreach ($properties as $property => $value) {
            $property = $this->cutNameToAddMultiplesValues($property);
            $this->propertyAccessor->setValue($obj, $property, $value);
        }
    }

    /**
     * Example user has posts, and u want to add multiples posts
     * create your posts with names "post|1" => 'post_reference_1', "post|2" => 'post_reference_2'
     *
     * @param $name
     * @return array
     */
    protected function cutNameToAddMultiplesValues ($name)
    {
        return explode(static::SYMBOL_TO_CUT_NAME_AND_GET_HIS_KEY, $name)[0];
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container->has('test.service_container') ? $container->get('test.service_container') : $container;
    }

}
