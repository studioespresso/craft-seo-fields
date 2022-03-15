# SEO Fields Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.0.6 - 2022-03-15
### Fixed
- Fixed in issue where ``dateModified`` properties in sitemap.xml could in incorrect.

## 2.0.5 - 2022-01-10
### Fixed
- Fixed an error when deleting a section ([#48](https://github.com/studioespresso/craft-seo-fields/issues/48))

## 2.0.4 - 2021-12-06
### Fixed
- Beter fix for the 404 overview page when referral urls are really long

## 2.0.3
### Fixed
- Fixed a layout issue on the 404 overview page when referral urls are really long

## 2.0.2
### Fixed
- Better handing for redirects that apply to all site ids

## 2.0.1
### Fixed
- Fixed an issue with redirect imports and league/csv

## 2.0.0
### Added
- 404 tracking
- Redirects
- The plugin label in the sidebar can be changed in settings

### Changes
- Defaults is now renamed to "Meta" in the CP sidebar

## 2.0.0-beta.4
### Fixed
- Fixed issue with alternate links

## 2.0.0-beta.3
### Fixed
- Allow league/csv 8.2 or 9.x

## 2.0.0-beta.2
### Fixed
- Added self-referencing hreflang tag
- Fixed hrefland links for homepages 

## 2.0.0-beta.1
### Added
- 404 tracking
- Redirects
- The plugin label in the sidebar can be changed in settings

### Changes
- Defaults is now renamed to "Meta" in the CP sidebar

## 1.2.3 - 2021-04-26
### Fixed
- Fixed an issue with disabled entries showing up in sitemaps ([#30](https://github.com/studioespresso/craft-seo-fields/pull/30))


## 1.2.2 - 2021-02-03
### Fixed
- Fixed an issue with sitemap querries on installed that use a tableprefix.

## 1.2.1 - 2020-10-16
### Fixed
- Fixed an issue where deleted sites were still included in queries


## 1.2.0 - 2020-10-15
### Added
- Added support for 3.5's option to disable sites [#28](https://github.com/studioespresso/craft-seo-fields/issues/28)

## 1.1.0 - 2020-10-08
### Added
- It's now possible to overwrite each SEO file in template. Docs can be found [here](https://studioespresso.github.io/craft-seo-fields/templating.html#overwriting-field-values)


## 1.0.9 - 2020-08-09
### Fixed
- Fixed an issue with the meta querries in Craft 3.5

## 1.0.8 - 2020-08-05
### Fixed
- Fixed an issue with the sitemap querries in Craft 3.5

## 1.0.7 - 2020-06-05
### Fixed
- Fixed an issue with installs that use a table prefix
- Fixed an issue with date formats in the sitemaps

## 1.0.6 - 2020-04-13
### Fixed
- Fixed a crash when a section that was included in the sitemap was deleted. ([#21](https://github.com/studioespresso/craft-seo-fields/issues/21))

## 1.0.5 - 2019-12-06
### Fixed
- Fixed an issue with rendering robots.txt templates

## 1.0.4 - 2019-11-27
### Fixed 
- Fixed lastmod date format in sitemaps ([#17](https://github.com/studioespresso/craft-seo-fields/issues/17))

## 1.0.3 - 2019-10-31
### Fixed
- Fixed a an issue with sitemap detail routing ([#15](https://github.com/studioespresso/craft-seo-fields/issues/15))
- Fixed a crash when viewing entries ([#16](https://github.com/studioespresso/craft-seo-fields/issues/16))

## 1.0.2 - 2019-10-30
### Fixed
- Fixed an issue where sitemaps for section were not being cached correctly. ([#14](https://github.com/studioespresso/craft-seo-fields/issues/14))


## 1.0.1 - 2019-08-13
### Fixed
- Fixed an issue where sitemaps weren't accessible for anonymous users. ([#11](https://github.com/studioespresso/craft-seo-fields/issues/11))

## 1.0.0 - 2019-04-17
### Added
- Initial release 🎉
