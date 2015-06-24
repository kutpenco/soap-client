<?php

namespace Phpro\SoapClient;

use Phpro\SoapClient\Event;
use Phpro\SoapClient\Soap\SoapClient;
use SoapFault;
use SoapHeader;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Client
 *
 * @package Phpro\SoapClient
 */
class Client implements ClientInterface
{
    /**
     * @var SoapClient
     */
    private $soapClient;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @param SoapClient      $soapClient
     * @param EventDispatcher $dispatcher
     */
    public function __construct(SoapClient $soapClient, EventDispatcher $dispatcher)
    {
        $this->soapClient = $soapClient;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param SoapHeader $soapHeader
     *
     * @return $this
     */
    public function addSoapHeader(SoapHeader $soapHeader)
    {
        $this->soapClient->__setSoapHeaders($soapHeader);

        return $this;
    }

    /**
     * Make it possible to debug the last request.
     *
     * @return array
     */
    public function debugLastSoapRequest()
    {
        return [
            'request' => [
                'headers' => $this->soapClient->__getLastRequestHeaders(),
                'body' => $this->soapClient->__getLastRequest(),
            ],
            'response' => [
               'headers' => $this->soapClient->__getLastResponseHeaders(),
                'body' => $this->soapClient->__getLastResponse(),
            ],
        ];
    }

    /**
     * @param string $location
     */
    public function changeSoapLocation($location)
    {
        $this->soapClient->__setLocation($location);
    }

    /**
     * @param       $method
     * @param array $params
     *
     * @return mixed
     * @throws SoapFault
     */
    protected function call($method, array $params = [])
    {
        $requestEvent = new Event\RequestEvent($method, $params);
        $this->dispatcher->dispatch(Events::REQUEST, $requestEvent);

        try {
            $result = $this->soapClient->$method($params);
        } catch (SoapFault $soapFault) {
            $this->dispatcher->dispatch(Events::FAULT, new Event\FaultEvent($soapFault, $requestEvent));
            throw $soapFault;
        }

        $this->dispatcher->dispatch(Events::RESPONSE, new Event\ResponseEvent($requestEvent, $result->result));
        return $result->result;
    }
}
