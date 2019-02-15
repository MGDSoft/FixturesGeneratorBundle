Fixtures Generator Bundle For Doctrine
==================================

This bundle generate the fictures code for doctrine, you can can override this code without problems 

**Unestable becareful**

Installation

``` 
composer require "mgd/fixtures_generator"
```

Add Into bundles.php
```
    MGDSoft\FixturesGeneratorBundle\MgdsoftFixturesGeneratorBundle::class => ['dev' => true],
```

Configure

```
mgdsoft_fixtures_generator: ~
```

Execute Command to generate Fixtures

```
bin/console mgdsoft:fixtures:generate EntityName
```

If you want to create recursive fixtures for your entity add **-r** Option

All pull request are welcome