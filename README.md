Fixtures Generator Bundle For Doctrine (Code Generator)
========================================================

This bundle generate the fixtures code for doctrine, you can override all code without problems 

**This bundle is in development becareful**

Installation
------------

``` 
composer require --dev "mgdsoft/fixtures-generator-bundle"
```

## Add Bundle

**For Symfony 4**, bundles.php
```php
    MGDSoft\FixturesGeneratorBundle\MgdsoftFixturesGeneratorBundle::class => ['dev' => true],
```

**For Symfony 3**, AppKernel.php
```php
    if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
        $bundles[] = new MGDSoft\FixturesGeneratorBundle\MgdsoftFixturesGeneratorBundle()
    }
```

**For symfony 4 skip this part**, for Symfony 3 configure default path 

```yaml
mgdsoft_fixtures_generator:
    fixture_path_default: '%kernel.root_dir%/../src/AppBundle/DataFixtures/ORM' # Default %kernel.root_dir%/DataFixtures/ORM
```

Show me a example ¬¬
--------------------

Execute command to generate Fixtures, by default it will take all entities for your proyect and will generate all fixtures for dev and test. If you want to create for an entity use **--entity** option, and if you want to create with all his dependencies use **-r** option

```
bin/console mgdsoft:fixtures:generate
```

This execution will create 3 files.
**src/DataFixtures/ORM/LibsAuto/AbstractLoadUserFixture.php**, this file is a abstract class you can override all methods in child class, we recommend not to modify this class.  
```php

namespace App\DataFixtures\ORM\LibsAuto;

use App\Entity\User;
use MGDSoft\FixturesGeneratorBundle\LoaderFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

abstract class AbstractLoadUserFixture extends AbstractFixture  implements DependentFixtureInterface
{
    /**
     * (Skipped) all parameters to auto complete IDE 
     */
    protected function loadRow($key, array $overrideDefaultValues = [])
    {
        $obj = new User();

        $defaultValues = $this->getDefaultValues();

        $properties = array_merge($defaultValues, $overrideDefaultValues);

        foreach ($properties as $property => $value) {
            $this->propertyAccessor->setValue($obj, $property, $value);
        }

        $this->om->persist($obj);
        $this->addReference("user-".$key, $obj);
    }

    protected function getDefaultValues()
    {
        return [

            // ---[ required values ]--- ,
            'username' => 'username',
            'usernameCanonical' => 'usernameCanonical',
            'email' => 'email',
            'emailCanonical' => 'emailCanonical',
            'password' => 'password',
            'roles' => ["ROLE_SUPER_ADMIN"],
            'colour' => 'colour',
            'isOnline' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime(),
            'salt' => 'salt',
            'enabled' => true,
            'plan' => $this->getReference("plan-1"),

            // ---[ required with default values ]--- ,
            // 'showTips' => true,

            // ---[ non-mandatory fields ]--- ,
            // 'lastLogin' => new \DateTime(),
            // 'confirmationToken' => 'confirmationToken',
            // 'passwordRequestedAt' => new \DateTime(),
            // 'name' => 'name',
            // 'lastName' => 'lastName',
            // 'avatar' => 'avatar',
            // 'initials' => 'initials',
            // 'lang' => 'en',
            // 'planDateEnd' => new \DateTime(),
            // 'stripeSubscriptionId' => 'stripeSubscriptionId',
            // 'userHasEmails' => $this->getReference("user_has_email-1"),
            // 'webPushes' => $this->getReference("user_web_push-1"),
            // 'user_resources' => $this->getReference("user_resources-1"),
            // 'tags' => $this->getReference("tag-1"),
            // 'guest' => $this->getReference("guest-1")
        ];
    }

    public function getDependencies()
    {
        return [
            'App\DataFixtures\ORM\LoadPlanFixture',
            // ---[ non-mandatory fields ]---,
            // 'App\DataFixtures\ORM\LoadUserHasEmailFixture',
            // 'App\DataFixtures\ORM\LoadUserWebPushFixture',
            // 'App\DataFixtures\ORM\LoadUserResourcesFixture',
            // 'App\DataFixtures\ORM\LoadTagFixture',
            // 'App\DataFixtures\ORM\LoadGuestFixture'
        ];
    }
}
```

**src/DataFixtures/ORM/LoadUserFixture.php**, Here you can customize what you want. This class will be loaded when you execute doctrine fixtures (bin/console doctrine:fixtures:load)
```php

namespace App\DataFixtures\ORM;

use App\DataFixtures\ORM\LibsAuto\AbstractLoadUserFixture;

class LoadUserFixture extends AbstractLoadUserFixture
{
    protected function loadRows()
    {
        $this->loadRow('1', []);
    }
}
```

And for test purpose is created tests/Fixtures/General/LoadTestUserFixture.php. If you dont want this class you can disable in configuration

```php

namespace Tests\Fixtures\General;

use App\DataFixtures\ORM\LibsAuto\AbstractLoadUserFixture;

class LoadTestUserFixture extends AbstractLoadUserFixture
{
    protected function loadRows()
    {
        $this->loadRow('1', []);
    }
}
```

How to insert multiples rows?
-----------------------------

src/DataFixtures/ORM/LoadUserFixture.php
```php

namespace App\DataFixtures\ORM;

use App\DataFixtures\ORM\LibsAuto\AbstractLoadUserFixture;

class LoadUserFixture extends AbstractLoadUserFixture
{
    protected function loadRows()
    {
        $this->loadRow('1', ['username' => 'Miguel1', 'email' => 'mgd1@mgdsoftware.com']);
        $this->loadRow('2', ['username' => 'Miguel2', 'email' => 'mgd2@mgdsoftware.com']);
        $this->loadRow('3', ['username' => 'Miguel3', 'email' => 'mgd3@mgdsoftware.com']);
    }
}
```

Each row insert has a doctrine reference with "class Prefix"-"$key"

If you want to add multiple values for an array you must use "|" symbol

```php

namespace App\DataFixtures\ORM;

use App\DataFixtures\ORM\LibsAuto\AbstractLoadUserFixture;

class LoadUserFixture extends AbstractLoadUserFixture
{
    protected function loadRows()
    {
        $this->loadRow('1', ['comments|1' => $this->getReference('comment-2'), 'comments|2' => $this->getReference('comment-1') ]);
    }
}
```

For autocomplete fields use **deep-assoc-completion** in phpstorm RECOMMENDED


To see all options execute

```bash
bin/console mgdsoft:fixtures:generate -h 
```

## Configuration

```yaml
mgdsoft_fixtures_creator:
    abstract_fixture_class: MGDSoft\FixturesGeneratorBundle\LoaderFixtures\AbstractFixture
    entity_path_default:  App\Entity
    template:             /<absolute>/MGDSoft/FixturesGeneratorBundle/DependencyInjection/../Generator/templates/basic.tpl
    template_lib:         /<absolute>/MGDSoft/FixturesGeneratorBundle/DependencyInjection/../Generator/templates/lib.tpl
    fixture_path_default: '%kernel.root_dir%/DataFixtures/ORM'
    php_cs_fixer:         php-cs-fixer
    generate_autocomplete_array_options: true
    test:
        template:             /<absolute>/MGDSoft/FixturesGeneratorBundle/DependencyInjection/../Generator/templates/basic_test.tpl
        enabled:              true
        fixture_path_default: '%kernel.root_dir%/../tests/Fixtures/General'
```

All pull request are welcome :sunglasses:
