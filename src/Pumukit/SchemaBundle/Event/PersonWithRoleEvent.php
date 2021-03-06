<?php

namespace Pumukit\SchemaBundle\Event;

use Pumukit\SchemaBundle\Document\EmbeddedPerson;
use Pumukit\SchemaBundle\Document\EmbeddedRole;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Symfony\Component\EventDispatcher\Event;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PersonWithRoleEvent extends Event
{
    /**
     * @var MultimediaObject
     */
    protected $multimediaObject;

    /**
     * @var Person
     */
    protected $person;

    /**
     * @var Role
     */
    protected $role;

    /**
     * @param MultimediaObject      $multimediaObject
     * @param Person|EmbeddedPerson $person
     * @param Role|EmbeddedRole     $role
     */
    public function __construct(MultimediaObject $multimediaObject, $person, $role)
    {
        $this->multimediaObject = $multimediaObject;
        $this->person = $person;
        $this->role = $role;
    }

    /**
     * @return MultimediaObject
     */
    public function getMultimediaObject()
    {
        return $this->multimediaObject;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }
}
