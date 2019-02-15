<?php

namespace MGDSoft\FixturesGeneratorBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use MGDSoft\FixturesGeneratorBundle\Extractor\Bean\PropertyDetails;
use MGDSoft\FixturesGeneratorBundle\Extractor\Property;
use MGDSoft\FixturesGeneratorBundle\Generator\Fixture;
use MGDSoft\FixturesGeneratorBundle\Generator\FixtureTest;
use MGDSoft\FixturesGeneratorBundle\Guesser\ClassNameSpace;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

class FixturesCreatorCommand extends ContainerAwareCommand
{
    const PHP_CS_FIX_RULES = "--rules='{\"array_syntax\": {\"syntax\": \"short\"}, \"binary_operator_spaces\": {\"align_double_arrow\": true }}'";

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

    /**
     * FixturesCreatorCommand constructor.
     */
    public function __construct(
        $name = null,
        EntityManagerInterface $em,
        Property $propertyExtractor,
        Fixture $fixtureGenerator,
        FixtureTest $fixtureTestGenerator,
        ClassNameSpace $classNameSpaceGuesser)
    {
        parent::__construct($name);

        $this->em                     = $em;
        $this->propertyExtractor      = $propertyExtractor;
        $this->fixtureGenerator       = $fixtureGenerator;
        $this->fixtureTestGenerator   = $fixtureTestGenerator;
        $this->classNameSpaceGuesser  = $classNameSpaceGuesser;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('mgdsoft:fixtures:generate')
            ->setDescription('Create fixture from entity')
            ->addArgument('entity', InputArgument::REQUIRED, 'namespace from class')
            ->addOption('path', 'p',InputOption::VALUE_OPTIONAL, 'path to save the new classes src/AppBundle/DataFixtures/ORM/, configure default value in config')
            ->addOption('php-cs-fixer', 'c',InputOption::VALUE_OPTIONAL, 'php-cs-fixer, configure default value in config')
            ->addOption('recursive', 'r',InputOption::VALUE_NONE, 'create dependent fixtures')
            ->addOption('dump', 'd',InputOption::VALUE_NONE, 'Only dump')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output                 = $output;
        $this->input                  = $input;

        $this->fixturetPathToSave     = $input->getOption('path') ?: $this->getContainer()->getParameter('mgdsoft.fixtures_generator.fixture_path_default');
        $this->recursive              = $this->input->getOption('recursive');
        $this->dump                   = $this->input->getOption('dump');

        $this->testEnabled  = $this->getContainer()->getParameter('mgdsoft.fixtures_generator.test.enabled');
        $this->testFixturePathDefault  = $this->getContainer()->getParameter('mgdsoft.fixtures_generator.test.fixture_path_default');

        $this->phpCSFixExecutablePath = $input->getOption('php-cs-fixer') ?: $this->getContainer()->getParameter('mgdsoft.fixtures_generator.php_cs_fixer');


        if (!$this->executableExist($this->phpCSFixExecutablePath)){
            $this->phpCSFixExecutablePath = null;
            $output->writeln('<error>PHP CS FIXER doesn\'t exist, code won\'t be formatted</error>');
        }

        $this->createFixture($input->getArgument('entity'));
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
        $properties = $this->propertyExtractor->getPropertiesFromEntity($entityReflection);

        $fixtureClassName = $this->fixtureGenerator->getShortNameNewFixture($entityReflection->getShortName());
        $fileFullPath = $this->fixturetPathToSave.'/'.$fixtureClassName.'.php';
        $nameSpaceNewFixture =  $this->classNameSpaceGuesser->getPathFromPath(dirname($fileFullPath));
        $fixturePHPString = $this->fixtureGenerator->getClassStringFixture(
            $properties,
            $entityReflection,
            $fixtureClassName,
            $nameSpaceNewFixture
        );

        if ($this->dump) {
            $this->dumpFile($fixtureClassName, $fixturePHPString);
        }else{
            $this->createNewFile($fixturePHPString, $fileFullPath);
        }

        if ($this->testEnabled) {
            $fixtureClassNameTest = $this->fixtureTestGenerator->getShortNameNewFixture($entityReflection->getShortName());
            $fileFullPathTest = $this->testFixturePathDefault.'/'.$fixtureClassNameTest.'.php';

            $fixtureTestPHPString = $this->fixtureTestGenerator->getClassStringFixture(
                $properties,
                $entityReflection,
                $fixtureClassNameTest,
                $this->classNameSpaceGuesser->getPathFromPath(dirname($fileFullPathTest)),
                $fixtureClassName,
                $nameSpaceNewFixture.'\\'.$fixtureClassName
            );

            if ($this->dump) {
                $this->dumpFile($fixtureClassName, $fixtureTestPHPString);
            }else{
                $this->createNewFile($fixtureTestPHPString, $fileFullPathTest);
            }
        }

        $this->generateRecursiveEntities($properties);
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
        $helper = $this->getHelper('question');

        $dirName = dirname($fileFullPath);

        if (!file_exists($dirName)) {

            $question = new ConfirmationQuestion("This folder <info>\"$dirName\"</info> doesn't exist \n<question>do you want to create a new one?</question> ", false);
            if (!$helper->ask($this->input, $this->output, $question)) {
                throw new \Exception('Folder is required');
            }

            mkdir($dirName, 0777, true);
        }

        if (file_exists($fileFullPath)) {
            $question = new ConfirmationQuestion("WARNING, the file <info>$fileFullPath</info> exist\n<question>do you want overwrite?</question> ", false);

            if (!$helper->ask($this->input, $this->output, $question)) {
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
                if ($property->isAssociationMapping()) {
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
}