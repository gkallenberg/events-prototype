<?php
namespace AppBundle\Controller;

use AppBundle\Utils\Filter;
use AppBundle\Utils\SolrQuery;
use EightPoints\Bundle\GuzzleBundle\GuzzleBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Solarium\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\EventsSearch;
use AppBundle\Form\EventsSearchType;

/**
 * Class EventsController
 * @package AppBundle\Controller
 */
class EventsController extends Controller
{

    const LOCAL_SOLR = '/solr/events';
    const REMOTE_SOLR = '/solr/solrevents';
    const REFINERY_API = '/api/nypl/ndo/v0.1';
    const DATE_STRING = 'M d';
    const TIME_STRING = ' @ g a';
    const TIME_STRING_MIN = ' @ g:i a';

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

    /**
     * @var string
     */
    public $headTitle = 'What\'s Happening @ NYPL';

    /**
     * @var string
     */
    public $pageTitle = 'What\'s Happening';

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * Matches /events/*
     * @Route("/events/{page}", name="homepage", requirements={"page": "\d+"})
     */
    public function indexAction($page = 0)
    {
        /**
         * @var EventsSearch
         */
        $eventSearch = new EventsSearch();
        $eventSearch->setPosition('40.7532,-73.9822'); // SASB
//        $eventSearch->setPosition('40.7347,-73.999'); // JMR

        if (isset($page)) {
            $eventSearch->setStart($page * $eventSearch->rows);
        }

        $form = $this->createForm(EventsSearchType::class, $eventSearch);

        $request = Request::createFromGlobals();

        if ($request->isMethod('POST')) {
            $form->submit($request->request->get($form->getName()));

            if ($form->isSubmitted() && $form->isValid()) {
                $eventSearch = $form->getData();
            }

        }

        $features = $this->getFeatures();

        $results = $this->readDocs($eventSearch);

        $pageElements = [
            'title' => $this->headTitle,
            'page_title' => $this->pageTitle,
            'features' => $features,
            'form' => $form->createView(),
        ];

        if (isset($results)) {
            $pageElements = array_merge($pageElements, $results);
        }

        return $this->render('page/events/index.html.twig', $pageElements);
    }

    /**
     * @param EventsSearch $eventSearch
     * @return array
     */
    public function processResults(EventsSearch $eventSearch)
    {
        $results = [
            'docs' => null,
            'facets' => null,
            'pages' => null,
        ];

        $response = $this->readDocs($filter);

        if ($response->getStatusCode() == '200') {
            $list = json_decode($response->getBody(), true);
            $total = $list['response']['numFound'];
            $results['pages'] = [
                'page' => ($start > 0) ? $start / $rows : $start,
                'total' => floor($total / $rows)
            ];
            if ($total > 0) {
                $facets = $list['facet_counts']['facet_fields'];
                $results['facets'] = $this->processFacets($facets);
            }
            $results['docs'] = $this->processDocs($list['response']['docs']);
        }

        return $results;
    }

    /**
     * @param EventsSearch $eventsSearch
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function readDocs(EventsSearch $eventsSearch)
    {
        $filter = new Filter($eventsSearch);

        /**
         * var SolariumClient
         */
        $client = $this->get('solarium.client');

        $selectQuery = new SolrQuery($client->createSelect(), $filter);

        $query = $client->createSelect();

        $query->setStart($eventsSearch->getStart())->setRows($eventsSearch->getRows());
        $query->setResponseWriter($eventsSearch->getResponseOutput());
        $query->setQuery($eventsSearch->getKeyword());
        $query->setSorts([$eventsSearch->getSort(), $eventsSearch->getSortOrder()]);

        var_dump($query);

        /**
         * @var GuzzleBundle
         */
//        $guzzleClient = $this->get('guzzle.client.solr');
//
//        $response = $guzzleClient->post(self::LOCAL_SOLR . '/query', [
//            'headers' => [ 'Content-Type' => 'application/json' ],
//            'json' => $query
//        ]);

        return $response;
    }

    /**
     * @param $facets
     * @return null
     */
    public function processFacets($facets)
    {
        $displayFacets = null;

        if (!empty($facets)) {
            foreach ($facets as $facetKey => $facetField) {
                $facetKey = ($facetKey == 'city') ? 'location' : $facetKey;
                for ($iter = 0; $iter < count($facetField) - 1; $iter += 2) {
                    $displayFacets[$facetKey][] = [
                        'name' => $facetField[$iter],
                        'icon' => (array_key_exists($facetField[$iter],
                            $this->icons)) ? $this->icons[$facetField[$iter]]['name'] : 'location-arrow',
                        'count' => $facetField[$iter + 1],
                    ];
                }
            }
        }

        return $displayFacets;
    }

    /**
     * @param $docs
     * @return array
     */
    public function processDocs(&$docs)
    {
        if (is_array($docs)) {
            foreach ($docs as $key => $doc) {
                $today = new \DateTime('now', new \DateTimeZone('America/New_York'));
                $dateTime = new \DateTime($doc['date_time_start']);
                $dateTime->setTimezone(new \DateTimeZone('America/New_York'));
                $timeString = ($dateTime->format('i') != '00') ? $dateTime->format(self::TIME_STRING_MIN) : $dateTime->format(self::TIME_STRING);
                $dateString = ($today->format('Y-m-d') == $dateTime->format('Y-m-d')) ? 'Today'. $timeString : $dateTime->format(self::DATE_STRING) . $timeString;
                $docs[$key]['date_time_start'] = $dateString;
                if (isset($doc['category']) && array_key_exists($doc['category'], $this->icons)) {
                    $docs[$key]['icon'] = $this->icons[$doc['category']]['name'];
                    $docs[$key]['iconColor'] = $this->icons[$doc['category']]['color'];
                } else {
                    $docs[$key]['icon'] = $this->icons['default']['name'];
                    $docs[$key]['iconColor'] = $this->icons['default']['color'];
                }
            }
        }

        return $docs;
    }

    /**
     * @return array|string
     */
    public function getFeatures()
    {
        /**
         * var array
         */
        $features = null;

        try {
            /**
             * var GuzzleClient
             */
            $client = $this->get('guzzle.client.refinery');

            $response = $client->get(self::REFINERY_API . '/site-data/containers', [
                'query' => [
                    'filter[slug]' => 'whats-happening',
                    'include' => 'children.slots.current-item,slots.current-item',
                ]
            ]);

            if ($response->getStatusCode() == '200') {
                $containers = json_decode($response->getBody());

                foreach ($containers->included as $container) {
                    if ($container->type == 'scheduled-featured-item') {
                        $features[] = $container;
                    }
                }
//                shuffle($featuredItems);
//                $features = array_slice($features, 0, 3);
                $features = [
                    $features[0], $features[4], $features[8]
                ];

                foreach ($features as $feature) {
                    $id = $feature->id;
                    $image = $client->get(self::REFINERY_API . '/site-data/scheduled-featured-items/'. $id .'/relationships/rectangular-image');
                    $imageUrl = json_decode($image->getBody());
                    $imageFullUrl = $imageUrl->data->attributes->uri->{'full-uri'};
                    $feature->attributes->{'image-uri'} = $imageFullUrl;
                }
            }
        } catch (Exception $exception) {
            return $exception->getMessage();
        }

        return $features;
    }

    /**
     * Matches /events/doc/*
     * @Route("/events/doc/{ident}", name="event_display")
     *
     * @return array
     */
    public function eventAction($ident)
    {
        $params->addParam('event_id', $ident);

        $response = $this->readDocs($params->getJsonQuery());

        $data = json_decode($response->getBody(), true);
        $doc = $data['response']['docs'];

        return $this->render('events/event.html.twig', [
            'title' => 'Event',
            'page_title' => 'Event',
            'event' => $doc,
        ]);
    }

    public function requestContainer()
    {
        // TODO: Get parent container, parse
        $parentEndpoint = [
            'url' => self::REFINERY_API . '/site-data/containers',
            'query' => ['filter[slug]' => 'whats-happening']
        ];

        $parentEndpoint = json_decode($parentEndpoint);

        // TODO: Get id and data:relationships:children:links:self, parse

        foreach ($parentEndpoint->data as $parentData) {
            $parentData->relationships->children->links->self;
            $childSlots = '';
        }

        // TODO: Get id and data:relationships:slots:links:self, parse

        foreach ($childSlots as $childSlot) {
            $childSlot->data->relationships->slots->links->self;
            $slots[] = '';
        }

        // TODO: Get first slot's id and data:relationships:current-item:links:self

        foreach ($slots as $slot) {
            $slot->data->relationships->{'current-item'}->links->self;
            $items[] = '';
        }

        // TODO: Get current-item's id and data:relationships:rectangular-image

        $items->data->relationships->{'rectangular-image'}->links->self;
        $images[] = '';
    }
}
