<?php

namespace MGDSoft\FixturesGeneratorBundle\Extractor;

use Doctrine\ORM\EntityManagerInterface;

class Entity
{
    protected $em;
    protected $entityDefaultPath;

    public function __construct(EntityManagerInterface $em, $entityDefaultPath)
    {
        $this->em = $em;
        $this->entityDefaultPath = $entityDefaultPath;
    }

    public function getAllEntities()
    {
        $entities = [];
        $metas = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($metas as $meta) {
            $reflectionClass = new \ReflectionClass($meta->getName());
            if ($reflectionClass->isAbstract() ) {
                continue;
            }
            $entities[] = $meta->getName();
        }

        return $entities;
    }

    public function guessChildEntities(\ReflectionClass $classParent, $shortNameFormat = false)
    {
        $data = [];
        $classMetadata = $this->em->getClassMetadata($classParent->getName());
        if ($classMetadata->discriminatorMap) {
            $data = array_values($classMetadata->discriminatorMap);
        }

        if ($shortNameFormat) {
            $data = $this->getShortNamesEntities($data);
        }

        return $data;
    }

    public function getShortNamesEntities(array $nameSpaces)
    {
        return array_map(function (&$val) {
            return $this->getShortNameEntity($val);
        }, $nameSpaces);
    }

    public function getShortNameEntity($nameSpace)
    {
        $replace = $this->entityDefaultPath.'\\';
        return str_replace($replace, '', $nameSpace);
    }
}