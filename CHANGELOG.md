# SEO Fields Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 4.0.9 - 2024-04-11
### Fixed
- Fixed missing meta description

## 4.0.8 - 2024-04-11
### Fixed
- Fixed missing meta description


## 4.0.6 - 2024-02-27
### Fixed
- Fixed support for Commerce Products ([#92](https://github.com/studioespresso/craft-seo-fields/issues/92))

## 4.0.5 - 2024-02-16
### Fixed
- Change schema version to fix an issue with migrations from 4.0.0 not being applied correctly.


## 4.0.4 - 2024-02-05
### Fixed
- Fixed an issue where the "Allow sitename to be hidden" option would not work ([#90](https://github.com/studioespresso/craft-seo-fields/issues/90))

## 4.0.3 - 2024-02-05
### Fixed
- Fixed a permissions issue on sites with multiple users and user groups


## 4.0.2 - 2024-01-21
### Fixed
- Fixed getting schema data for multi-site setups

## 4.0.1 - 2024-01-21
### Fixed
- Fixed on issue on the schema settings screen, when no settings had been defined yet

## 4.0.0 - 2024-01-20
### Added
- JSON-LD Schema.org markup
- Automatic redirect creation on URI change

### Fixed
- Fixed overriding meta values in templates ([new docs here](https://studioespresso.github.io/craft-seo-fields/templating.html#overwriting-field-values))

## 3.3.8.1 - 2023-11-24
### Fixed
- Fixed a twig syntax error ([#88](https://github.com/studioespresso/craft-seo-fields/pull/88))


## 3.3.8 - 2023-11-23
### Fixed
- Redirects and 404's now works for users that only have access to 1 site. ([#87](https://github.com/studioespresso/craft-seo-fields/issues/87))



## 3.3.7 - 2023-11-19
### Fixed
- Fixed an issue where page title wouldn't be used as fallback meta title ([#86](https://github.com/studioespresso/craft-seo-fields/issues/86))


## 3.3.6 - 2023-11-15
### Fixed
- Final fix for pagination on redirects & 404's ([#85](https://github.com/studioespresso/craft-seo-fields/pull/85))

## 3.3.5 - 2023-11-09
### Fixed
- Temporary fix for incomplete pagination on redirects ([#85](https://github.com/studioespresso/craft-seo-fields/pull/85))


## 3.3.4 - 2023-10-14
### Fixed
- Fixed an error where deleted sections were still shown in the sitemap settings ([#82](https://github.com/studioespresso/craft-seo-fields/issues/82))


## 3.3.3 - 2023-09-18
### Fixed
- Fixed an error when using a custom meta template ([#81](https://github.com/studioespresso/craft-seo-fields/issues/81))


## 3.3.2 - 2023-09-13
### Fixed
- Fixed an issue where sitemaps couldn't be saved for different sites

## 3.3.1 - 2023-08-08
### Fixed
- Permissions for defaults are now really fixed ([#77](https://github.com/studioespresso/craft-seo-fields/issues/77))
- Canoncial urls now take into account pagination and multsite subfolders ([#78](https://github.com/studioespresso/craft-seo-fields/issues/78))


## 3.3.0 - 2023-07-24
### Added
- Overview of 404s and redirects are now using Craft's internal adminTable, making them much more performant.

### Fixed
- Fixed a permissions issue where users would never get to see the "Default" menu ([#77](https://github.com/studioespresso/craft-seo-fields/issues/77))
- Removed the self-referencing canonical on 404 pages ([#71](https://github.com/studioespresso/craft-seo-fields/issues/71))
- Use full path for canonical URLs ([#78](https://github.com/studioespresso/craft-seo-fields/issues/78))

## 3.2.4 - 2023-06-30
### Fixed
- Fixed an error in the sitemap query where we were checking an incorrect collumn and not all items would be returned 

## 3.2.3 - 2023-06-08
### Fixed
- Fixed issue where redirect would always get a ? appended

## 3.2.2 - 2023-06-08
### Fixed
- Fixed issue where query parameters would be discarded on redirect
- Fixed issue where 404's wouldn't always be saved.


## 3.2.1 - 2023-04-04
### Fixed
- Fixed issue where elements weren't included in sitemaps in some cases ([#70](https://github.com/studioespresso/craft-seo-fields/issues/70))

## 3.2.0 - 2022-12-29
### Added
- 404's can now be filtered by All, handled only, not handled only
- Regex matching for redirects

## Fixed
- Imported redirects are assigned a valued sourceMatch again [#67](https://github.com/studioespresso/craft-seo-fields/issues/67)

## 3.1.2 - 2022-10-20
### Fixed
- Fixed an issue with an incorrect return type 

## 3.1.1 - 2022-10-15
### Fixed
- Fixed an issue where users with limited permissions would still be redirects to the defaults tab ([#65](https://github.com/studioespresso/craft-seo-fields/issues/65))

## 3.1.0 - 2022-10-03
### Added
- Redirects can now be site to ignore query parameters in the source url

## 3.0.5 - 2022-06-02
### Fixed
- Fixed an issue with canoncial urls when the site isn't running on the root url.

## 3.0.4 - 2022-05-24
### Fixed
- Fixed missing class in SeoFieldModel
- Fixed an issue with Twitter share images

## 3.0.3 - 2022-05-22
### Fixed
- Fixed a type error in the Robots & Sitemap controllers ([#60](https://github.com/studioespresso/craft-seo-fields/issues/60))

## 3.0.2 - 2022-05-11
### Fixed
- Fixed a error with plugin permissions

## 3.0.1 - 2022-05-05
### Fixed
- Fixed a PHP error when added a field

## 3.0.0 - 2022-05-01
### Added
- Craft 4 🚀

## 3.0.0-beta.1 - 2022-03-02
### Added
- Craft CMS 4 compatibility

## 2.0.8 - 2022-04-22
### Fixed
- Removed dump

## 2.0.7 - 2022-04-20
### Fixed
- Query parameters are no longer included in canoncial urls

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
