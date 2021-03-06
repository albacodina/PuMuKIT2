<?php

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class PersonalFilter extends WebTVFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
        if ("Pumukit\SchemaBundle\Document\Series" === $targetDocument->reflClass->name) {
            return $this->getSeriesCriteria();
        }
    }

    private function getMultimediaObjectCriteria()
    {
        $criteria = array();
        $criteria_portal = $this->getCriteria();
        $criteria_backoffice = array();
        if (isset($this->parameters['people']) && isset($this->parameters['groups'])) {
            $criteria_backoffice['$or'] = array(
                array('people' => $this->parameters['people']),
                array('groups' => $this->parameters['groups']),
            );
        }
        if ($criteria_portal && $criteria_backoffice) {
            $criteria['$or'] = array($criteria_portal, $criteria_backoffice);
        } else {
            $criteria = $criteria_portal ?: $criteria_backoffice;
        }

        return $criteria;
    }

    private function getSeriesCriteria()
    {
        $criteria = array();
        if (isset($this->parameters['person_id']) && isset($this->parameters['role_code']) && isset($this->parameters['series_groups'])) {
            $criteria['_id'] = $this->getSeriesMongoQuery($this->parameters['person_id'], $this->parameters['role_code'], $this->parameters['series_groups']);
        }

        return $criteria;
    }

    /**
     * Get series mongo query
     * Match the Series
     * with given ids.
     *
     * Query in MongoDB:
     * db.Series.find({ "_id": { "$in": [ ObjectId("__id_1__"), ObjectId("__id_2__")... ] } });
     *
     * @param $personId
     * @param string $roleCode
     * @param array  $groups
     *
     * @return array
     */
    private function getSeriesMongoQuery($personId, $roleCode, $groups)
    {
        $seriesIds = array();
        if ((null !== $personId) && (null !== $roleCode)) {
            $repoMmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
            $referencedSeries = $repoMmobj->findSeriesFieldByPersonIdAndRoleCodOrGroups($personId, $roleCode, $groups);
            $seriesIds['$in'] = $referencedSeries->toArray();
        }

        return $seriesIds;
    }
}
