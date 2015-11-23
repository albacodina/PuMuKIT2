<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\NewAdminBundle\Form\Type\PersonType;

class PersonController extends AdminController
{
    /**
     * Index.
     *
     * @Template("PumukitNewAdminBundle:Person:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config, $request->getLocale());
        $resources = $this->getResources($request, $config, $criteria);

        $personService = $this->get('pumukitschema.person');
        $countMmPeople = array();
        foreach ($resources as $person) {
            $countMmPeople[$person->getId()] = $personService->countMultimediaObjectsWithPerson($person);
        }

        return array(
                     'people' => $resources,
                     'countMmPeople' => $countMmPeople,
                     );
    }

    /**
     * Create new person.
     *
     * @Template("PumukitNewAdminBundle:Person:create.html.twig")
     */
    public function createAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $person = new Person();
        $form = $this->createForm(new PersonType($translator, $locale), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            if ($form->bind($request)->isValid()) {
                try {
                    $person = $personService->savePerson($person);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
            } else {
                $errors = $this->get('validator')->validate($person);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
        }

        return array(
                     'person' => $person,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Update person.
     *
     * @Template("PumukitNewAdminBundle:Person:update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new PersonType($translator, $locale), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            if ($form->bind($request)->isValid()) {
                try {
                    $person = $personService->updatePerson($person);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
            } else {
                $errors = $this->get('validator')->validate($person);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
        }

        return array(
                     'person' => $person,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Show person.
     *
     * @Template("PumukitNewAdminBundle:Person:show.html.twig")
     */
    public function showAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $limit = 5;
        $series = $personService->findSeriesWithPerson($person, $limit);

        return array(
                     'person' => $person,
                     'series' => $series,
                     );
    }

    /**
     * List people.
     *
     * @Template("PumukitNewAdminBundle:Person:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();

        $sorting = $request->get('sorting');
        if (null !== $sorting) {
            $this->get('session')->set('admin/person/type', $sorting[key($sorting)]);
            $this->get('session')->set('admin/person/sort', key($sorting));
        }

        $criteria = $this->getCriteria($config, $request->getLocale());
        $resources = $this->getResources($request, $config, $criteria);

        $personService = $this->get('pumukitschema.person');
        $countMmPeople = array();
        foreach ($resources as $person) {
            $countMmPeople[$person->getId()] = $personService->countMultimediaObjectsWithPerson($person);
        }

        return array(
                     'people' => $resources,
                     'countMmPeople' => $countMmPeople,
                     );
    }

    /**  
     * Create new person with role from Multimedia Object.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitNewAdminBundle:Person:listautocomplete.html.twig")
     */
    public function listAutocompleteAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();

        $criteria = $this->getCriteria($config, $request->getLocale());
        $resources = $this->getResources($request, $config, $criteria);

        $template = $multimediaObject->isPrototype() ? '_template' : '';
        $ldapEnabled = $this->container->has('pumukit_ldap.ldap');

        return array(
                     'people' => $resources,
                     'mm' => $multimediaObject,
                     'role' => $role,
                     'template' => $template,
                     'ldap_enabled' => $ldapEnabled,
                     );
    }

    /**
     * Create relation.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitNewAdminBundle:Person:createrelation.html.twig")
     */
    public function createRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $person = new Person();
        $person->setName(preg_replace('/\d+ - /', '', $request->get('name')));

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new PersonType($translator, $locale), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            if ($form->bind($request)->isValid()) {
                try {
                    $personService = $this->get('pumukitschema.person');
                    $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                $template = $multimediaObject->isPrototype() ? '_template' : '';
            } else {
                $errors = $this->get('validator')->validate($person);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }

            return $this->render('PumukitNewAdminBundle:Person:listrelation.html.twig',
                                 array(
                                       'people' => $multimediaObject->getPeopleByRole($role, true),
                                       'role' => $role,
                                       'mm' => $multimediaObject,
                                       'template' => $template,
                                       ));
        }

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        return array(
                     'person' => $person,
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Update relation.
     *
     * @Template("PumukitNewAdminBundle:Person:updaterelation.html.twig")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function updateRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new PersonType($translator, $locale), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            if ($form->bind($request)->isValid()) {
                try {
                    $person = $personService->updatePerson($person);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                $template = $multimediaObject->isPrototype() ? '_template' : '';

                return $this->render('PumukitNewAdminBundle:Person:listrelation.html.twig',
                                     array(
                                           'people' => $multimediaObject->getPeopleByRole($role, true),
                                           'role' => $role,
                                           'mm' => $multimediaObject,
                                           'template' => $template,
                                           ));
            } else {
                $errors = $this->get('validator')->validate($person);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
        }

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        return array(
                     'person' => $person,
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Link person to multimedia object with role.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function linkAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        try {
            $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
            // TODO Snackbars and toasts
            //$message = sprintf($this->getContext()->getI18N()->__("Persona asociada correctamente a la plantilla con el rol \"%s\"."), $this->role->getName());
            //$msg_alert = array('info', $message);
        } catch (\Excepction $e) {
            //$message = sprintf($this->getContext()->getI18N()->__("Persona ya asociada a la plantilla con el rol \"%s\"."), $this->role->getName());
            //$this->msg_alert = array('error', $message);          
        }

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }

        return $this->render('PumukitNewAdminBundle:Person:listrelation.html.twig',
                             array(
                                   'people' => $multimediaObject->getPeopleByRole($role, true),
                                   'role' => $role,
                                   'mm' => $multimediaObject,
                                   'template' => $template,
                                   ));
    }

    /**
     * Auto complete.
     */
    public function autoCompleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $name = $request->get('term');
        $people = $personService->autoCompletePeopleByName($name);

        $out = [];
        foreach ($people as $p) {
            $out[] = array(
                           'id' => $p->getId(),
                           'label' => $p->getName(),
                           'desc' => $p->getPost().' '.$p->getFirm(),
                           'value' => $p->getName(),
                           );
        }

        return new JsonResponse($out);
    }

    /**
     * Up person in MultimediaObject.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $multimediaObject = $personService->upPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }

        return $this->render('PumukitNewAdminBundle:Person:listrelation.html.twig',
                             array(
                                   'people' => $multimediaObject->getPeopleByRole($role, true),
                                   'role' => $role,
                                   'mm' => $multimediaObject,
                                   'template' => $template,
                                   ));
    }

    /**
     * Down person in MultimediaObject.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $multimediaObject = $personService->downPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }

        return $this->render('PumukitNewAdminBundle:Person:listrelation.html.twig',
                             array(
                                   'people' => $multimediaObject->getPeopleByRole($role, true),
                                   'role' => $role,
                                   'mm' => $multimediaObject,
                                   'template' => $template,
                                   ));
    }

    /**
     * Delete relation: EmbeddedPerson in Multimedia Object.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitNewAdminBundle:Person:listrelation.html.twig")
     */
    public function deleteRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $multimediaObject = $personService->deleteRelation($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }

        return array(
                     'people' => $multimediaObject->getPeopleByRole($role, true),
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template,
                     );
    }

    /**
     * Delete Person.
     *
     * @Template("PumukitNewAdminBundle:Person:list.html")
     */
    public function deleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        try {
            if (0 === $personService->countMultimediaObjectsWithPerson($person)) {
                $personService->deletePerson($person);
            }
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
    }

    /**
     * Gets the criteria values.
     */
    public function getCriteria($config, $locale = 'en')
    {
        $criteria = $config->getCriteria();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/person/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/person/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/person/criteria', array());

        $new_criteria = array();

        if (array_key_exists('name', $criteria) && array_key_exists('letter', $criteria)) {
            if (('' !== $criteria['name']) && ('' !== $criteria['letter'])) {
                $more = strtoupper($criteria['name'][0]) == strtoupper($criteria['letter']) ? '|^'.$criteria['name'].'.*' : '';
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'.*'.$criteria['name'].'.*'.$more.'/i');
            } elseif ('' !== $criteria['name']) {
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'/i');
            } elseif ('' !== $criteria['letter']) {
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'/i');
            }
        } elseif (array_key_exists('name', $criteria)) {
            if ('' !== $criteria['name']) {
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'/i');
            }
        } elseif (array_key_exists('letter', $criteria)) {
            if ('' !== $criteria['letter']) {
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'/i');
            }
        }

        if (array_key_exists('post', $criteria)) {
            if ('' !== $criteria['post']) {
                $new_criteria['post.'.$locale] = new \MongoRegex('/'.$criteria['post'].'/i');
            }
        }

        return $new_criteria;
    }

    /**
     * Gets the list of resources according to a criteria.
     */
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $config->getSorting();
        $repository = $this->getRepository();

        if ($config->isPaginated()) {
            $resources = $this
              ->resourceResolver
              ->getResource($repository, 'createPaginator', array($criteria, $sorting))
              ;

            if ($request->get('page', null)) {
                $this->get('session')->set('admin/person/page', $request->get('page', 1));
            }

            $resources
              ->setMaxPerPage($config->getPaginationMaxPerPage())
              ->setNormalizeOutOfRangePages(true)
              ->setCurrentPage($this->get('session')->get('admin/person/page', 1));
        } else {
            $resources = $this
              ->resourceResolver
              ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()))
              ;
        }

        return $resources;
    }
}
