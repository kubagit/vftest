<?php

namespace VfTest\Lib;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class CodeFinder {

    /**
     * @var LocationClient
     */
    private $locationClient;

    /**
     * @constructor
     * @param LocationClient $locationClient Location Service Client
     * @return void
     */
    public function __construct(LocationClient $locationClient) {
        $this->setLocationClient($locationClient);
    }

    /**
     * Sets Location Service Client
     * @param LocationClient $locationClient Location Service Client
     * @return void
     */
    public function setLocationClient(LocationClient $locationClient) {
        $this->locationClient = $locationClient;
    }

    /**
     * Checks and sanitizes input data
     * @param array $citiesArgs Arguments from cli (exploded by spaces)
     * @return array
     * @throws Exception
     */
    protected function _processInput(array $citiesArgs) {
        $wholeInputString = implode(' ', $citiesArgs);
        $citiesList = explode(',', $wholeInputString);
        $sanitizedInput = [];
        foreach ($citiesList as $cityName) {
            if ('' !== $trimmedCityName = trim($cityName)) {
                $sanitizedInput[] = $trimmedCityName;
            }
        }

        $citiesListCount = count($sanitizedInput);
        if ($citiesListCount < 2 || $citiesListCount > 3) {
            throw new \Exception('Please enter 2 or 3 cities separated by commas (,)' . PHP_EOL);
        }
        return $sanitizedInput;
    }

    /**
     * Formats array with one search result into string 
     * @param array $searchResultItem The one search result
     * @return string
     */
    protected function _formatSearchResultItem(array $searchResultItem) {
        $formattedString = $searchResultItem['Town'] . ', ' . $searchResultItem['County'] . ': ' . $searchResultItem['PostCode'] . PHP_EOL;

        return $formattedString;
    }

    /**
     * Formats array with search result
     * @param array $searchResultSet Array with search results for one city
     * @return string
     */
    protected function _formatSearchResultSet(array $searchResultSet) {
        if (count($searchResultSet) === 0) {
            $formattedString = 'No code found.' . PHP_EOL;
        } else {
            $formattedString = '';
            foreach ($searchResultSet as $searchResultItem) {
                $formattedString .= $this->_formatSearchResultItem($searchResultItem);
            }
        }

        return $formattedString;
    }

    /**
     * Formats array with search results for printing on console
     * @param array $searchResult Array with search results for cities
     * @return string
     */
    protected function _processOutput(array $searchResults) {
        $formattedSearchResults = '';

        foreach ($searchResults as $cityName => $searchResultSet) {
            $formattedSearchResults .= ('Outward codes for your search "' . $cityName . '":' . PHP_EOL);
            $formattedSearchResults .= ($this->_formatSearchResultSet($searchResultSet) . PHP_EOL . PHP_EOL);
        }

        return $formattedSearchResults;
    }

    /**
     * Performs post code searches for given cities 
     * @param array $citiesArgs Arguments from cli (exploded by spaces)
     * @return string
     */
    public function findPostalCodes(array $citiesArgs) {
        $citiesList = $this->_processInput($citiesArgs);

        $searchResults = [];
        foreach ($citiesList as $cityName) {
            $searchResults[$cityName] = $this->locationClient->findPostalCode($cityName);
        }

        $formattedSearchResults = $this->_processOutput($searchResults);

        return $formattedSearchResults;
    }

}
