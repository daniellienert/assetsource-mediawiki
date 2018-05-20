<?php
namespace DL\AssetSource\Wikimedia\Api\SearchStrategies;


use DL\AssetSource\Wikimedia\Api\Dto\ImageSearchResult;
use Neos\Utility\Arrays;

class DirectImageSearchStrategy extends AbstractSearchStrategy
{

    /**
     * @param string $term
     * @param int $offset
     * @return ImageSearchResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search(string $term, int $offset = 0): ImageSearchResult
    {
        $searchResultArray = $this->wikimediaClient->executeQuery([
            'titles' => $term,
            'prop' => 'images',
            'imlimit' => $this->totalResultLimit
        ]);

        return $this->buildImageSearchResult($searchResultArray, $offset);
    }

    /**
     * @param array $searchResult
     * @param int $offset
     * @return ImageSearchResult
     */
    protected function buildImageSearchResult(array $searchResult, int $offset): ImageSearchResult
    {
        $imageSearchResult = new ImageSearchResult();

        $pages = Arrays::getValueByPath($searchResult, 'query.pages');

        if (!is_array($pages) || !isset(current($pages)['images'])) {
            return $imageSearchResult;
        }

        $allImages = [];
        foreach (current($pages)['images'] as $imageArray) {
            $allImages[] = $imageArray['title'];
        }

        $this->filterExcludedImages($allImages);

        $imagesToExpand = array_slice($allImages, $offset, $this->itemsPerPage);

        foreach ($imagesToExpand as $image) {
            $imageSearchResult->addImageTitle(str_replace(' ', '_', $image));
        }

        $imageSearchResult->setTotalResults(count($allImages));

        return $imageSearchResult;
    }
}
