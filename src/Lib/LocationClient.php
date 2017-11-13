<?php

namespace VfTest\Lib;

use BeSimple\SoapClient\SoapClientBuilder;
use BeSimple\SoapClient\SoapClientOptionsBuilder;
use BeSimple\SoapCommon\SoapOptionsBuilder;
use BeSimple\SoapClient\SoapClient;

class LocationClient {

    /**
     * @var SoapClient
     */
    private $soapClient;

    /**
     * @constructor
     * @param Config $config VfTest Config service
     * @return void
     */
    public function __construct(Config $config) {
        $serviceConfig = $config->getConfigValue('location_service');
        $serviceEndpoint = $serviceConfig['endpoint'];
        $soapClient = $this->_createSoapClient($serviceEndpoint);
        $this->setSoapClient($soapClient);
    }

    /**
     * Sets SOAP Client
     * @param SoapClient $soapClient SOAP Client
     * @return string
     */
    public function setSoapClient(SoapClient $soapClient) {
        $this->soapClient = $soapClient;
    }

    /**
     * Creates SOAP Client
     * @param string $endpoint Service endpoint
     * @return SoapClient
     */
    private function _createSoapClient($endpoint) {
        $soapClientBuilder = new SoapClientBuilder();

        try {
            $soapClient = $soapClientBuilder->build(SoapClientOptionsBuilder::createWithDefaults(), SoapOptionsBuilder::createWithDefaults($endpoint));
        } catch (\SoapFault $e) {
            throw new \Exception('Search service failed, check internet connection or try again later');
        }

        return $soapClient;
    }

    /**
     * Performs post code search for given city name
     * @param string $cityName City name
     * @return array
     * @throws Exception
     */
    public function findPostalCode($cityName) {
        $soapRequest = new \stdClass();
        $soapRequest->Town = $cityName;

        try {
            $soapResponse = $this->soapClient->soapCall('GetUKLocationByTown', [$soapRequest]);
        } catch (SoapFaultWithTracingData $e) {
            throw new \Exception('Search service failed, check internet connection or try again later');
        }

        $xmlResponse = $soapResponse->getResponseObject()->GetUKLocationByTownResult;
        $simpleXMLResponse = simplexml_load_string($xmlResponse);
        $jsonResponse = json_encode($simpleXMLResponse);
        $arrayResponse = json_decode($jsonResponse, TRUE);

        if (isset($arrayResponse['Table'])) {
            $returnArray = $arrayResponse['Table'];
        } else {
            $returnArray = [];
        }

        return $returnArray;
    }

}
