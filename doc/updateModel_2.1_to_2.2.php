#!/usr/bin/env php
<?php
// application.php

set_time_limit(0);

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;
use Pumukit\SchemaBundle\Document\Person;


class MyLocalCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:model:2.1to2.2')
            ->setDescription('Update ViewsLog documents.')
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateViewsLog();
        $output->writeln('Mongo ViewsLog collection updated');
        $this->updateUserPerson();
        $output->writeln('Mongo User and Person collections updated');
    }

    protected function updateViewsLog()
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $seriesAux = $dm->createQueryBuilder('PumukitSchemaBundle:MultimediaObject')
          ->hydrate(false)
          ->select('series')
          ->getQuery()
          ->execute();

        foreach($seriesAux as $s){
          
          $dm->createQueryBuilder('PumukitStatsBundle:ViewsLog')
            ->update()
            ->multiple(true)
            ->field('series')->set($s['series'])
            ->field('multimediaObject')->equals((string)$s['_id'])
            ->getQuery()
            ->execute();

          $dm->createQueryBuilder('PumukitStatsBundle:ViewsLog')
            ->update()
            ->multiple(true)
            ->field('multimediaObject')->set($s['_id'])
            ->field('multimediaObject')->equals((string)$s['_id'])
            ->getQuery()
            ->execute();

        }
    }

    protected function updateUserPerson()
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $personRepo = $dm->getRepository('PumukitSchemaBundle:Person');
        $permProfRepo = $dm->getRepository('PumukitSchemaBundle:PermissionProfile');
        $userService = $this->getContainer()->get('pumukitschema.user');
        
        $permPublisher = $permProfRepo->findOneByName('Publisher');
        $permViewer = $permProfRepo->findOneByName('Viewer');
        if(!$permPublisher) {
            throw new \RuntimeException('The "Publisher" Permision Profile is not set. Did you initialize the Permission Profiles repo? (pumukit:init:repo permissionprofile --force)');
        }
        if(!$permViewer) {
            throw new \RuntimeException('The "Viewer" Permision Profile is not set. Did you initialize the Permission Profiles repo? (pumukit:init:repo permissionprofile --force)');
        }

        $allUsers = $dm->createQueryBuilder('PumukitSchemaBundle:User')
                  ->getQuery()
                  ->execute();

        foreach($allUsers as $user) {
            if($user->getPermissionProfile()) {
                continue;
            }
            $people = $personRepo->createQueryBuilder()
                    ->field('email')->equals($user->getEmail())
                    ->getQuery()
                    ->execute()
                    ->toArray();

            //Prepare person
            if(isset($people[0])) {
                $person = $people[0];
            }
            else {
                $person = new Person();
                $person->setName($user->getFullname());
                $person->setEmail($user->getEmail());
            }
            //Set the person user
            $person->setUser($user);
            $dm->persist($person);
            
            //Set the user person and find the permission profile.
            $user->setPerson($person);
            $userRoles = $user->getRoles();
            if(in_array('ROLE_SUPER_ADMIN', $userRoles)) {
                continue;
            }
            else if(in_array('ROLE_ADMIN', $userRoles)){
                $permissionProfile = $permPublisher;
            }
            else {
                $permissionProfile = $permViewer;
            }
            var_dump($permissionProfile->getName());
            $user->setPermissionProfile($permissionProfile);
            $userService->update($user);
            var_dump($user->getRoles());
            $dm->persist($user);
            $dm->flush();
        }
    }
}


$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
$debug = getenv('SYMFONY_DEBUG') !== '0' && !$input->hasParameterOption(array('--no-debug', '')) && $env !== 'prod';

if ($debug) {
    Debug::enable();
}

$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
$application->add(new MyLocalCommand());
$application->run();