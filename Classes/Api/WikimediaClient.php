<?php
namespace DL\AssetSource\Wikimedia\Api;

/*
 * This file is part of the DL.AssetSource.Pexels package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\AssetSource\Wikimedia\Api\Dto\ImageSearchResult;
use Neos\Flow\Annotations as Flow;
use GuzzleHttp\Client;
use Neos\Flow\Log\PsrSystemLoggerInterface;
use Neos\Utility\Arrays;

class WikimediaClient
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
     * WikimediaClient constructor.
     * @param string $domain
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
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
    public function getAssetDetails(ImageSearchResult $imageSearchResult, $thumbSize = 240): WikimediaQueryResult
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
            if(!isset($page['imageinfo'])) {
                continue;
            }

            $identifier = str_replace(' ', '_', $page['title']);
            $items[$identifier] = current($page['imageinfo']);

            $items[$identifier]['identifier'] = $identifier;
            $items[$identifier]['filename'] = explode(':',$page['title'])[1];
        }

        return new WikimediaQueryResult($items, $imageSearchResult->getTotalResults());
    }

    /**
     * @param array $data
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function executeQuery(array $data): array
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
