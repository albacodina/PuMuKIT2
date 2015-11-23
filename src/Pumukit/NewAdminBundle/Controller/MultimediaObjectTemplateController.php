<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectTemplateMetaType;

class MultimediaObjectTemplateController extends MultimediaObjectController
{
    /**
     * Display the form for editing or update the resource.
     */
    public function updatemetaAction(Request $request)
    {
        $config = $this->getConfiguration();

        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $roles = $personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $sessionId = $this->get('session')->get('admin/series/id', null);
        $series = $factoryService->findSeriesById($request->get('seriesId'), $sessionId);

        if (null === $series) {
            throw new \Exception('Series with id '.$request->get('seriesId').' or with session id '.$sessionId.' not found.');
        }
        $this->get('session')->set('admin/series/id', $series->getId());

        $parentTags = $factoryService->getParentTags();
        $mmtemplate = $factoryService->getMultimediaObjectPrototype($series);

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(new MultimediaObjectTemplateMetaType($translator, $locale), $mmtemplate);

        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $method = $request->getMethod();
        if (in_array($method, array('POST', 'PUT', 'PATCH')) &&
            $formMeta->submit($request, !$request->isMethod('PATCH'))->isValid()) {
            $this->domainManager->update($mmtemplate);

            if ($config->isApiRequest()) {
                return $this->handleView($this->view($formMeta));
            }

            return new JsonResponse(array('mmtemplate' => 'updatemeta'));
        }

        if ($config->isApiRequest()) {
            return $this->handleView($this->view($formMeta));
        }

        return $this->render('PumukitNewAdminBundle:MultimediaObjectTemplate:edit.html.twig',
                             array(
                                   'mm' => $resource,
                                   'form_meta' => $formMeta->createView(),
                                   'series' => $series,
                                   'roles' => $roles,
                                   'pub_decisions' => $pubDecisionsTags,
                                   'parent_tags' => $parentTags,
                                   )
                             );
    }
}
