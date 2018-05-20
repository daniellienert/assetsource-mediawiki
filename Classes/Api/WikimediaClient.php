<?php
namespace DL\AssetSource\Wikimedia\Api;

/*
 * This file is part of the DL.AssetSource.Pexels package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use GuzzleHttp\Client;
use Neos\Flow\Http\Uri;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Utility\Arrays;

class WikimediaClient
{
    /**
     * @var string
     */
    protected $domain;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $queryResults = [];

    /**
     * @var PsrSystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * The result limit for the simple filename query
     * @var int
     */
    protected $totalResultLimit = 500;

    /**
     * @var int
     */
    protected $itemsPerPage = 20;

    /**
     * WikimediaClient constructor.
     * @param string $domain
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param string $term
     * @param int $offset
     * @return WikimediaQueryResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search(string $term, int $offset = 0): WikimediaQueryResult
    {
        $searchResultArray = $this->executeQuery([
            'titles' => $term,
            'prop' => 'images',
            'imlimit' => $this->totalResultLimit
        ]);

        return $this->expandSearchResultForPage($searchResultArray, $offset);
    }

    /**
     * @param int $offset
     * @return WikimediaQueryResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findAll(int $offset = 0): WikimediaQueryResult
    {
        $resultArray = $this->executeQuery([
            'list' => 'allimages',
            'aisort' => 'timestamp',
            'aidir' => 'older',
            'ailimit' => $this->totalResultLimit
        ]);
        $fileNames = [];
        $allImages = Arrays::getValueByPath($resultArray, 'query.allimages');
        $imagesToExpand = array_slice($allImages, $offset, $this->itemsPerPage);

        foreach ($imagesToExpand as $image) {
            $fileNames[] = str_replace(' ', '_', $image['title']);
        }

        return new WikimediaQueryResult($this->getAssetDetails($fileNames), $this->countAll());
    }

    /**
     * Count all asset
     *
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function countAll(): int
    {
        $result = $this->executeQuery([
            'siprop' => 'statistics',
            'meta' => 'siteinfo'
        ]);

        return Arrays::getValueByPath($result, 'query.statistics.images');
    }


    /**
     * @param array $searchResult
     * @param int $offset
     * @return WikimediaQueryResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function expandSearchResultForPage(array $searchResult, int $offset): WikimediaQueryResult
    {
        $fileNames = [];

        $pages = Arrays::getValueByPath($searchResult, 'query.pages');

        if (!is_array($pages) || !isset(current($pages)['images'])) {
            return new WikimediaQueryResult([], 0);
        }

        $allImages = current($pages)['images'];
        $imagesToExpand = array_slice($allImages, $offset, $this->itemsPerPage);

        foreach ($imagesToExpand as $image) {
            $fileNames[] = str_replace(' ', '_', $image['title']);
        }

        return new WikimediaQueryResult($this->getAssetDetails($fileNames), count($allImages));
    }

    /**
     * @param array $fileNames
     * @param int $thumbSize
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAssetDetails(array $fileNames, $thumbSize = 240): array
    {
        $items = [];

        $iiprop = 'url|size|metadata|extmetadata|user';
        $titles = implode('|', $fileNames);

        $assetDetails = $this->executeQuery([
            'prop' => 'imageinfo',
            'titles' => $titles,
            'iiprop' => $iiprop,
            'iiurlwidth' => $thumbSize,
            'iiurlheight' => $thumbSize,
        ]);

        $pages = Arrays::getValueByPath($assetDetails, 'query.pages');

        foreach ($pages as $key => $page) {
            if(!isset($page['imageinfo'])) {
                continue;
            }

            $identifier = str_replace(' ', '_', $page['title']);
            $items[$identifier] = current($page['imageinfo']);

            $items[$identifier]['identifier'] = $identifier;
            $items[$identifier]['filename'] = substr($page['title'], 5);
        }

        return $items;
    }

    /**
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function executeQuery(array $data): array
    {
        $queryUrl = $this->buildQueryUrl($data);
        $result = $this->getClient()->request('GET', $queryUrl);

        $this->logger->debug('Executed Query to wikimedia API "' . $queryUrl . '"');

        return \GuzzleHttp\json_decode($result->getBody(), true);
    }

    /**
     * @param array $data
     * @return string
     */
    protected function buildQueryUrl(array $data): string
    {
        $data['action'] = 'query';
        $data['format'] = 'json';

        $uri = sprintf('https://%s/w/api.php?%s', $this->domain, http_build_query($data));

        return $uri;
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        if ($this->client === null) {
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'Neos Asset Source Client'
                ]
            ]);
        }

        return $this->client;
    }
}
