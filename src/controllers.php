<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

$app->match('/', function() use ($app) {
    return $app['twig']->render('index.html.twig');
})->bind('homepage');

$app->match('/add', function() use ($app) {
    $builder = $app['form.factory']->createBuilder('form');

    $default_date = new Datetime();
    $form = $builder
        ->add('title', 'text', array(
            'constraints' => new Assert\NotBlank(),
            'attr'        => array('placeholder' => 'Title')
        ))
        ->add('date', 'datetime', array('data' => $default_date->format('Y-m-d H:i:s'), 'input' => 'string'))
        ->add('password', 'password', array(
          'attr' => array('placeholder' => 'Leave empty for public link')
        ))
        ->getForm();

    if ('POST' === $app['request']->getMethod()) {
        $form->bind($app['request']);

        if ($form->isValid()) {
            $title = $form->get('title')->getData();
            $date = $form->get('date')->getData();
            $pass = $form->get('password')->getData();

            if ($title && $date) {
                echo $title.'<br>';
                var_dump($date).'<br>';
                echo $pass.'<br>';
                if (new Datetime($date) < new Datetime()) {
                    $app['session']->getFlashBag()
                      ->add('error', 'You can\'t set a past date');
                }
                else {
                    $cd = array(
                        'title' => $title,
                        'date' => $date,
                        'password' => $pass,
                    );
                    $app['db']->insert('countdowns', $cd);
                }
            }
            else {
                $app['session']->getFlashBag()
                    ->add('error', 'Title and date are mandatory');
            }
        }
    }

    return $app['twig']->render('add.html.twig', array('form' => $form->createView()));
})->bind('cd-add');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message, $code);
});

return $app;
