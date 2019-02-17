<?php

namespace MGDSoft\FixturesGeneratorBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Extractor\Entity;
use MGDSoft\FixturesGeneratorBundle\Extractor\Property;
use MGDSoft\FixturesGeneratorBundle\Generator\Fixture;
use MGDSoft\FixturesGeneratorBundle\Generator\FixtureLib;
use MGDSoft\FixturesGeneratorBundle\Generator\FixtureTest;
use MGDSoft\FixturesGeneratorBundle\Guesser\ClassNameSpace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

class FixturesCreatorCommand extends ContainerAwareCommand
{
    const PHP_CS_FIX_RULES = "--rules='{\"array_syntax\": {\"syntax\": \"short\"}, \"binary_operator_spaces\": {\"align_double_arrow\": true }}'";
    const ENTITY_OPTION_ALL = "ALL";

    /** @var OutputInterface */
    private $output;

    /** @var InputInterface */
    private $input;

    /** @var EntityManager */
    private $em;

    /** @var Property */
    private $propertyExtractor;

    /** @var bool */
    private $recursive;

    /** @var bool */
    private $dump;

    /** @var Fixture */
    private $fixtureGenerator;

    /** @var FixtureTest */
    private $fixtureTestGenerator;

    /** @var FixtureLib  */
    private $fixtureLibGenerator;

    /** @var ClassNameSpace */
    private $classNameSpaceGuesser;

    /** @var  String */
    private $fixturetPathToSave;

    /** @var  String */
    private $phpCSFixExecutablePath=null;

    /** @var array */
    private $fixturesCreated=[];

    /** @var  bool */
    private $testEnabled;

    /** @var string */
    private $testFixturePathDefault;

    private $helper;

    /** @var Entity  */
    private $entityExtractor;
    private $onlyOverwriteAbstract;


    /**
     * FixturesCreatorCommand constructor.
     */
    public function __construct(
        $name = null,
        EntityManagerInterface $em,
        Property $propertyExtractor,
        Entity $entityExtractor,
        FixtureLib $fixtureLib,
        Fixture $fixtureGenerator,
        FixtureTest $fixtureTestGenerator,
        ClassNameSpace $classNameSpaceGuesser)
    {
        parent::__construct($name);

        $this->em                     = $em;
        $this->propertyExtractor      = $propertyExtractor;
        $this->entityExtractor        = $entityExtractor;
        $this->fixtureGenerator       = $fixtureGenerator;
        $this->fixtureTestGenerator   = $fixtureTestGenerator;
        $this->fixtureLibGenerator    = $fixtureLib;
        $this->classNameSpaceGuesser  = $classNameSpaceGuesser;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('mgdsoft:fixtures:generate')
            ->setDescription('Create fixture from entity')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'namespace from class', 'ALL')
            ->addOption('onlyAbstracts', 'a', InputOption::VALUE_NONE, 'only overwrite abstract class')
            ->addOption('path', 'p',InputOption::VALUE_OPTIONAL, 'path to save the new classes src/AppBundle/DataFixtures/ORM/, configure default value in config')
            ->addOption('php-cs-fixer', 'c',InputOption::VALUE_OPTIONAL, 'php-cs-fixer, configure default value in config')
            ->addOption('recursive', 'r',InputOption::VALUE_NONE, 'create dependent fixtures')
            ->addOption('dump', 'd',InputOption::VALUE_NONE, 'Only dump')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = $input->getOption('entity');

        $this->output = $output;
        $this->input  = $input;
        $this->helper = $this->getHelper('question');

        $this->onlyOverwriteAbstract  = $this->input->getOption('onlyAbstracts');
        $this->fixturetPathToSave     = $input->getOption('path') ?? $this->getContainer()->getParameter('mgdsoft.fixtures_generator.fixture_path_default');
        $this->recursive              = $entity === static::ENTITY_OPTION_ALL ? false : $this->input->getOption('recursive');
        $this->dump                   = $this->input->getOption('dump');

        $this->testEnabled  = $this->getContainer()->getParameter('mgdsoft.fixtures_generator.test.enabled');
        $this->testFixturePathDefault  = $this->getContainer()->getParameter('mgdsoft.fixtures_generator.test.fixture_path_default');

        $this->phpCSFixExecutablePath = $input->getOption('php-cs-fixer') ?: $this->getContainer()->getParameter('mgdsoft.fixtures_generator.php_cs_fixer');

        if (!$this->phpCSFixExecutablePath || !$this->executableExist($this->phpCSFixExecutablePath)){
            $this->phpCSFixExecutablePath = null;
            $output->writeln('<error>PHP CS FIXER doesn\'t exist, code won\'t be formatted</error>');
        }

        if ($entity === 'ALL') {
            $entities = $this->entityExtractor->getAllEntities();

            foreach ($entities as $entity) {
                $this->createFixture($entity);
            }
        } else {
            $this->createFixture($entity);
        }
    }

    private function executableExist($exe)
    {
        $process = new Process(sprintf("which %s", escapeshellarg($exe)));
        $process->run();

        return !empty($process->getOutput());
    }

    protected function createFixture($entity)
    {
        $entityNameSpace = $this->getFullNameSpace($entity);

        if (in_array($entityNameSpace, $this->fixturesCreated)) {
            return;
        }

        $this->fixturesCreated[] = $entityNameSpace;

        $entityReflection = new \ReflectionClass($entityNameSpace);

        $properties = $this->propertyExtractor->getPropertiesFromEntity($entityReflection, $this->askForSpecialClassEntityCallable());


        $fixtureClassName    = $this->fixtureGenerator->getShortNameNewFixture($entityReflection->getShortName());
        $fileFullPath        = $this->fixturetPathToSave . '/' . $fixtureClassName . '.php';
        $nameSpaceNewFixture = $this->classNameSpaceGuesser->getPathFromPath(dirname($fileFullPath));

        if (!$this->onlyOverwriteAbstract) {
            $fixturePHPString = $this->fixtureGenerator->getClassStringFixture(
                $properties,
                $entityReflection,
                $fixtureClassName,
                $nameSpaceNewFixture
            );

            $this->createFileOrDump($fixtureClassName, $fixturePHPString, $fileFullPath);
        }

        $nameSpaceForDependencies = $nameSpaceNewFixture;
        $fixtureClassName = 'Abstract'.$this->fixtureGenerator->getShortNameNewFixture($entityReflection->getShortName());
        $fileFullPath = $this->fixturetPathToSave.'/LibsAuto/'.$fixtureClassName.'.php';
        $nameSpaceNewFixture =  $this->classNameSpaceGuesser->getPathFromPath(dirname($fileFullPath));

        $fixtureLibPHPString = $this->fixtureLibGenerator->getClassStringFixture(
            $properties,
            $entityReflection,
            $fixtureClassName,
            $nameSpaceNewFixture,
            $nameSpaceForDependencies
        );

        $this->createFileOrDump($fixtureClassName, $fixtureLibPHPString, $fileFullPath);


        if ($this->testEnabled && !$this->onlyOverwriteAbstract) {
            $fixtureClassNameExtended = $nameSpaceNewFixture . '\\' . $fixtureClassName;
            $fixtureClassNameTest = $this->fixtureTestGenerator->getShortNameNewFixture($entityReflection->getShortName());
            $fileFullPathTest = $this->testFixturePathDefault.'/'.$fixtureClassNameTest.'.php';

            $fixtureTestPHPString = $this->fixtureTestGenerator->getClassStringFixture(
                $properties,
                $entityReflection,
                $fixtureClassNameTest,
                $this->classNameSpaceGuesser->getPathFromPath(dirname($fileFullPathTest)),
                $fixtureClassName,
                $fixtureClassNameExtended
            );

            $this->createFileOrDump($fixtureClassName, $fixtureTestPHPString, $fileFullPathTest);
        }

        $this->generateRecursiveEntities($properties);
    }

    private function createFileOrDump($fixtureClassName, $fixturePHPString, $fileFullPath)
    {
        if ($this->dump) {
            $this->dumpFile($fixtureClassName, $fixturePHPString);
        }else{
            $this->createNewFile($fixturePHPString, $fileFullPath);
        }
    }

    private function getFullNameSpace($entity)
    {
        $defaultNameSpace = $this->getContainer()->getParameter('mgdsoft.fixtures_generator.entity_path_default');
        $classNameSpace = $entity;

        if (!class_exists($classNameSpace)) {
            $defaultNameSpace = $defaultNameSpace .'\\'. $classNameSpace;
            if (!class_exists($defaultNameSpace)) {
                throw new \RuntimeException("Class doesn't exist '$classNameSpace' || '$entity'");
            }
            $classNameSpace = $defaultNameSpace;
        }

        return $classNameSpace;
    }

    private function createNewFile($fixturePHPString, $fileFullPath)
    {
        $dirName = dirname($fileFullPath);

        if (!file_exists($dirName)) {

            $question = new ConfirmationQuestion("This folder <info>\"$dirName\"</info> doesn't exist \n<question>do you want to create a new one?</question> ", true);
            if (!$this->helper->ask($this->input, $this->output, $question)) {
                throw new \RuntimeException('Folder is required');
            }

            mkdir($dirName, 0777, true);
        }

            if (file_exists($fileFullPath)) {
            $question = new ConfirmationQuestion("WARNING, the file <info>$fileFullPath</info> exist\n<question>do you want overwrite?</question> ", false);

            if (!$this->helper->ask($this->input, $this->output, $question)) {
                return;
            }
        }

        file_put_contents($fileFullPath, $fixturePHPString);
        $fileFullPath = realpath($fileFullPath);

        $this->output->writeln("<info>[+]</info> File - <comment>$fileFullPath</comment>");
        if ($this->phpCSFixExecutablePath) {
            $command = "$this->phpCSFixExecutablePath fix $fileFullPath " . static::PHP_CS_FIX_RULES;
            $this->output->writeln($command, OutputInterface::VERBOSITY_VERBOSE);
            $p = new Process($command);
            $p->run();
        }
    }

    /**
     * @param PropertyDetails[] $properties
     */
    private function generateRecursiveEntities($properties)
    {
        if ($this->recursive) {
            foreach ($properties as $property) {
                if ($property->isAssociationMapping() && $property->isRequired()) {
                    $this->createFixture($property->getAssociationMappingsClass());
                }
            }
        }
    }

    private function dumpFile($fixtureClassName, $fixturePHPString)
    {
        $this->output->writeln(
            "<info>[!]</info> Dump - <comment>$fixtureClassName</comment>\n\n\n$fixturePHPString\n\n"
        );
    }

    protected function askForSpecialClassEntityCallable(): \Closure
    {
        return function ($property, $nameSpaceEntity, $choices = []){

            $default = $choices[0] ?? null;

            $question = new Question(
                "<error>[!]</error> property: <comment>$property</comment> has a type of class <comment>$nameSpaceEntity</comment> is a abstract entity, use other ($default): ",
                $default
            );
            $question->setAutocompleterValues($choices);

            $entityName = $this->helper->ask($this->input, $this->output, $question);

            try{
                $nameSpace = $this->getFullNameSpace($entityName);
            }catch (\Exception $e) {
                return ($this->askForSpecialClassEntityCallable())($property, $nameSpaceEntity, $choices);
            }

            return $nameSpace;
        };

    }
}