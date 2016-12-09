## 0.1.0 Beta 3 "Repeat Stuff, Repeat Stuff, Gotta Repeat Stuff"

Bug fixes, improved Windows support, and repeaters!

**New**

- Introduction of a new Repeater PageView
    - Repeater PageViews can also support automatic redirects to the permalink
- Added support for redirecting to PageViews or ContentItems
    - By making the `permalink` Front Matter attribute an array of URLs instead of a single one, you will enable automatic redirects. The first element in that list will be the permalink while all the others will redirect automatically to the first link
    - Redirects can be special templates you define in your `_config.yml` or a generic will be used
- Percent signs can be escaped in Front Matter now by using `\` to escape it
- Unit tests are now tested on AppVeyor in addition to Travis to test Windows

**Changes**

- The `menu` Twig variable is now an array of PageViews instead of an array with limited information
- FrontMatter evaluation has evolved into its own parser with supported for "expanded values"

**Fixes**

- The `order` Twig filter now works with PageViews
- Fixed fenced code blocks wouldn't render as escaped HTML
- RST include vulnerability has been fixed; everything is now jailed to the current working directory while building the website
- Fix `composer build` functionality on Windows
- Fix file paths used by Stakx internally to be Windows friendly
- Fix automatic permalink generation based on relative paths

## 0.1.0 Beta 2 "The Flash"

The compile time of a website and watch command startup time has been improved drastically.

**New**

- Add new `summary` Twig filter
- Twig in ContentItems is now evaluated
- The `where` Twig filter has a new `~=` comparison for strings and arrays.
    - For strings, it will return true if the string (left) contains the needle (right)
    - For arrays, it will return true if the needle exists inside of the array
- The `where` filter can now check against `null`
- Markdown headers (h1, h2, h3, etc.) automatically have an ID in the rendered HTML
- All internal manager classes now support tracking files for the `watch` command
- New `--no-clean` option has been added to **not** clean the `_site` (target) folder on build
- Verbose messages now show timestamps
- Using the `exclude` option in configuration files now ignores files or directories instead of filtering them out
    - e.g. `node_modules` is ignored by stakx by default; now, `node_modules` will be skipped entirely instead of being
      scanned and later ignored during the compile process

**Changes**

- Themes are now under the `theme` namespace for Twig templates
    - In order to extend a theme template in Twig, it must be accessed with `@theme` prefixed to it
- The `watch` command has been marked as experimental and has been rewritten from scratch
- Collection items are now stored by file name instead of counter-intuitive hashes
- Permalinks are now always lowercase
- Twig errors now display file paths instead of random hashes

**Fixes**

- Don't fail when an invalid configuration file is parsed
- The `where` Twig filter no longer fails with ContentItems
- Don't crash when a theme file doesn't have either the `exclude` or `include` section

## 0.1.0 Beta 1

**Changes**

- The `base` configuration file option has been superseded by `baseurl` and will be removed in version 1.0.0.

**Fixes**

- An error message now appears with SimpleXML isn't installed; e.g. PHP 7
- Having a base URL in the configuration file now outputs that website into that specified folder. For example, a
  website with a base URL of `super-site` will now create the website at: `_site/super-site/`
- The target directory where the compiled website is now cleared at every build
- The `url()` Twig function correctly outputs the base URL
- Exceptions no longer cause the program to die
- Errors or exception during the `watch` command no longer cause the program to stop watching and dying

## 0.1.0 Alpha 2

**New**

- Add partial support for rendering reStructuredText
- Content Items with unknown file extensions are rendered as-is

**Changes**

- Front Matter variable names can only be alphabetic characters
- The _Finder_ Twig function now requires a parameter of where to look at

**Fixes**

- Pemalinks are now sanitized and have invalid characters removed
- Files without an explicit `permalink` in the Front Matter get their permalink based on their location
- Invalid Yaml in Front Matter now stops execution
- Front Matter special values are now re-evaluated when a dynamic page is built
    - e.g. The `date` field creates the `%year`, `%month`, and `%day` fields automatically
- The _Finder_ Twig function now works

## 0.1.0 Alpha 1

**New**

- New `finder` Twig filter gives access to Symfony's Finder component
- New DataItems and DataSets have been introduced
- Add a `--safe` option to disable filesystem access from Twig
- New `markdown` Twig filter **and** tag
- Add new `url` Twig function to generate a URL with the `base` prepended
- Add new `group` Twig function to group array contents based on its contents
- Add new `--no-conf` option to build a website without a configuration
- Add extremely primitive `watch` command to rebuild the entire website every time there's a change
- Add new `file` Twig function access a file content's

**Changes**

- FrontMatter variables now begin with a `%` instead of `:`
    - e.g. `permalink: /blog/%year/%title/`
- Remove Laravel dependencies

**Fixes**

- Looping through empty collections no longer crashes
- PHAR archives now work with the current working directory
- Don't crash when no PageView folders are specified

## 0.0.0 "It builds!"

A very early tag of Stakx with only the `build` command mostly functional