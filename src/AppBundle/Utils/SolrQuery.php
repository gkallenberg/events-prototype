<?php
namespace AppBundle\Utils;

use Solarium\QueryType\Select\Query\Query;

class SolrQuery
{
    /**
     * @var Query
     */
    protected $solrQuery;

    /**
     * @var Filter
     */
    protected $filters;

    public function __construct(Query $query, Filter $filter)
    {
        $this->setSolrQuery($query);
        $this->setFilters($filter);
    }

    /**
     * @return Query
     */
    public function getSolrQuery()
    {
        return $this->solrQuery;
    }

    /**
     * @param Query $solrQuery
     */
    public function setSolrQuery(Query $solrQuery)
    {
        $this->solrQuery = $solrQuery;
    }

    public function setFilters(Filter $filter)
    {
        $this->filters = $filter;
    }

    public function setBaseParams()
    {
        $this->setQuery($this->search->getKeyword());
        $this->setResponse($this->search->getResponseOutput());
        $this->setSort($this->search->getSort(), $this->search->getSortOrder());
        $this->setRows($this->search->getRows());
        $this->setStart($this->search->getStart());

        // Set facet fields
        $this->setFacetFields($this->search->getFacetFields());

        // Geo spatial filter
        $this->addFilter('{!geofilt sfield=geo}');
        $this->addParam('pt', $this->search->getPosition());
        $this->addParam('d', $this->search->getDistance());
    }

    public function buildParams()
    {
        $this->setBaseParams();

        if (!empty($baseFilters)) {
            foreach ($baseFilters as $facet => $filter) {
                if (!empty($filter)) {
                    $this->addFilter($facet . ':' . $filter);
                }
            }
        }
    }

}
