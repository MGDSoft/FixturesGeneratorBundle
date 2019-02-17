<?php

namespace MGDSoft\FixturesGeneratorBundle\Generator;

use MGDSoft\FixturesGeneratorBundle\Exception\FixturesGeneratorException;
use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Extractor\Property;
use MGDSoft\FixturesGeneratorBundle\Guesser\Data;

class AbstractFixtureGenerator
{
    /** @var  String */
    protected $template;

    /** @var  String */
    protected $nameSpaceFixture;

    /** @var  String */
    protected $nameSpaceBaseForDependecies;

    /** @var PropertyDetails[] */
    protected $properties;

    /** @var \ReflectionClass */
    protected $entityReflection;

    /** @var Data */
    protected $dataGenerator;
    /** @var Property */
    protected $propertyExtractor;

    const prefixNewFixture = 'Load';
    const suffixNewFixture = 'Fixture';

    protected $depsRequired = [];
    protected $depsOptional = [];

    public function __construct($template, Data $dataGenerator, Property $propertyExtractor)
    {
        $this->dataGenerator = $dataGenerator;
        $this->propertyExtractor = $propertyExtractor;

        if (file_exists($template)) {
            $this->template = $template;
        }else{
            $this->template = __DIR__ .'/../Generator/templates/' . $this->template;
        }

        if (!file_exists($this->template)) {
            throw new FixturesGeneratorException("Template '$this->template' doesn't exist");
        }
    }

    public function getShortNameNewFixture($className)
    {
        return static::prefixNewFixture . $className . static::suffixNewFixture;
    }

    protected function getArrDefaultValues()
    {
        $required = ["\n            // ---[ required values ]--- "];
        $requiredWithDefaultValues = ["\n            // ---[ required with default values ]--- "];
        $notRequired = ["\n            // ---[ non-mandatory fields ]--- "];

        foreach ($this->properties as $property) {

            if ($property->isSkipSetValue()) {
                continue;
            }

            $value = $property->getValueDefault();

            if (!$property->isSkipDefaultValue() && !$value) {
                $value = $this->dataGenerator->createRandomValue($property);
            }

            $current = "            ".($property->isRequired() && !$property->isSkipDefaultValue() && !$property->getValueDefault() ? '' : '// ')."'";

            if ($property->isDefaultValueScalar()) {
                $current .= $property->getName()."' => ". var_export($value, true);
            }else{
                $current .= $property->getName()."' => ". $value;
            }

            if ($property->isRequired() && !$property->getValueDefault()) {
                $required[] = $current;
            }elseif($property->isRequired() && $property->getValueDefault()){
                $requiredWithDefaultValues[] = $current;
            }else{
                $notRequired[]=$current;
            }
        }

        return "[\n".implode(",\n", array_merge($required, $requiredWithDefaultValues, $notRequired)) . "\n        ]";
    }

    static public function getFixtureReferencePrefix($classShortName)
    {
        return ltrim(
            strtolower(
                preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $classShortName)
            ),
            '_'
        );
    }

    protected function strReplaceAssoc(array $replace, $subject)
    {
        return str_replace(array_keys($replace), array_values($replace), $subject);
    }

    protected function calculateDependencies()
    {
        $this->depsRequired = [];
        $this->depsOptional = [];

        foreach ($this->properties as $property) {
            if (!$property->isAssociationMapping()) {
                continue;
            }

            $r = new \ReflectionClass($property->getAssociationMappingsClass());
            $classShortName = $this->getShortNameNewFixture($r->getShortName());

            $dependency = '            '. ($property->isRequired() ? '' : '// ') .
                '\'' . $this->nameSpaceBaseForDependecies . '\\' . $classShortName . '\'';

            if ($property->isRequired()) {
                $this->depsRequired[] = $dependency;
            }else{
                $this->depsOptional[] = $dependency;
            }
        }
    }

    protected function generateDependenciesString()
    {
        return "[\n" .
            implode(",\n",
                array_merge($this->depsRequired, ['            // ---[ non-mandatory fields ]---'], $this->depsOptional)
            ) . "\n        ]";
    }

    protected function generateCommentInterfaceString()
    {
        if (count($this->depsRequired) > 0) {
            return '';
        }

        return '//';
    }

    protected function generateConstructorArgumentsString()
    {
        $constructor = $this->entityReflection->getConstructor();
        if (!$constructor || !$params = $constructor->getParameters()) {
            return '';
        }

        $argsResult = [];

        foreach ($params as $param) {
            if ($property = $this->propertyExtractor->findPropertyByArray($param->getName(), $this->properties)){
                $tmp = '$overrideDefaultValues["'.$property->getName().'"] ?? ';
                if ($property->getName() === 'id' && $property->getType() === 'string') {
                    $tmp.='$key';
                }else {
                    $tmp.=var_export($this->dataGenerator->createRandomValueSimple($property->getType(), $property->getName()), true);
                }
                $argsResult[] = $tmp;
            } else {
                $argsResult[]=var_export('Unknown type', true);
            }
        }

        return implode(", ", $argsResult);
    }

}