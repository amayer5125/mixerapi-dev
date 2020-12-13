<?php

namespace MixerApi\CollectionView\Test\TestCase\View;

use Cake\Controller\Component\PaginatorComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use MixerApi\CollectionView\Configuration;

class XmlCollectionViewTest extends TestCase
{
    /**
     * @var string[]
     */
    public $fixtures = [
        'plugin.MixerApi/CollectionView.Actors',
        'plugin.MixerApi/CollectionView.FilmActors',
        'plugin.MixerApi/CollectionView.Films',
    ];

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        Router::reload();
        Router::connect('/', ['controller' => 'Actors', 'action' => 'index']);
        Router::connect('/:controller/:action/*');
        Router::connect('/:plugin/:controller/:action/*');
        (new Configuration())->default();
    }

    public function testCollection()
    {
        $request = new ServerRequest([
            'url' => 'actors',
            'params' => [
                'plugin' => null,
                'controller' => 'Actors',
                'action' => 'index',
            ]
        ]);
        $request = $request->withEnv('HTTP_ACCEPT', 'application/xml, text/plain, */*');
        Router::setRequest($request);

        $controller = new Controller($request, new Response(), 'Actors');
        $controller->modelClass = 'Actors';
        $registry = new ComponentRegistry($controller);

        $paginator = new PaginatorComponent($registry);

        $actorTable = FactoryLocator::get('Table')->get('Actors');
        $actors = $paginator->paginate($actorTable, [
            'contain' => ['Films'],
            'limit' => 2
        ]);

        $controller->set([
            'actors' => $actors,
        ]);

        $controller->viewBuilder()
            ->setClassName('MixerApi/CollectionView.XmlCollection')
            ->setOptions(['serialize' => 'actors']);
        $View = $controller->createView();
        $output = $View->render();

        $this->assertIsString($output);

        $simpleXml = simplexml_load_string($output);
        $this->assertInstanceOf(\SimpleXMLElement::class, $simpleXml);

        $this->assertEquals(2, (int)$simpleXml->collection->count);
        $this->assertEquals(20, (int)$simpleXml->collection->total);
        $this->assertEquals('/actors', $simpleXml->collection->url);
        $this->assertEquals('/?page=2', $simpleXml->collection->next);
        $this->assertEquals('/?page=10', $simpleXml->collection->last);

        $actor = $simpleXml->data[0];
        $this->assertInstanceOf(\SimpleXMLElement::class, $actor->films);
    }
}