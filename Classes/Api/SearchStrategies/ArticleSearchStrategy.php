<?php
namespace DL\AssetSource\Wikimedia\Api\SearchStrategies;


use DL\AssetSource\Wikimedia\Api\Dto\ImageSearchResult;
use Neos\Utility\Arrays;

class ArticleSearchStrategy extends AbstractSearchStrategy
{

    /**
     * @param string $term
     * @param int $offset
     * @return ImageSearchResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search(string $term, int $offset = 0): ImageSearchResult
    {
        $articleResultArray = $this->wikimediaClient->executeQuery([
            'list' => 'search',
            'srsearch' => $term,
            'srlimit' => 10
        ]);

        return $this->buildImageSearchResult($articleResultArray, $offset);
    }

    /**
     * @param array $searchResult
     * @param int $offset
     * @return ImageSearchResult
     * @throws \GuzzleHttp\Exception\GuzzleException
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

        $imageResultArray = $this->wikimediaClient->executeQuery([
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

        $imagesToExpand = array_slice($allImages, $offset, $this->itemsPerPage);

        foreach ($imagesToExpand as $imageTitle) {
            $imageSearchResult->addImageTitle(str_replace(' ', '_', $imageTitle));
        }

        $imageSearchResult->setTotalResults(count($allImages));

        return $imageSearchResult;
    }
}
