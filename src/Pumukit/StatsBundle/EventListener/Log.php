<?php

namespace Pumukit\StatsBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;

class Log
{
    private $dm;
    private $requestStack;
    private $tokenStorage;

    public function __construct(DocumentManager $documentManager, RequestStack $requestStack, TokenStorage $tokenStorage)
    {
        $this->dm = $documentManager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }


    public function onMultimediaObjectViewed(ViewedEvent $event)
    {
        $req = $this->requestStack->getMasterRequest();
        $track = $event->getTrack();
        if(!$this->isViewableTrack($track))
            return;

        $log = new ViewsLog($req->getUri(),
                            $req->getClientIp(),
                            $req->headers->get('user-agent'),
                            $req->headers->get('referer'),
                            $event->getMultimediaObject()->getId(),
                            $event->getMultimediaObject()->getSeries()->getId(),
                            $event->getTrack() ? $event->getTrack()->getId() : null,
                            $this->getUser());

        $this->dm->persist($log);
        $this->dm->flush();
    }


    private function getUser()
    {
        if (null !== $token = $this->tokenStorage->getToken()) {
            if (is_object($user = $token->getUser())) {
                return $user->getUsername();
            }
        }

        return null;
    }

    /**
     * Check if the track is 'viewable'
     *
     * @param Track $track
     * @return boolean
     */
    private function isViewableTrack(Track $track = null)
    {
        //'presentation/delivery' corresponds to the opencast slides, thus should not be counted
        return !$track || !$track->containsTag('presentation/delivery');
    }
}