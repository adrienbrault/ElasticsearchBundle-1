<?php

declare(strict_types=1);

namespace M6Web\Bundle\ElasticsearchBundle\DataCollector;

use M6Web\Bundle\ElasticsearchBundle\EventDispatcher\ElasticsearchEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * ElasticsearchDataCollector
 */
class ElasticsearchDataCollector extends DataCollector
{
    /**
     * ElasticsearchDataCollector constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    public function handleEvent(ElasticsearchEvent $event)
    {
        $query = [
            'method' => $event->getMethod(),
            'uri' => $event->getUri(),
            'headers' => $this->stringifyVariable($event->getHeaders()),
            'status_code' => $event->getStatusCode(),
            'duration' => $event->getDuration(),
            'took' => $event->getTook(),
            'body' => json_decode($event->getBody()),
            'error' => $event->getError(),
        ];
        $this->data['queries'][] = $query;
        $this->data['total_execution_time'] += $query['duration'];
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'elasticsearch';
    }

    /**
     * Get queries
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }

    /**
     * Get total execution time
     *
     * @return float
     */
    public function getTotalExecutionTime()
    {
        return $this->data['total_execution_time'];
    }

    /**
     * Resets this data collector to its initial state.
     */
    public function reset()
    {
        $this->data = [
            'queries' => [],
            'total_execution_time' => 0,
        ];
    }

    /**
     * Converts a PHP variable to a string or
     * Converts the variable into a serializable Data instance.
     *
     * The convert action depend on method available in the sf DataCollector class.
     * In sf >= 4, the DataCollector::varToString() doesn't exists anymore
     *
     * @param mixed $var
     *
     * @return mixed
     */
    protected function stringifyVariable($var)
    {
        if (method_exists($this, 'varToString')) {
            return $this->varToString($var);
        } else {
            return $this->cloneVar($var);
        }
    }
}
