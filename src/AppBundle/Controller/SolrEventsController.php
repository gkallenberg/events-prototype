<?php
namespace AppBundle\Controller;

use AppBundle\Form\EventsSearchType;
use EightPoints\Bundle\GuzzleBundle\GuzzleBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SolrEventsController
 * @package AppBundle\Controller
 */
class SolrEventsController extends Controller
{

    const LOCAL_SOLR = '/solr/events';
    const REMOTE_SOLR = '/solr/solrevents';
    const DATE_STRING = 'M d';
    const TIME_STRING = ' @ g a';
    const TIME_STRING_MIN = ' @ g:i a';

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $eventSearch = new EventsSearchType();
        $date = new \DateTimeImmutable('now', new \DateTimeZone('America/New_York'));
        $week = $date->add(new \DateInterval('P7D'));
        $month = $date->add(new \DateInterval('P1M'));

        $form = $this->createFormBuilder($eventSearch)
            ->add('category', ChoiceType::class, [
                'label' => 'Show me',
                'choices' => [
                    'Everything' => 'all',
                    'Author Talks & Gatherings' => '8171',
                    'Career & Finance' => '8177',
                    'Children & Family' => '8174',
                    'Computers & Workshops' => '8175',
                    'Exhibitions' => '8172',
                    'Health & Fitness' => '8176',
                    'Performing Arts & Films' => '8173',
                    'Tours' => '8178',
                ]
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'in',
                'choices' => [
                    'Everywhere' => 'all',
                    'The Bronx' => 'Bronx',
                    'Manhattan' => 'Manhattan',
                    'Staten Island' => 'Staten+Island',
                ]
            ])
            ->add('audience', ChoiceType::class, [
                'label' => 'for',
                'choices' => [
                    'Everyone' => 'all',
                    'Adults' => 'Adult',
                    'Teens/Young Adults' => 'Young Adult',
                    'Kids & Families' => 'Children',
                ]
            ])
            ->add('date', ChoiceType::class, [
                'label' => 'happening',
                'choices' => [
                    'Anytime' => 'all',
                    'Today' => '[' . $date->format('Y-m-d') .'T00:00:00Z TO ' . $date->format('Y-m-d') .'T23:59:59Z]',
                    'This Week' => '[' . $date->format('Y-m-d') .'T00:00:00Z TO '. $week->format('Y-m-d') .'T23:59:59Z]',
                    'This Month' => '[' . $date->format('Y-m-d') .'T00:00:00Z TO '. $month->format('Y-m-d') .'T23:59:59Z]',
                ],
            ])
//            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
//                $events = $event->getData();
//                $form = $event->getForm();
//            })
            ->add('submit', SubmitType::class, ['label' => 'Search'])
        ->getForm();

        $request = Request::createFromGlobals();
//        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $form->submit($request->request->get($form->getName()));

            if ($form->isSubmitted() && $form->isValid()) {
                $eventSearch = $form->getData();
            }
        }

        $results = $this->listAction($eventSearch);

        return $this->render('index.html.twig', [
            'title' => 'What\'s Happening? @ NYPL',
            'header' => 'What\'s Happening?',
            'form' => $form->createView(),
            'list' => $results['list'],
            'facets' => $results['items']
        ]);
    }

    /**
     * @param EventsSearchType $eventSearch
     * @return array
     */
    public function listAction(EventsSearchType $eventSearch)
    {
        $params = [
            'category_id' => ($eventSearch->category != 'all') ? $eventSearch->category : '',
            'city' => ($eventSearch->location != 'all') ? $eventSearch->location : '',
            'target_audience' => ($eventSearch->audience != 'all') ? $eventSearch->audience : '',
             'date_time_start' => ($eventSearch->date != 'all') ? $eventSearch->date : '[' . date('Y-m-d', time()) .'T00:00:00Z TO *]',
//            'date_range_start' => '[2016-09-22]',
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
//        array_push($fq, '{!geofilt sfield=geo}');
        $myPos = '40.7528919,-73.9815126';
        $distance = '.5';

        /**
         * @var GuzzleBundle
         */
        $client = $this->get('guzzle.client.solr');
        $query = [
            'params' => [
                'q' => '*:*',
                'facet' => 'true',
                'facet.field' => $facetFields,
                'sort' => 'date_time_start asc',
                'rows' => 10,
                'start' => 0,
                'wt' => 'json',
                'pt' => $myPos,
                'd' => $distance,
            ]
        ];

        if (!empty($fq)) {
            $query['filter'] = $fq;
        }

//        var_dump(json_encode($query));

        $response = $client->post(self::LOCAL_SOLR . '/query', [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'json' => $query
        ]);

        $items = [];
        $list = [];

        if ($response->getStatusCode() == '200') {
            $list = json_decode($response->getBody(), true);
            foreach ($facetFields as $facetField) {
                $facets = $list['facet_counts']['facet_fields'][$facetField];
                $facetField = ($facetField == 'city') ? 'location' : $facetField;
                for ($iter = 0 ; $iter < count($facets)-1 ; $iter+=2) {
                    $items[$facetField][] = [
                        'name' => $facets[$iter],
                        'count' => $facets[$iter+1],
                    ];
                }
            }
        }

        $docs = $this->processDocs($list['response']['docs']);

        return ['list' => $docs, 'items' => $items];
    }

    public function processDocs(&$docs)
    {
        if (is_array($docs)) {
            foreach ($docs as $key => $doc) {
                $today = new \DateTime('now', new \DateTimeZone('America/New_York'));
                $dateTime = new \DateTime($doc['date_time_start'], new \DateTimeZone('America/New_York'));
                $timeString = ($dateTime->format('i') != '00') ? $dateTime->format(self::TIME_STRING_MIN) : $dateTime->format(self::TIME_STRING);
                $dateString = ($today->format('Y-m-d') == $dateTime->format('Y-m-d')) ? 'Today'. $timeString : $dateTime->format(self::DATE_STRING) . $timeString;
                $docs[$key]['date_time_start'] = $dateString;
                if ($doc['event_type']  == 'Classes/Workshops') {
                    $docs[$key]['icon'] = 'glyphicon-education';
                } elseif ($doc['event_type']  == 'Story Times/Read Alouds') {
                    $docs[$key]['icon'] = 'glyphicon-book';
                } elseif ($doc['event_type']  == 'Film/Video Screenings') {
                    $docs[$key]['icon'] = 'glyphicon-film';
                } elseif ($doc['event_type']  == 'Special Events') {
                    $docs[$key]['icon'] = 'glyphicon-sunglasses';
                } else {
                    $docs[$key]['icon'] = 'glyphicon-tag';
                }
            }
        }

        return $docs;
    }
}
