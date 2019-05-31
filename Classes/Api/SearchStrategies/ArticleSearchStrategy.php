<?php
declare(strict_types=1);
namespace DL\AssetSource\MediaWiki\Api\SearchStrategies;

/*
 * This file is part of the DL.AssetSource.MediaWiki package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DL\AssetSource\MediaWiki\Api\Dto\ImageSearchResult;
use GuzzleHttp\Exception\GuzzleException;
use Neos\Utility\Arrays;

class ArticleSearchStrategy extends AbstractSearchStrategy
{

    /**
     * @var int
     */
    protected $articleLimit;

    public function initializeObject(): void
    {
        $this->articleLimit = $this->assetSource->getOption('searchStrategyOptions.articleLimit') ?? 10;
    }

    /**
     * @param string $term
     * @param int $offset
     * @return ImageSearchResult
     * @throws GuzzleException
     * @throws \Neos\Cache\Exception
     */
    public function search(string $term, int $offset = 0): ImageSearchResult
    {
        $articleResultArray = $this->mediaWikiClient->executeQuery([
            'list' => 'search',
            'srsearch' => $term,
            'srlimit' => $this->articleLimit
        ]);

        return $this->buildImageSearchResult($articleResultArray, $offset);
    }

    /**
     * @param string[] $searchResult
     * @param int $offset
     * @return ImageSearchResult
     * @throws GuzzleException
     * @throws \Neos\Cache\Exception
     */
    protected function buildImageSearchResult(array $searchResult, int $offset): ImageSearchResult
    {
        $imageSearchResult = new ImageSearchResult();
        $documentIds = [];
        $documentResults = Arrays::getValueByPath($searchResult, 'query.search');

        foreach ($documentResults as $documentResult) {
            if (isset($documentResult['pageid'])) {
                $documentIds[] = $documentResult['pageid'];
            }
        }

        $imageResultArray = $this->mediaWikiClient->executeQuery([
            'prop' => 'images',
            'pageids' => implode('|', $documentIds),
            'imlimit' => $this->totalResultLimit
        ]);

        $pages = Arrays::getValueByPath($imageResultArray, 'query.pages');

        if (!is_array($pages)) {
            return $imageSearchResult;
        }

        $allImages = [];
        foreach ($pages as $page) {
            if (isset($page['images']) && is_array($page['images'])) {
                foreach ($page['images'] as $image) {
                    if (isset($image['title'])) {
                        $allImages[] = $image['title'];
                    }
                }
            }
        }

        $this->filterExcludedImages($allImages);
        $imagesToExpand = array_slice($allImages, $offset, $this->itemsPerPage);

        foreach ($imagesToExpand as $imageTitle) {
            $imageSearchResult->addImageTitle(str_replace(' ', '_', $imageTitle));
        }

        $imageSearchResult->setTotalResults(count($allImages));

        return $imageSearchResult;
    }
}
