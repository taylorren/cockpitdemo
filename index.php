<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\FormServiceProvider;
use Michelf\MarkdownExtra;
use Symfony\VarDumper;

require_once __DIR__ . '/vendor/autoload.php';

$dir = __DIR__ . '/../cockpit/bootstrap.php';
require_once $dir;

$app = new Silex\Application();
$app['debug'] = true;

// Register Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));

// Register UrlGenerator
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


$app->get('/', function () use ($app) {
    $collections = cockpit('collections:collections', []);
    $galleries = cockpit('galleries:galleries', []);

    return $app['twig']->render('index.html.twig', ['collections' => $collections, 'galleries' => $galleries]);
})->bind('home');

$app->get('/collection/{col}', function ($col) use ($app) {
    $entries = collection($col)->find()->toArray();

    foreach ($entries as &$entry) {
        $text = $entry['diary'];
        $html = MarkdownExtra::defaultTransform($text);

        $entry['diary'] = $html;
    }
    return $app['twig']->render('entries.html.twig', ['collection' => $col, 'entries' => $entries]);
}
)->bind('collection');

$app->get('/gallery/{gal}', function ($gal) use ($app) {

    $images = cockpit("galleries")->gallery($gal);
    
    foreach ($images as &$img) {
        $image = $img['path'];
        $imgurl = cockpit('mediamanager:thumbnail', $image, 200, 200);
        $img['cache']=$imgurl;
        
        $path=$img['path'];
        $url=str_replace('site:', 'http://'.$app['request']->getHost().'/', $path);
        $img['url']=$url;
        
    }

    return $app['twig']->render('gallery.html.twig', ['images'=>$images, 'gal'=>$gal]);
}
)->bind('gallery');

$app->run();

