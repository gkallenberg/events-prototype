<?php
namespace AppBundle\Entity;

class EventsSearch
{
    /**
     * @var string
     */
    public $keyword = '';

    /**
     * @var bool
     */
    public $nearby = false;

    /**
     * @var string
     */
    public $category = '';

    /**
     * @var string
     */
    public $location = '';

    /**
     * @var string
     */
    public $audience = '';

    /**
     * @var string
     */
    public $date = '';

    /**
     * @var integer
     */
    public $start = 0;

    /**
     * @var integer
     */
    public $rows = 10;

    /**
     * @var string
     */
    public $position = '';

    /**
     * @var string
     */
    public $distance = '1.5';

    /**
     * @var string
     */
    public $sort = 'date_time_start';

    /**
     * @var string
     */
    public $sortOrder = 'asc';

    /**
     * @var string
     */
    public $responseOutput = 'json';

    /**
     * @var array
     */
    public $facetFields = [
        'Category' => 'category',
        'Location' => 'city',
    ];

    /**
     * @var array
     */
    public $icons = [
        'Author Talks & Conversations' => [
            'name' => 'microphone',
            'color' => '#202000',
        ],
        'Business & Finance' => [
            'name' => 'line-chart',
            'color' => '',
        ],
        'Classes & Workshops' => [
            'name' => 'desktop',
            'color' => '#806040',
        ],
        'Children & Family' => [
            'name' => 'child',
            'color' => '#80a0c0',
        ],
        'Performing Arts & Films' => [
            'name' => 'film',
            'color' => '#606020',
        ],
        'Career & Education' => [
            'name' => 'graduation-cap',
            'color' => '#806020',
        ],
        'Exhibitions & Tours' => [
            'name' => 'compass',
            'color' => '#802000',
        ],
        'default' => [
            'name' => 'tag',
            'color' => '#600000',
        ]
    ];

    public function __construct()
    {
        $this->setDate('[NOW-1HOUR TO *]');
    }

    /**
     * @param string $keyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
    }

    /**
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * @return boolean
     */
    public function isNearby()
    {
        return $this->nearby;
    }

    /**
     * @param boolean $nearby
     */
    public function setNearby($nearby)
    {
        $this->nearby = $nearby;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        if (strpos(' ', $location) !== false) {
            $location = '"' . $location . '"';
        }
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getAudience()
    {
        return $this->audience;
    }

    /**
     * @param string $audience
     */
    public function setAudience($audience)
    {
        if (strpos(' ', $audience) !== false) {
            $audience = '"' . $audience . '"';
        }
        $this->audience = $audience;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return mixed
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param mixed $rows
     */
    public function setRows($rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return string
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * @param string $distance
     */
    public function setDistance($distance)
    {
        $this->distance = $distance;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param string $sort
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param string $sortOrder
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return string
     */
    public function getResponseOutput()
    {
        return $this->responseOutput;
    }

    /**
     * @param string $responseOutput
     */
    public function setResponseOutput($responseOutput)
    {
        $this->responseOutput = $responseOutput;
    }

    public function prepareView($results)
    {
        return $results;
    }

}
