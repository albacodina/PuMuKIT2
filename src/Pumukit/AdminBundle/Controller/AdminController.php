<?php

namespace Pumukit\AdminBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sylius\Bundle\ResourceBundle\Event\ResourceEvent;

class AdminController extends ResourceController
{

  public function copyAction(Request $request)
  {
        $resource = $this->findOr404();
	
	$new_resource = $resource->cloneDirect();
	
	$this->create($new_resource);
	

	$this->setFlash('success', 'copy');
	
	$config = $this->getConfiguration();
	
	return $this->redirectToRoute(
	   $config->getRedirectRoute('index'),
	   $config->getRedirectParameters()
	);
	


  }

}