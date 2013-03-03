<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

$app->match('/', function() use ($app) {
    return $app['twig']->render('index.html.twig');
})->bind('homepage');

$app->match('/countdown/{id}', function($id) use ($app) {
    if (!is_numeric($id)) {
        $app->redirect($app['url_generator']->generate('homepage'));
    }

    // Get the value in db.
    $sql = 'SELECT id,title,date,password FROM countdowns where id = ?';
    $countdown = $app['db']->fetchAssoc($sql, array((int) $id));
    if (!is_array($countdown)) {
        return $app->redirect($app['url_generator']->generate('homepage'));
    }

    $form = null;
    $auth = true;
    // If this page is protected.
    if ($countdown['password']) {
        $auth = false;
        $builder = $app['form.factory']->createBuilder('form');
        $form = $builder
            ->add('password', 'password')
            ->getForm();

        if ('POST' === $app['request']->getMethod()) {
            $form->bind($app['request']);

            if ($form->isValid()) {
                $sent_pass = $form->get('password')->getData();
                if (sha1($sent_pass) == $countdown['password']) {
                    $auth = true;
                    $app['session']->getFlashBag()->add(
                        'success', 'Right password :)'
                    );
                }
                else {
                    $app['session']->getFlashBag()->add(
                        'error', 'Wrong password'
                    );
                }
            }
        }
    }

    $date = new Datetime($countdown['date']);

    return $app['twig']->render('countdown.html.twig', array(
        'title' => $countdown['title'],
        'date'  => $date->format('d F Y H:i:s'),
        'auth'  => $auth,
        'form'  => $form ? $form->createView() : null
    ));
})->bind('cd-see');

$app->match('/add', function() use ($app) {
    $builder = $app['form.factory']->createBuilder('form');

    $default_date = new Datetime();
    $form = $builder
        ->add('title', 'text', array(
            'constraints' => new Assert\NotBlank(),
            'attr'        => array('placeholder' => 'Title')
        ))
        ->add('date', 'datetime', array(
          'data' => $default_date->format('Y-m-d H:i:s'),
          'input' => 'string'
        ))
        ->add('password', 'password', array(
          'attr' => array('placeholder' => 'Leave empty for public link')
        ))
        ->add('antispam', 'text', array(
          'constraints' => new Assert\NotBlank(),
          'attr'        => array('placeholder' => 'What`s the app name ?')
        ))
        ->getForm();

    if ('POST' === $app['request']->getMethod()) {
        $form->bind($app['request']);

        if ($form->isValid()) {
            $spam = $form->get('antispam')->getData();
            if ($spam == "wenizit") {
                $title = $form->get('title')->getData();
                $date = $form->get('date')->getData();
                $pass = $form->get('password')->getData();

                if ($title && $date) {
                    if (new Datetime($date) < new Datetime()) {
                        $app['session']->getFlashBag()
                          ->add('error', 'You can\'t set a past date');
                    }
                    else {
                        $cd = array(
                            'title' => $title,
                            'date' => $date,
                        );
                        if ($pass) {
                            $cd['password'] = sha1($pass);
                        }

                        $app['db']->insert('countdowns', $cd);
                        $id = $app['db']->lastInsertId();
                        $app['session']->getFlashBag()
                            ->add('success', 'Your countdown ' . $title . ' has been saved');
                        return $app->redirect($app['url_generator']->generate(
                            'cd-see', array('id' => $id)
                        ));
                    }
                }
                else {
                    $app['session']->getFlashBag()
                        ->add('error', 'Title and date are mandatory');
                }
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
