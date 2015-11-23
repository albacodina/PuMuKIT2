<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class InboxController extends Controller
{
    /**
     * @Route("/inbox", defaults={"_format"="json"})
     */
    public function dirAction(Request $request)
    {
        $dir = $request->query->get('dir', '');
        $type = $request->query->get('type', 'file');
        $baseDir = realpath($this->container->getParameter('pumukit2.inbox'));

        /*
        if(0 !== strpos($dir, $baseDir)) {
            throw $this->createAccessDeniedException();
        }
        */

        $finder = new Finder();

        $res = array();

        if ('file' == $type) {
            $finder->depth('< 1')->followLinks()->in($dir);
            $finder->sortByName();
            foreach ($finder as $f) {
                $res[] = array('path' => $f->getRealpath(),
                               'relativepath' => $f->getRelativePathname(),
                               'is_file' => $f->isFile(),
                               'hash' => hash('md5', $f->getRealpath()),
                               'content' => false, );
            }
        } else {
            $finder->depth('< 1')->directories()->followLinks()->in($dir);
            $finder->sortByName();
            foreach ($finder as $f) {
                if (0 !== (count(glob("$f/*")))) {
                    $contentFinder = new Finder();
                    $contentFinder->files()->in($f->getRealpath());
                    $res[] = array('path' => $f->getRealpath(),
                                   'relativepath' => $f->getRelativePathname(),
                                   'is_file' => $f->isFile(),
                                   'hash' => hash('md5', $f->getRealpath()),
                                   'content' => $contentFinder->count(), );
                }
            }
        }

        return new JsonResponse($res);
    }

    /**
     * @Template
     */
    public function formAction($onlyDir = false)
    {
        if (!$this->container->hasParameter('pumukit2.inbox')) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noconf.html.twig');
        }

        $dir = realpath($this->container->getParameter('pumukit2.inbox'));

        if (!file_exists($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_nofile.html.twig', array('dir' => $dir));
        }

        if (!is_readable($dir)) {
            return $this->render('@PumukitNewAdmin/Inbox/form_noperm.html.twig', array('dir' => $dir));
        }

        return $this->render('@PumukitNewAdmin/Inbox/form.html.twig', array('dir' => $dir, 'onlyDir' => $onlyDir));
    }
}
