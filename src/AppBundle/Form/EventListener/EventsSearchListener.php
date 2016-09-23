<?php
namespace AppBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EventsSearchListener extends EventSubscriberInterface
{
    public static function getSubscriberEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(FormEvent $event)
    {
        $search = $event->getData();
        $form = $event->getForm();
    }

}
