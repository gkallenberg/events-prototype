<?php
namespace AppBundle\Controller;

use EightPoints\Bundle\GuzzleBundle\GuzzleBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
    const DATE_STRING = 'M d';
    const TIME_STRING = ' @ g a';
    const TIME_STRING_MIN = ' @ g:i a';

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        /**
         * @var EventsSearch
         */
        $eventSearch = new EventsSearch();

        $form = $this->createForm(EventsSearchType::class, $eventSearch);

        $request = Request::createFromGlobals();

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
     * @param EventsSearch $eventSearch
     * @return array
     */
    public function listAction(EventsSearch $eventSearch)
    {
        $params = [
            'category_id' => ($eventSearch->category != 'all') ? $eventSearch->category : '',
            'city' => ($eventSearch->location != 'all') ? $eventSearch->location : '',
            'target_audience' => ($eventSearch->audience != 'all') ? $eventSearch->audience : '',
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
        array_push($fq, '{!geofilt sfield=geo}');
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
                switch ($doc['category']) {
                    case 'Author Talks & Conversations':
                        $docs[$key]['icon'] = 'fa-microphone';
                        $docs[$key]['icon_color'] = 'black';
                        break;
                    case 'Classes & Workshops':
                        $docs[$key]['icon'] = 'fa-desktop';
                        $docs[$key]['icon_color'] = 'gray';
                        break;

                    case 'Children & Family':
                        $docs[$key]['icon'] = 'fa-child';
                        $docs[$key]['icon_color'] = 'blue';
                        break;

                    case 'Performing Arts & Films':
                        $docs[$key]['icon'] = 'fa-film';
                        $docs[$key]['icon_color'] = 'black';
                        break;

                    case 'Career & Education':
                        $docs[$key]['icon'] = 'fa-graducation-cap';
                        $docs[$key]['icon_color'] = 'purple';
                        break;

                    case 'Exhibitions & Tours':
                        $docs[$key]['icon'] = 'fa-compass';
                        $docs[$key]['icon_color'] = 'gray';
                        break;

                    default:
                        $docs[$key]['icon'] = 'fa-tag';
                        $docs[$key]['icon_color'] = 'yellow';
                        break;
                }
            }
        }

        return $docs;
    }
}
