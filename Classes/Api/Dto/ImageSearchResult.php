<?php
namespace DL\AssetSource\Wikimedia\Api\Dto;

use Doctrine\Common\Collections\ArrayCollection;

class ImageSearchResult
{
    /**
     * @var ArrayCollection
     */
    protected $imageTitles;

    /**
     * @var int
     */
    protected $totalResults;

    public function __construct(array $imageTitles = [], int $totalResults = 0)
    {
        $this->imageTitles = new ArrayCollection($imageTitles);
        $this->totalResults = $totalResults;
    }

    /**
     * @return int
     */
    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    /**
     * @param int $totalResults
     */
    public function setTotalResults(int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }

    /**
     * @return ArrayCollection
     */
    public function getImageTitles(): ArrayCollection
    {
        return $this->imageTitles;
    }

    /**
     * @param string $imageTitle
     */
    public function addImageTitle(string $imageTitle) {
        $this->imageTitles->add($imageTitle);
    }
}
