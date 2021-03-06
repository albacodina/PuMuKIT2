<?php

namespace Pumukit\LiveBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\LiveBundle\Document\Live;
use Pumukit\LiveBundle\Services\LiveService;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;

class LiveTwigExtension extends \Twig_Extension
{
    private $dm;
    private $liveService;
    private $eventsService;

    public function __construct(DocumentManager $documentManager, LiveService $liveService, EmbeddedEventSessionService $eventsService)
    {
        $this->dm = $documentManager;
        $this->liveService = $liveService;
        $this->eventsService = $eventsService;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('generate_hls_url', array($this, 'genHlsUrl')),
            new \Twig_SimpleFunction('future_and_not_finished_event', array($this, 'getFutureAndNotFinishedEvent')),
            new \Twig_SimpleFunction('poster_pic', array($this, 'getPosterPic')),
            new \Twig_SimpleFunction('poster_pic_text_color', array($this, 'getPosterPicTextColor')),
            new \Twig_SimpleFunction('poster', array($this, 'getEventPoster')),
            new \Twig_SimpleFunction('poster_text_color', array($this, 'getPosterTextColor')),
            new \Twig_SimpleFunction('event_first_thumbnail', array($this, 'getEventThumbnail')),
        );
    }

    /**
     * Generate HLS URL from RTMP url.
     *
     * Original twig template:
     *    {{ live.url|replace({'rtmp://':'http://', 'rtmpt://': 'http://'}) }}/{{ live.sourcename }}/playlist.m3u8
     *
     * @param Live $live
     *
     * @return string
     */
    public function genHlsUrl(Live $live)
    {
        return $this->liveService->generateHlsUrl($live);
    }

    /**
     * Get future and not finished event.
     *
     * @param int|null  $limit
     * @param Live|null $live
     *
     * @return mixed
     */
    public function getFutureAndNotFinishedEvent($limit = null, Live $live = null)
    {
        $eventRepo = $this->dm->getRepository('PumukitLiveBundle:Event');

        return $eventRepo->findFutureAndNotFinished($limit, null, $live);
    }

    /**
     * @param EmbeddedEvent $event
     *
     * @Deprecated use getPosterPic
     *
     * @return string
     */
    public function getEventPoster(EmbeddedEvent $event)
    {
        return $this->eventsService->getEventPoster($event);
    }

    /**
     * Get event poster.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getPosterPic(MultimediaObject $multimediaObject)
    {
        return $this->eventsService->getEventPicPoster($multimediaObject);
    }

    /**
     * @param EmbeddedEvent $event
     *
     * @Deprecated use getPosterPicTextColor
     *
     * @return string
     */
    public function getPosterTextColor(EmbeddedEvent $event)
    {
        return $this->eventsService->getPosterTextColor($event);
    }

    /**
     * Get poster text color.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getPosterPicTextColor(MultimediaObject $multimediaObject)
    {
        return $this->eventsService->getPicPosterTextColor($multimediaObject);
    }

    /**
     * Get event thumbnail.
     *
     * @param EmbeddedEvent|array $event
     *
     * @return string
     */
    public function getEventThumbnail($event)
    {
        if (!is_array($event)) {
            return $this->eventsService->getEventThumbnail($event);
        } else {
            return $this->eventsService->getEventThumbnailByEventId($event['event']['_id']);
        }
    }
}
