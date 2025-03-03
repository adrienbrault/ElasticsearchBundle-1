<?php

declare(strict_types=1);

namespace M6Web\Bundle\ElasticsearchBundle\Tests\Units\DependencyInjection;

use atoum\atoum\test;
use M6Web\Bundle\ElasticsearchBundle\DependencyInjection\M6WebElasticsearchExtension as TestedClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests of the M6WebElasticsearchExtension class
 */
class M6WebElasticsearchExtension extends test
{
    /**
     * The loading of a configuration with no `hosts` entry should fail
     */
    public function testNoHostsError()
    {
        $configs = [
            ['clients' => [
                'no_hosts_client' => [
                ],
            ]],
        ];

        $parameterBag = new ParameterBag(['kernel.debug' => true]);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);

        $this->if($extension = new TestedClass())
            ->exception(function () use ($extension, $configs, $container) {
                $extension->load($configs, $container);
            })
            ->isInstanceOf('\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
    }

    /**
     * The loading of a configuration with an empty `hosts` entry should fail
     */
    public function testEmptyHostsError()
    {
        $configs = [
            ['clients' => [
                'empty_hosts_client' => [
                    'hosts' => [],
                ],
            ]],
        ];

        $parameterBag = new ParameterBag(['kernel.debug' => true]);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);

        $this->if($extension = new TestedClass())
            ->exception(function () use ($extension, $configs, $container) {
                $extension->load($configs, $container);
            })
            ->isInstanceOf('\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
    }

    /**
     * Test the loading of a single Elasticsearch Client
     */
    public function testLoadsElasticsearchClient()
    {
        $configs = [
            ['clients' => [
                'my_only_client' => [
                    'hosts' => [
                        'localhost:9200',
                        'localhost:9201',
                    ],
                ],
            ]],
        ];

        $parameterBag = new ParameterBag(['kernel.debug' => true]);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $container->set('event_dispatcher', new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface());

        $this->if($extension = new TestedClass())
        ->when($extension->load($configs, $container))
        ->clientIsDefinedInContainer($container, 'my_only_client', 2)
        ->clientIsCorrectlyInstanciated($container, 'my_only_client');
    }

    /**
     * Test the loading of multiple Elasticsearch Clients
     */
    public function testLoadMultipleElasticsearchClients()
    {
        $configs = [
            ['clients' => [
                'my_first_client' => [
                    'hosts' => [
                        'localhost:9200',
                        'localhost:9201',
                    ],
                ],
                'my_second_client' => [
                    'hosts' => [
                        'myserver:9200',
                    ],
                ],
            ]],
        ];

        $parameterBag = new ParameterBag(['kernel.debug' => true]);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $container->set('event_dispatcher', new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface());

        $this->if($extension = new TestedClass())
        ->when($extension->load($configs, $container))
        ->clientIsDefinedInContainer($container, 'my_first_client', 2)
        ->clientIsCorrectlyInstanciated($container, 'my_first_client')
        ->clientIsDefinedInContainer($container, 'my_second_client', 1)
        ->clientIsCorrectlyInstanciated($container, 'my_second_client');
    }

    /**
     * Test the definition of a default client
     */
    public function testDefineDefaultElasticsearchClients()
    {
        $configs = [[
            'clients' => [
                'my_first_client' => [
                    'hosts' => [
                        'localhost:9200',
                    ],
                ],
                'my_second_client' => [
                    'hosts' => [
                        'myserver:9200',
                    ],
                ],
            ],
            'default_client' => 'my_second_client',
        ]];

        $parameterBag = new ParameterBag(['kernel.debug' => true]);

        $container = new \Symfony\Component\DependencyInjection\ContainerBuilder($parameterBag);
        $container->set('event_dispatcher', new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface());

        $this->if($extension = new TestedClass())
        ->when($extension->load($configs, $container))
        ->boolean($container->has('m6web_elasticsearch.client.default'))
            ->isTrue()
        ->and()
        ->clientIsCorrectlyInstanciated($container, 'default');
    }

    /**
     * Check if the client is correctly defined in the container
     *
     * @param string $clientName
     * @param int    $hostsSize
     *
     * @return M6WebElasticsearchExtension $this
     */
    protected function clientIsDefinedInContainer(ContainerInterface $container, $clientName, $hostsSize)
    {
        $this
            ->boolean($container->has('m6web_elasticsearch.client.'.$clientName))
                ->isTrue()
            ->and($arguments = $container->getDefinition('m6web_elasticsearch.client.'.$clientName)->getArguments())
                ->array($arguments[0])
                    ->hasKey('hosts')
                ->array($arguments[0]['hosts'])
                    ->hasSize($hostsSize);

        return $this;
    }

    /**
     * Check if the client is correctly instanciated
     *
     * @param string $clientName
     *
     * @return M6WebElasticsearchExtension $this
     */
    protected function clientIsCorrectlyInstanciated(ContainerInterface $container, $clientName)
    {
        $this
            ->object($container->get('m6web_elasticsearch.client.'.$clientName))
            ->isInstanceOf('\Elasticsearch\Client');

        return $this;
    }
}
