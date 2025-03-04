<?php

declare(strict_types=1);

namespace M6Web\Bundle\ElasticsearchBundle\Tests\Units\Elasticsearch;

use Elasticsearch\Connections\Connection;

/**
 * Class ConnectionMocker
 */
trait ConnectionMocker
{
    /**
     * @param int $numberOfConnections
     *
     * @return Connection[]
     */
    protected function getConnectionMocks($numberOfConnections = 1)
    {
        return array_map([$this, 'getConnectionMock'], array_fill(0, $numberOfConnections, []));
    }

    /**
     * @return Connection
     */
    protected function getConnectionMock(array $callbacks = [])
    {
        $this->mockGenerator->orphanize('__construct');
        $this->mockGenerator->shuntParentClassCalls();

        $connectionMock = new \mock\Elasticsearch\Connections\Connection();

        foreach ($callbacks as $method => $callback) {
            $this->calling($connectionMock)->$method = $callback;
        }

        return $connectionMock;
    }
}
