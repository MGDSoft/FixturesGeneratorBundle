<?php

namespace MGDSoft\FixturesGeneratorBundle\Extractor\Bean;

class PropertyDetails
{
    /** @var String */
    private $name;

    /** @var String|null */
    private $valueDefault;

    /** @var String */
    private $type;

    /** @var boolean */
    private $required;

    /** @var boolean */
    private $skipSetValue;

    /** @var boolean */
    private $skipDefaultValue;

    /** @var array */
    private $metadataProperty;

    /** @var boolean */
    private $defaultValueIsScalar;

    /** @var string */
    private $associationMappingsClass;

    /**
     * PropertyDetails constructor.
     * @param String $name
     * @param null|String $valueDefault
     * @param String $type
     * @param bool $required
     * @param bool $skipSetValue
     * @param bool $skipDefaultValue
     * @param array $metadataProperty
     * @param bool $defaultValueIsScalar
     * @param int $associationMappingsType
     * @param string $associationMappingsClass
     */
    public function __construct(
        $name,
        $valueDefault,
        $type,
        $required,
        $skipSetValue,
        $skipDefaultValue,
        array $metadataProperty,
        $defaultValueIsScalar=true,
        $associationMappingsClass=null
    ) {
        $this->name                     = $name;
        $this->valueDefault             = $valueDefault;
        $this->type                     = $type;
        $this->required                 = $required;
        $this->skipSetValue             = $skipSetValue;
        $this->skipDefaultValue         = $skipDefaultValue;
        $this->metadataProperty         = $metadataProperty;
        $this->defaultValueIsScalar     = $defaultValueIsScalar;
        $this->associationMappingsClass = $associationMappingsClass;
    }

    /**
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|String
     */
    public function getValueDefault()
    {
        return $this->valueDefault;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return bool
     */
    public function isSkipDefaultValue()
    {
        return $this->skipDefaultValue;
    }

    /**
     * @return bool
     */
    public function isSkipSetValue()
    {
        return $this->skipSetValue;
    }

    /**
     * @return bool
     */
    public function isDefaultValueScalar()
    {
        return $this->defaultValueIsScalar;
    }

    /**
     * @param bool $defaultValueIsScalar
     * @return $this
     */
    public function setDefaultValueIsScalar($defaultValueIsScalar)
    {
        $this->defaultValueIsScalar = $defaultValueIsScalar;
        return $this;
    }

    /**
     * @return array
     */
    public function getMetadataProperty()
    {
        return $this->metadataProperty;
    }

    /**
     * @return bool
     */
    public function isDefaultValueIsScalar()
    {
        return $this->defaultValueIsScalar;
    }

    /**
     * @return string
     */
    public function getAssociationMappingsClass()
    {
        return $this->associationMappingsClass;
    }

    public function isAssociationMapping()
    {
        return $this->associationMappingsClass !== null;
    }
}