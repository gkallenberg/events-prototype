<?php
namespace AppBundle\Utils;

use AppBundle\Entity\EventsSearch;
use Symfony\Component\EventDispatcher\Event;

class Filter
{
    /**
     * @var array
     */
    public $params = [];

    /**
     * @var string
     */
    public $query = '*:*';

    /**
     * @var array
     */
    public $filters = [];

    /**
     * @var array
     */
    public $facetFields = [];

    /**
     * @var array
     */
    public $filterQuery = [];

    /**
     * @var EventsSearch
     */
    public $search;

    public function __construct(EventsSearch $search)
    {
        $this->setSearch($search);
        $this->setQuery($search->getKeyword());
        $this->setFilterQuery([
            'category_id' => ($this->search->getCategory() != 'all') ? $this->search->getCategory() : '',
            'city' => ($this->search->getLocation() != 'all') ? $this->search->getLocation() : '',
            'target_audience' => ($this->search->getAudience() != 'all') ? $this->search->getAudience() : '',
            'date_time_start' => ($this->search->getDate() != 'all') ? $this->search->getDate() : '[NOW-1HOUR TO *]',
            'pub_status' => 1,
        ]);
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function getFacetFields()
    {
        return $this->facetFields;
    }

    /**
     * @param array $facetFields
     */
    public function setFacetFields($facetFields)
    {
        $this->facetFields = $facetFields;
    }

    /**
     * @return array
     */
    public function getFilterQuery()
    {
        return $this->filterQuery;
    }

    /**
     * @param array $filterQuery
     */
    public function setFilterQuery($filterQuery)
    {
        $this->filterQuery = $filterQuery;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return EventsSearch
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param EventsSearch $search
     */
    public function setSearch($search)
    {
        $this->search = $search;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

}
