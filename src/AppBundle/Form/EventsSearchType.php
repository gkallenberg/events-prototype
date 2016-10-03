<?php
namespace AppBundle\Form;

use AppBundle\Form\EventListener\EventsSearchListener;
use Symfony\Component\DependencyInjection\Tests\Compiler\H;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EventsSearchForm
 * @package AppBundle\Form
 */
class EventsSearchType extends AbstractType
{
    /**
     * @var \DateTimeImmutable
     */
    public $date;

    /**
     * @var \DateTimeImmutable
     */
    public $week;

    /**
     * @var \DateTimeImmutable
     */
    public $month;

    /**
     * @var array
     */
    public $categories;

    /**
     * @var array
     */
    public $locations;

    /**
     * @var array
     */
    public $audiences;

    /**
     * @var array
     */
    public $dateRanges;

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * @param mixed $week
     */
    public function setWeek($week)
    {
        $this->week = $week;
    }

    /**
     * @return mixed
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * @param mixed $month
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param mixed $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    /**
     * @return mixed
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @param mixed $locations
     */
    public function setLocations($locations)
    {
        $this->locations = $locations;
    }

    /**
     * @return mixed
     */
    public function getAudiences()
    {
        return $this->audiences;
    }

    /**
     * @param mixed $audiences
     */
    public function setAudiences($audiences)
    {
        $this->audiences = $audiences;
    }

    /**
     * @return mixed
     */
    public function getDateRanges()
    {
        return $this->dateRanges;
    }

    /**
     * @param mixed $dateRanges
     */
    public function setDateRanges($dateRanges)
    {
        $this->dateRanges = $dateRanges;
    }

    public function __construct()
    {
        $this->setDate(new \DateTimeImmutable('now', new \DateTimeZone('America/New_York')));
        $this->setWeek($this->date->add(new \DateInterval('P7D')));
        $this->setMonth($this->date->add(new \DateInterval('P1M')));

        $this->setCategories([
            'Show Everything' => 'all',
            'Author Talks & Conversations' => '8171',
            'Business & Finance' => '8176',
            'Career & Education' => '8177',
            'Children & Family' => '8174',
            'Computers & Workshops' => '8175',
            'Exhibitions & Tours' => '8172',
            'Performing Arts & Films' => '8173',
        ]);

        $this->setLocations([
            'Everywhere' => 'all',
            'The Bronx' => 'Bronx',
            'Manhattan' => 'Manhattan',
            'Staten Island' => 'Staten Island',
        ]);

        $this->setAudiences([
            'For Everyone' => 'all',
            'Adults' => 'Adult',
            'Children' => 'Children',
            'Teens/Young Adults' => 'Young Adult',
        ]);

        $this->setDateRanges([
            'At Anytime' => 'all',
            'Today' => '[NOW-1HOUR TO ' . $this->date->format('Y-m-d') .'T23:59:59Z]',
            'This Week' => '[' . $this->date->format('Y-m-d') .'T00:00:00Z TO '. $this->week->format('Y-m-d') .'T23:59:59Z]',
            'This Month' => '[' . $this->date->format('Y-m-d') .'T00:00:00Z TO '. $this->month->format('Y-m-d') .'T23:59:59Z]',
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nearby', CheckboxType::class, [
                'label' => 'Nearby',
                'label_attr' => ['class' => 'form-check-label'],
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Show me',
                'choices' => $this->getCategories(),
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'in',
                'choices' => $this->getLocations(),
            ])
            ->add('audience', ChoiceType::class, [
                'label' => 'for',
                'choices' => $this->getAudiences(),
            ])
            ->add('date', ChoiceType::class, [
                'label' => 'happening',
                'choices' => $this->getDateRanges(),
            ])
            ->add('start', HiddenType::class)
            ->add('rows', HiddenType::class)
            ->add('submit', SubmitType::class, ['label' => 'Search'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'])
        ;

        parent::buildForm($builder, $options); // TODO: Change the autogenerated stub
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $event->setData($event->getForm()->getData());
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\EventsSearch',
        ));
    }
}
