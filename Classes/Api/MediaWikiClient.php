<?php
namespace DL\AssetSource\MediaWiki\Api;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use DL\AssetSource\MediaWiki\Api\Dto\ImageSearchResult;
use Neos\Cache\Frontend\VariableFrontend;
use GuzzleHttp\Client;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Utility\Arrays;

class MediaWikiClient
{
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
     * @var VariableFrontend
     * @Flow\Inject
     */
    protected $queryResultCache;

    /**
     * @var bool
     */
    protected $useQueryResultCache = false;

    /**
     * MediaWikiClient constructor.
     *
     * @param string $domain
     * @param bool $useQueryResultCache
     */
    public function __construct(string $domain, bool $useQueryResultCache)
    {
        $this->domain = $domain;
        $this->useQueryResultCache = $useQueryResultCache;
    }

    /**
     * @param int $offset
     * @return ImageSearchResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findAll(int $offset = 0): ImageSearchResult
    {
        $resultArray = $this->executeQuery([
            'list' => 'allimages',
            'aisort' => 'timestamp',
            'aidir' => 'older',
            'ailimit' => $this->totalResultLimit
        ]);

        $allImages = Arrays::getValueByPath($resultArray, 'query.allimages');
        $imageCollection = new ImageSearchResult([], $this->countAll());
        $imagesToExpand = array_slice($allImages, $offset, $this->itemsPerPage);

        foreach ($imagesToExpand as $image) {
            $imageCollection->addImageTitle(str_replace(' ', '_', $image['title']));
        }

        return $imageCollection;
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
     * @param ImageSearchResult $imageSearchResult
     * @param int $thumbSize
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getAssetDetails(ImageSearchResult $imageSearchResult, $thumbSize = 240): MediaWikiQueryResult
    {
        $items = [];
        $iiprop = 'url|size|metadata|extmetadata|user';
        $titles = implode('|', $imageSearchResult->getImageTitles()->toArray());

        $assetDetails = $this->executeQuery([
            'prop' => 'imageinfo',
            'titles' => $titles,
            'iiprop' => $iiprop,
            'iiurlwidth' => $thumbSize,
            'iiurlheight' => $thumbSize,
        ]);

        $pages = Arrays::getValueByPath($assetDetails, 'query.pages');

        foreach ($pages as $key => $page) {
            if (!isset($page['imageinfo'])) {
                continue;
            }

            $identifier = str_replace(' ', '_', $page['title']);
            $items[$identifier] = current($page['imageinfo']);

            $items[$identifier]['identifier'] = $identifier;
            $items[$identifier]['filename'] = explode(':', $page['title'])[1];
        }

        return new MediaWikiQueryResult($items, $imageSearchResult->getTotalResults());
    }

    /**
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeQuery(array $data): array
    {
        $queryUrl = $this->buildQueryUrl($data);

        if ($this->useQueryResultCache) {
            $queryHash = sha1($queryUrl);
            if ($this->queryResultCache->has($queryHash)) {
                $this->logger->debug('Received result for API-Query  "' . $queryUrl . '" from cache');
                return $this->queryResultCache->get($queryHash);
            }
        }

        $result = $this->getClient()->request('GET', $queryUrl);
        $resultData = \GuzzleHttp\json_decode($result->getBody(), true);

        $this->logger->debug('Executed Query to mediawiki API "' . $queryUrl . '"');
        if ($this->useQueryResultCache) {
            $this->queryResultCache->set($queryHash, $resultData);
        }

        return $resultData;
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
