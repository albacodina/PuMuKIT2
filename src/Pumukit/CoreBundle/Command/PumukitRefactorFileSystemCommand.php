<?php

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Input\InputOption;
use UnexpectedValueException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class PumukitRefactorFileSystemCommand.
 */
class PumukitRefactorFileSystemCommand extends ContainerAwareCommand
{
    private $dm;
    private $output;
    private $input;
    private $finder;
    private $pics;
    private $materials;
    private $logger;
    private $force;
    private $id;
    private $regex = '/^[0-9a-z]{24}$/';
    private $allowedTypes = array('pics', 'materials');

    protected function configure()
    {
        $this
            ->setName('pumukit:files:refactor:path')
            ->setDescription('Pumukit refactor wrongs path for images and materials')
            ->addOption('pics', null, InputOption::VALUE_NONE, 'Refactor pics')
            ->addOption('materials', null, InputOption::VALUE_NONE, 'Refactor materials')
            ->addOption('id', null, InputOption::VALUE_NONE, 'Filter by id')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Use this to execute command')
            ->setHelp(
            <<<'EOT'

                Command to refactor path of pics and materials on pumukit. The command will change element path from old versions
                to the new element path on pumukit new versions.

                Old Path:
                    element.path = "element/uid/file.ext"
                New path:
                    element.path = "element/uidseries/video/uidvideo/file.ext"

                Show change on: https://github.com/campusdomar/PuMuKIT2/commit/bd63851ce2a9d44be90017a0db0d5e073b55dec5#diff-2cb454b02139985bdcb5f15387a4be64

                Examples:

                1. List the refactor pics
                    php app/console pumukit:files:refactor:path --pics
                2. List the refactor materials
                    php app/console pumukit:files:refactor:path --materials
                3. List both
                    php app/console pumukit:files:refactor:path --pics --materials

                Example to execute:

                1. Refactor pics
                    php app/console pumukit:files:refactor:path --pics --force
                2. Refactor materials
                    php app/console pumukit:files:refactor:path --materials --force
                3. Refactor both
                    php app/console pumukit:files:refactor:path --pics --materials --force

EOT
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->output = $output;
        $this->input = $input;

        $this->pics = $this->input->getOption('pics');
        $this->materials = $this->input->getOption('materials');
        $this->id = $this->input->getOption('id');
        $this->force = (true === $input->getOption('force'));
        $this->finder = new Finder();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkInputs();

        $this->repairMongoDBPicsAndMaterial($this->dm);

        if ($this->pics) {
            $message = '***** List pics paths to refactor *****';
            if ($this->force) {
                $message = '***** Refactor pics paths *****';
            }

            $this->showMessage($output, $message);
            $this->refactorPicsPath($output, $this->dm);
            $this->showMessage($output, '----- Refactor pics done -----');
        }

        if ($this->materials) {
            $output->writeln('Trying to refactor materials paths ...');
            try {
                $this->refactorMaterialsPath($output, $this->dm);
            } catch (\Exception $exception) {
                throw new \Exception($exception->getMessage());
            }

            $this->output->writeln('Refactor materials done');
        }
    }

    /**
     * @throws \Exception
     */
    private function checkInputs()
    {
        if (!$this->pics && !$this->materials) {
            throw new \Exception('Please select one type ( pics or materials )');
        }

        if ($this->id && !preg_match($this->regex, $this->id)) {
            throw new \Exception('Param ID is not a valid objectID');
        }
    }

    /**
     * Function to repair path on MongoDB.
     *
     * @param DocumentManager $documentManager
     *
     * @throws \Exception
     */
    private function repairMongoDBPicsAndMaterial(DocumentManager $documentManager)
    {
        if ($this->pics) {
            $this->repairMongoDB($documentManager, 'pics');
        }

        if ($this->materials) {
            $this->repairMongoDB($documentManager, 'materials');
        }
    }

    /**
     * @param DocumentManager $documentManager
     * @param                 $type
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function repairMongoDB(DocumentManager $documentManager, $type)
    {
        if ('pics' === $type) {
            $multimediaObjects = $this->findPicsWithoutPaths($documentManager);
        } else {
            $multimediaObjects = $this->findMaterialsWithoutPaths($documentManager);
        }

        if (!$multimediaObjects) {
            $this->showMessage($this->output, 'No multimedia objects found');

            return true;
        }

        foreach ($multimediaObjects as $multimediaObject) {
            $this->fixPathMultimediaObject($documentManager, $multimediaObject, $type);
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param                 $message
     */
    private function showMessage(OutputInterface $output, $message)
    {
        $output->writeln($message);
    }

    /**
     * @param DocumentManager  $documentManager
     * @param MultimediaObject $multimediaObject
     * @param                  $type
     *
     * @throws \Exception
     */
    private function fixPathMultimediaObject(DocumentManager $documentManager, MultimediaObject $multimediaObject, $type)
    {
        if (!in_array($type, $this->allowedTypes)) {
            throw new \Exception('Types cant be distinct of '.implode(' or ', $this->allowedTypes));
        }

        if ('pics' === $type) {
            $elements = $multimediaObject->getPics();
        } else {
            $elements = $multimediaObject->getMaterials();
        }

        $this->fixPath($documentManager, $elements, $type);
    }

    /**
     * @param DocumentManager $documentManager
     * @param                 $elements
     * @param                 $type
     */
    private function fixPath(DocumentManager $documentManager, $elements, $type)
    {
        $haveChanges = false;
        foreach ($elements as $elem) {
            $path = $elem->getPath();
            if (!isset($path) && false !== stripos($elem->getUrl(), '/uploads/pic/')) {
                $path = realpath($this->getContainer()->getParameter('kernel.root_dir').'/../web'.$elem->getUrl());
                $checkFile = $this->checkFileExists($path);
                if ($checkFile && $this->force) {
                    $message = 'Adding path '.$path.' form pic with ID - '.$elem->getId();
                    $elem->setPath($path);
                    $haveChanges = true;
                } elseif (!$checkFile) {
                    $message = "$type (".$elem->getId().') - URL <comment>('.$elem->getUrl().'</comment>) doesnt exists on filesystem.';
                } else {
                    $message = "<info>$type (".$elem->getId().') - URL ('.$elem->getUrl().') </info>';
                }
                $this->showMessage($this->output, $message);
            }
        }

        if ($haveChanges && $this->force) {
            $documentManager->flush();
        }
    }

    /**
     * @param OutputInterface $output
     * @param DocumentManager $documentManager
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function refactorPicsPath(OutputInterface $output, DocumentManager $documentManager)
    {
        $multimediaObjects = $this->findWrongPathPics($documentManager);

        foreach ($multimediaObjects as $multimediaObject) {
            $haveChanges = false;
            foreach ($multimediaObject['pics'] as $pic) {
                if (!isset($pic['path'])) {
                    continue;
                }

                $multimediaObjectId = $multimediaObject['_id']->{'$id'};
                if (false === stripos($pic['url'], '/pic/series/')) {
                    $oldDirname = $pic['path'];
                    $seriesID = $multimediaObject['series'][0]->{'$id'};

                    if (!$seriesID) {
                        $this->showMessage($output, "<error>There aren't series ID for multimediaObject ".$multimediaObjectId.'</error>');
                        continue;
                    }

                    $replaceString = '/pic/series/'.$seriesID.'/video/';
                    $newPath = str_replace('/pic/', $replaceString, $pic['path']);
                    $newPath = str_replace(' ', '_', $newPath);
                    $newUrl = str_replace('/pic/', $replaceString, $pic['url']);
                    $newUrl = str_replace(' ', '_', $newUrl);

                    if ($this->checkFileExists($pic['path'])) {
                        try {
                            $this->moveElement($pic['path'], $newPath);
                        } catch (\Exception $exception) {
                            $this->showMessage($output, '<warning> Pic ('.$pic['id'].') not exists '.$pic['path']);
                            continue;
                        }
                    }

                    try {
                        $this->updateMultimediaObjectPic($documentManager, $multimediaObjectId, $pic['path'], $newPath, $newUrl);
                    } catch (\Exception $exception) {
                        $this->showMessage($output, 'Cant update mmobj '.$multimediaObjectId.' with the new path of the pic '.$pic['path']);
                        continue;
                    }

                    $haveChanges = true;
                }
            }

            if ($haveChanges && isset($oldDirname)) {
                $this->deleteDirectory($output, $haveChanges, $oldDirname);
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param DocumentManager $documentManager
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function refactorMaterialsPath(OutputInterface $output, DocumentManager $documentManager)
    {
        $multimediaObjects = $this->findWrongPathMaterials($documentManager);

        foreach ($multimediaObjects as $multimediaObject) {
            $haveChanges = false;
            foreach ($multimediaObject['materials'] as $material) {
                if (!isset($material['path'])) {
                    continue;
                }

                $multimediaObjectId = $multimediaObject['_id']->{'$id'};
                if (false === stripos($material['url'], '/material/series/')) {
                    $oldDirname = $material['path'];
                    $seriesID = $multimediaObject['series'][0]->{'$id'};

                    if (!$seriesID) {
                        $this->showMessage($output, "<error>There aren't series ID for multimediaObject ".$multimediaObjectId.'</error>');
                        continue;
                    }

                    $replaceString = '/material/series/'.$seriesID.'/video/';
                    $newPath = str_replace('/material/', $replaceString, $material['path']);
                    $newPath = str_replace(' ', '_', $newPath);
                    $newUrl = str_replace('/material/', $replaceString, $material['url']);
                    $newUrl = str_replace(' ', '_', $newUrl);

                    if ($this->checkFileExists($material['path'])) {
                        try {
                            $this->moveElement($material['path'], $newPath);
                        } catch (\Exception $exception) {
                            $this->showMessage($output, '<warning> Material ('.$material['id'].') not exists '.$material['path']);
                            continue;
                        }
                    }

                    try {
                        $this->updateMultimediaObjectMaterial($documentManager, $multimediaObjectId, $material['path'], $newPath, $newUrl);
                    } catch (\Exception $exception) {
                        $this->showMessage($output, 'Cant update mmobj '.$multimediaObjectId.' with the new path of the material '.$material['path']);
                        continue;
                    }

                    $haveChanges = true;
                }
            }

            if ($haveChanges && isset($oldDirname)) {
                $this->deleteDirectory($output, $haveChanges, $oldDirname);
            }
        }
    }

    /**
     * Returns multimedia objects with pics without paths.
     *
     * @param DocumentManager $documentManager
     *
     * @return array|MultimediaObject[]|\Pumukit\SchemaBundle\Document\Role[]
     */
    private function findPicsWithoutPaths(DocumentManager $documentManager)
    {
        $multimediaObjects = $documentManager->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(
            array(
                'pics.url' => new \MongoRegex('/uploads/pic/'),
                'pics.path' => array('$exists' => false),
            )
        );

        return $multimediaObjects;
    }

    /**
     * Returns multimedia objects with materials without paths.
     *
     * @param DocumentManager $documentManager
     *
     * @return array|MultimediaObject[]|\Pumukit\SchemaBundle\Document\Role[]
     */
    private function findMaterialsWithoutPaths(DocumentManager $documentManager)
    {
        $multimediaObjects = $documentManager->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(
            array(
                'materials.url' => new \MongoRegex('/uploads/material/'),
                'materials.path' => array('$exists' => false),
            )
        );

        return $multimediaObjects;
    }

    /**
     * @param DocumentManager $documentManager
     *
     * @return \Doctrine\MongoDB\Iterator
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function findWrongPathPics(DocumentManager $documentManager)
    {
        $collection = $documentManager->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');

        $pipeline = array(
            array(
                '$match' => array(
                    'pics.path' => array('$regex' => '/uploads/pic/', '$options' => 'i'),
                ),
            ),
        );
        array_push($pipeline, array('$unwind' => '$pics'));

        $group = array(
            '$group' => array(
                '_id' => '$_id',
                'series' => array('$addToSet' => '$series'),
                'pics' => array('$addToSet' => '$pics'),
            ),
        );

        array_push($pipeline, $group);
        $pics = $collection->aggregate($pipeline, array('cursor' => array()));

        return $pics;
    }

    /**
     * @param DocumentManager $documentManager
     *
     * @return \Doctrine\MongoDB\Iterator
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function findWrongPathMaterials(DocumentManager $documentManager)
    {
        $collection = $documentManager->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');

        $pipeline = array(
            array(
                '$match' => array(
                    'materials' => array('$exists' => true),
                    'materials.path' => array('$regex' => 'uploads/material/', '$options' => 'i'),
                ),
            ),
        );
        array_push($pipeline, array('$unwind' => '$materials'));

        $group = array(
            '$group' => array(
                '_id' => '$_id',
                'series' => array('$addToSet' => '$series'),
                'materials' => array('$addToSet' => '$materials'),
            ),
        );

        array_push($pipeline, $group);
        $materials = $collection->aggregate($pipeline, array('cursor' => array()));

        return $materials;
    }

    /**
     * @param $path
     *
     * @return bool
     */
    private function checkFileExists($path)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists($path)) {
            return true;
        }

        return false;
    }

    /**
     * @param $oldPath
     * @param $newPath
     *
     * @throws \Exception
     */
    private function moveElement($oldPath, $newPath)
    {
        $dirName = dirname($newPath);

        if (!$this->checkFileExists($dirName)) {
            if (mkdir($dirName, 0755, true)) {
                $this->createProcessToMove($oldPath, $newPath);
            } else {
                throw new \Exception('Error trying to create folders - '.$dirName);
            }
        }

        $this->createProcessToMove($oldPath, $newPath);
    }

    /**
     * @param $oldPath
     * @param $newPath
     *
     * @return mixed
     */
    private function createProcessToMove($oldPath, $newPath)
    {
        $parameters = array(
            $oldPath,
            $newPath,
        );

        $builder = new ProcessBuilder();
        $builder->setPrefix('mv');
        $builder->setArguments($parameters);

        $builder->setTimeout(3600);
        $process = $builder->getProcess();
        try {
            $process->mustRun();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $aResult = json_decode($process->getOutput(), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new UnexpectedValueException(json_last_error_msg());
            }

            return $aResult;
        } catch (ProcessFailedException $exception) {
            echo $exception->getMessage();
        }

        return null;
    }

    /**
     * @param DocumentManager $documentManager
     * @param                 $multimediaObjectId
     * @param                 $oldPath
     * @param                 $newPath
     * @param                 $newUrl
     */
    private function updateMultimediaObjectPic(DocumentManager $documentManager, $multimediaObjectId, $oldPath, $newPath, $newUrl)
    {
        $multimediaObject = $documentManager->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
            array('_id' => new \MongoId($multimediaObjectId))
        );

        foreach ($multimediaObject->getPics() as $pic) {
            if ($pic->getPath() && $pic->getPath() === $oldPath) {
                $pic->setPath($newPath);
                $pic->setUrl($newUrl);
            }
        }

        $documentManager->flush();
    }

    /**
     * @param DocumentManager $documentManager
     * @param                 $multimediaObjectId
     * @param                 $oldPath
     * @param                 $newPath
     * @param                 $newUrl
     */
    private function updateMultimediaObjectMaterial(DocumentManager $documentManager, $multimediaObjectId, $oldPath, $newPath, $newUrl)
    {
        $multimediaObject = $documentManager->getRepository('PumukitSchemaBundle:MultimediaObject')->findOneBy(
            array('_id' => new \MongoId($multimediaObjectId))
        );

        foreach ($multimediaObject->getMaterials() as $material) {
            if ($material->getPath() && $material->getPath() === $oldPath) {
                $material->setPath($newPath);
                $material->setUrl($newUrl);
            }
        }

        $documentManager->flush();
    }

    /**
     * @param OutputInterface $output
     * @param                 $haveChanges
     * @param                 $oldDirName
     *
     * @return bool
     */
    private function deleteDirectory(OutputInterface $output, $haveChanges, $oldDirName)
    {
        if ($haveChanges && isset($oldDirName)) {
            if ($this->checkFileExists(dirname($oldDirName))) {
                rmdir(dirname($oldDirName));

                return true;
            }
            $this->showMessage($output, 'File or directory ( '.$oldDirName.' ) doesnt exists');
        }

        return false;
    }
}
