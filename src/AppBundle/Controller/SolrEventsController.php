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
     * @Route("/events")
     */
    public function searchAction()
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
                    'Computers & Workshops' => '4315',
                    'Drop In Programs' => '4327',
                    'Exhibitions' => 'exhib',
                    'Author Talks & Gatherings' => '4322',
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
            ])->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $events = $event->getData();
                $form = $event->getForm();
            })
            ->add('submit', SubmitType::class, ['label' => 'Search'])
        ->getForm();

        $request = Request::createFromGlobals();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventSearch = $form->getData();
        }

        $results = $this->listAction($eventSearch);

        return $this->render('events/index.html.twig', [
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
            'event_type_id' => ($eventSearch->category != 'all') ? $eventSearch->category : '',
            'city' => ($eventSearch->location != 'all') ? $eventSearch->location : '',
            'target_audience' => ($eventSearch->audience != 'all') ? $eventSearch->audience : '',
            'date_time_start' => ($eventSearch->date != 'all') ? $eventSearch->date : '[' . date('Y-m-d', time()) .'T00:00:00Z TO *]',
        ];

        $fq = [];
        foreach ($params as $facet => $param) {
            if (!empty($param)) {
                array_push($fq, $facet . ': ' . $param);
            }
        }

        $filterString = implode(' AND ', $fq);

        /**
         * @var GuzzleBundle
         */
        $client = $this->get('guzzle.client.solr');
        $query = [
            'q' => '*:*',
            'wt' => 'json',
            'fq' => $filterString,
            'facet' => 'true',
            'facet.field' => 'city',
            'sort' => 'date_time_start asc',
        ];

        $response = $client->get(self::LOCAL_SOLR . '/select', ['query' => $query]);

        $items = [];
        $list = [];

        if ($response->getStatusCode() == '200') {
            $list = json_decode($response->getBody(), true);
            $facets = $list['facet_counts']['facet_fields']['city'];
            for ($iter = 0 ; $iter < count($facets)-1 ; $iter+=2) {
                $items[] = [
                    'name' => $facets[$iter],
                    'count' => $facets[$iter+1],
                ];
            }
        }

        $docs = $this->processDocs($list['response']['docs']);

        return ['list' => $docs, 'items' => $items];
    }

    public function processDocs(&$docs)
    {
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
            } else {
                $docs[$key]['icon'] = 'glyphicon-calendar';
            }
        }

        return $docs;
    }
}
