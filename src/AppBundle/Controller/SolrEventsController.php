<?php
namespace AppBundle\Controller;

use EightPoints\Bundle\GuzzleBundle\GuzzleBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\EventsSearch;
use AppBundle\Form\EventsSearchType;

/**
 * Class SolrEventsController
 * @package AppBundle\Controller
 */
class SolrEventsController extends Controller
{

    const LOCAL_SOLR = '/solr/events';
    const REMOTE_SOLR = '/solr/solrevents';
    const REFINERY_API = '/api/nypl/ndo/v0.1';
    const DATE_STRING = 'M d';
    const TIME_STRING = ' @ g a';
    const TIME_STRING_MIN = ' @ g:i a';

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * Matches /*
     * @Route("/{page}", name="homepage", requirements={"page": "\d+"})
     */
    public function indexAction($page = 0)
    {
        /**
         * @var EventsSearch
         */
        $eventSearch = new EventsSearch();
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

        $results = $this->listAction($eventSearch);

//        $features = $this->getFeatures();

        return $this->render('index.html.twig', [
            'title' => 'What\'s Happening @ NYPL',
            'page_title' => 'What\'s Happening',
            'features' => (isset($features)) ? $features : null,
            'form' => $form->createView(),
            'list' => (!empty($results['list'])) ? $results['list'] : null,
            'facets' => (!empty($results['items'])) ? $results['items'] : null,
            'pages' => $results['pages'],
        ]);
    }

    /**
     * @param EventsSearch $eventSearch
     * @return array
     */
    public function listAction(EventsSearch $eventSearch)
    {
        $location = $eventSearch->location;
        $audience = $eventSearch->audience;

        if ($location == 'Staten Island') {
            $location = '"' . $location . '"';
        }

        if ($audience == 'Young Adult') {
            $audience = '"' . $audience . '"';
        }

        $params = [
            'category_id' => ($eventSearch->category != 'all') ? $eventSearch->category : '',
            'city' => ($location != 'all') ? $location : '',
            'target_audience' => ($audience != 'all') ? $audience : '',
            'date_time_start' => ($eventSearch->date != 'all') ? $eventSearch->date : '[' . date('Y-m-d', time()) .'T00:00:00Z TO *]',
            'pub_status' => 1,
        ];
        $facetFields = [
            'category',
            'city',
        ];

        $fq = [];
        foreach ($params as $facet => $param) {
            if (!empty($param)) {
                array_push($fq, $facet . ':' . $param);
            }
        }

        // Geo spatial filter
        if ($eventSearch->nearby) {
            array_push($fq, '{!geofilt sfield=geo}');
        }
        $myPos = '40.7532,-73.9822';
//        $myPos = '40.7347,-73.999';
        $distance = $eventSearch->distance;
        $start = $eventSearch->start;
        $rows = $eventSearch->rows;

        $query = [
            'params' => [
                'q' => '*:*',
                'facet' => 'true',
                'facet.field' => $facetFields,
                'sort' => 'date_time_start asc',
                'rows' => $rows,
                'start' => $start,
                'wt' => 'json',
                'pt' => $myPos,
                'd' => $distance,
            ]
        ];

        if (!empty($fq)) {
            $query['filter'] = $fq;
        }

        $response = $this->retrieveDocs($query);

        $items = [];
        $docs = [];
        $pages = 0;
        $icons = [
            'Author Talks & Conversations' => 'microphone',
            'Business & Finance' => 'line-chart',
            'Classes & Workshops' => 'desktop',
            'Children & Family' => 'child',
            'Performing Arts & Films' => 'film',
            'Career & Education' => 'graduation-cap',
            'Exhibitions & Tours' => 'compass',
        ];

        if ($response->getStatusCode() == '200') {
            $list = json_decode($response->getBody(), true);
            $total = $list['response']['numFound'];
            $pages = [
                'page' => ($start > 0) ? $start / $rows : $start,
                'total' => floor($total / $rows)
            ];
            if ($total > 0) {
                foreach ($facetFields as $facetField) {
                    $facets = $list['facet_counts']['facet_fields'][$facetField];
                    $facetField = ($facetField == 'city') ? 'location' : $facetField;
                    for ($iter = 0; $iter < count($facets) - 1; $iter += 2) {
                        $items[$facetField][] = [
                            'name' => $facets[$iter],
                            'icon' => (array_key_exists($facets[$iter],
                                $icons)) ? $icons[$facets[$iter]] : 'location-arrow',
                            'count' => $facets[$iter + 1],
                        ];
                    }
                }
            }
            $docs = $this->processDocs($list['response']['docs']);
        }

        return ['list' => $docs, 'items' => $items, 'pages' => $pages];
    }

    /**
     * @param array $query
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function retrieveDocs(array $query)
    {
        /**
         * @var GuzzleBundle
         */
        $client = $this->get('guzzle.client.solr');

        $response = $client->post(self::LOCAL_SOLR . '/query', [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'json' => $query
        ]);

        return $response;
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
                if (isset($doc['category'])) {
                    switch ($doc['category']) {
                        case 'Author Talks & Conversations':
                            $docs[$key]['icon'] = 'fa-microphone';
                            $docs[$key]['icon_color'] = '#202000';
                            break;
                        case 'Classes & Workshops':
                            $docs[$key]['icon'] = 'fa-desktop';
                            $docs[$key]['icon_color'] = '#806040';
                            break;

                        case 'Children & Family':
                            $docs[$key]['icon'] = 'fa-child';
                            $docs[$key]['icon_color'] = '#80a0c0';
                            break;

                        case 'Performing Arts & Films':
                            $docs[$key]['icon'] = 'fa-film';
                            $docs[$key]['icon_color'] = '#606020';
                            break;

                        case 'Career & Education':
                            $docs[$key]['icon'] = 'fa-graduation-cap';
                            $docs[$key]['icon_color'] = '#806020';
                            break;

                        case 'Exhibitions & Tours':
                            $docs[$key]['icon'] = 'fa-compass';
                            $docs[$key]['icon_color'] = '#802000';
                            break;

                        default:
                            $docs[$key]['icon'] = 'fa-tag';
                            $docs[$key]['icon_color'] = '#600000';
                            break;
                    }
                } else {
                    $docs[$key]['icon'] = 'fa-tag';
                    $docs[$key]['icon_color'] = '#600000';
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
        static $features = [];

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
     * Matches /event/*
     * @Route("/event/{ident}", name="event_display")
     *
     * @return array
     */
    public function eventAction($ident)
    {
        $query = [
            'params' => [
                'q' => '*:*',
                'event_id' => $ident,
                'wt' => 'json',
            ]
        ];
        $response = $this->retrieveDocs($query);

        $data = json_decode($response->getBody(), true);
        $doc = $data['response']['docs'];

        return $this->render('events/event.html.twig', [
            'title' => 'Event',
            'page_title' => 'Event',
            'event' => $doc,
        ]);
    }
}
