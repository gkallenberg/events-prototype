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
    public $rows = 20;

    /**
     * @var string
     */
    public $distance = '.5';

    public function __construct()
    {
        $this->setDate('[' . date('Y-m-d', time()) .'T00:00:00Z TO *]');
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

}
