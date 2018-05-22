[![Latest Stable Version](https://poser.pugx.org/dl/assetsource-mediawiki/v/stable)](https://packagist.org/packages/dl/assetsource-mediawiki) [![Total Downloads](https://poser.pugx.org/dl/assetsource-mediawiki/downloads)](https://packagist.org/packages/dl/assetsource-mediawiki) [![License](https://poser.pugx.org/dl/assetsource-mediawiki/license)](https://packagist.org/packages/dl/assetsource-mediawiki)

# MediaWiki Asset Source

This asset source uses the public API of [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) installations like the [Wikipedia](https://en.wikipedia.org/w/api.php), [MediaWiki Commons](https://commons.mediaWiki.org) or [any other Media Wiki](https://wikistats.wmflabs.org/) instance to make the used assets searchable within a Neos installation.

## Installation

Install the package via composer:

	composer require dl/assetsource-mediawiki

## Configuration

You can add arbitrary instances of this asset source, to query different wikimedia instances - e.g. the english and german instance. To do that, just add another configuration block with the specific settings under a custom identifier.

| Setting                    | Description          |
| -------------------------- |----------------------|
| domain                     | The domain on which the MediaWiki instance is available.  |
| label                      | The label of the instance, shown in the backend  |
| searchStrategy             | A class with implemented search strategy. See the section below for details      |
| searchStrategyOptions      | Search strategy specific options      |
| useQueryResultCache        | Whether or not to use the result cache for queries to the API. If used, speeds up the pagination a lot but may return outdated results. The caching lifetime defaults to 1 day.      |
| excludedIdentifierPatterns | Asset identifiers which should be filtered out and not displayed. Used to filter out Wikipedias common icons. |

**Example for accessing the german Wikipedia:**

    Neos:
      Media:
        assetSources:
          wikipedia_de:
            assetSource: 'DL\AssetSource\MediaWiki\AssetSource\MediaWikiAssetSource'
            assetSourceOptions:
              domain: de.wikipedia.org
              label: Wikipedia (DE)
              searchStrategy: DL\AssetSource\MediaWiki\Api\SearchStrategies\ArticleSearchStrategy
              searchStrategyOptions:
                articleLimit: 10
              useQueryResultCache: true
              excludedIdentifierPatterns:
                 - '*.svg'
                 
## Search Strategies

Searching in the wikipedia for images is a bit tricky. First there is not only one wikipedia instance, but one for each available language. Second an image can be stored in the language specific wikipedia or in Wikimedia Commons and included from there. 

The package brings two different search strategies with different pros and cons.

### Direct Image Search Strategy

	searchStrategy: DL\AssetSource\MediaWiki\Api\SearchStrategies\DirectImageSearchStrategy

This search strategy uses the filename and available meta data like the description of an asset to search on. That means if you configure the `commons.wikimedia.org` as domain, the package will search through about ~50 Million asssets available in all languages. But for historical reasons, some images are stored directly in the language specific wikipedia instances and therefore not available with that setting.

### Article Search Strategy (Default)

	searchStrategy: DL\AssetSource\MediaWiki\Api\SearchStrategies\ArticleSearchStrategy
	
This search strategy fits better to the Wikipedia use case. It doesn't search the images directly but uses the more powerfull article search to receive a number of wiki articles and then queries the images shown on that articles. The benefit is, if you configure the domain to `en.wikipedia.org` you will get assets, that are uploaded directly to this instance, as well as all fitting assets uploaded to Wikimedia Commons

| Setting                   | Description                                |
| ------------------------- |--------------------------------------------|
| articleLimit              | How many articles should be taken into account to query images from. Maximum are 50 articles. Higher values result in more returned articles, but the results may get inaccurate |

## Usage of images in your project

Please take care of the [correct attribution](https://wiki.creativecommons.org/wiki/Best_practices_for_attribution) of used photos in the frontend.

## Known Issues

See the issue list for [known issues](https://github.com/daniellienert/assetsource-mediawiki/issues) and missing features.
