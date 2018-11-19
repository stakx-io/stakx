## HEAD

**New**

- The `url()` Twig filter can now link to Repeater PageViews ([#78](https://github.com/stakx-io/stakx/pull/78))
- Twig now has a new global template called `repeaters` to access all of the Repeater PageViews; these PageViews are indexed based on the `title` FrontMatter value just like Static PageViews.
- Added new `slug` Twig filter
- The 500 error page in the `serve` command now shows more useful information about the exception that was thrown
- Sass Asset Engine now caches the Sass AST it builds inside of `.stakx-cache/`. Using the `--use-cache` flag will enable stakx to read this cache and only rebuild the updated Sass ([#91](https://github.com/stakx-io/stakx/pull/91))

**Changes**

- Passing a `JailedDocument` into the Twig `dump()` filter will now output useful information; such as, all of the accessible FrontMatter and whitelisted methods.
- Heading IDs inside of the markdown engine now use the same algorithm as the Twig `slug()` filter
- Underscore folders inside of themes are now automatically ignored ([#87](https://github.com/stakx-io/stakx/pull/87))
- The Sass compiler has been updated to better handle AST caching especially during `serve` and `--use-cache`
- The `where()` Twig filter logic has been simplified and longer works recursively, which was "undefined behavior" to begin with

**Fixes**

- Feeding `JailedDocument`s into the Twig `dump()` filter will no longer cause an infinite loop
- Sass is correctly updated and compiled during the `serve` command; you no longer need to restart the server to get your Sass to recompile ([#86](https://github.com/stakx-io/stakx/pull/86))
- FrontMatter from Dynamic PageViews are now accessible through children DataItems ([#93](https://github.com/stakx-io/stakx/issues/93)); information from the DataItem will override PageView FrontMatter
- DataItems part of Datasets now have the `filePath` variable to match ContentItems

**Development**

- The `ReadableDocument` object now has support for getting and setting metadata available to stakx internals
- The `serve` infrastructure has been refactored and has had a lot of renaming to have more intuitive names
- CompileProcess events have been renamed and split up into separate events for each PageView type and now contain more useful information

## 0.2.0 Alpha 2

**New**

- Add new `serve` command to take the place of the defunct `watch` command
- Your `*.scss.twig` files now support the `@theme` directive to import partials relative to your theme's `_sass` folder

**Fixes**

- Nested folders inside of `_sass` no longer throw errors

## 0.2.0 Alpha 1

The first alpha tag of the next major release of stakx with a rebuilt core, Scss support, and Twig improvements.

**Deprecations**

- The `watch` command has been removed
- The `--no-conf` flag has been removed; all sites now need a configuration file

**New**

- FrontMatter now has "complex variables," meaning you can inject variables from your site's configuration file into any FM block
- The `select`, `order`, `where`, and `group` Twig filters have support for dot notation to access nested data in arrays
- Make use Symfony's Event Dispatcher throughout our core to dispatch events for core and possibly third-party plugins
- Add Scss and source map support in a `_sass` folder

**Change**

- The "working directory" of websites is now relative to the given configuration file
- Dynamic PageViews are no longer accessible through Twig

**Fixes**

- Twig files through `{% include %}` are now treated as dependencies for the watch functionality
- The `summary` and `toc` Twig filters now better support HTML5 and are more robust
- The `basename` and `filename` variables are available in FrontMatter

**Development**

- A **lot** of restructuring and moving of namespaces
- All references to files are now handled by a dedicated `File` object
- Add Symfony container, which will autowrite classes and support dependency injection
- Use the official highlight.php library again, which is now maintained again
- Templating has been abstracted out into interfaces; a new Twig to stakx bridge has been created
- Updated to Symfony 3.4.x components
- Abstracted out data transformers into separate classes with an interface for DataItems
- Twig filters and functions are now handled through the container
- Improved cross-platform support for file paths used internally
- Markup engines have been abstracted out into interfaces and separate classes
- Standardized filesystem reads and writes throughout core
- The internal `Service` singleton uses bitwise flags instead of an array of arbitrary keys

## 0.1.3 "Pacifist Tasmanian Devil"

A maintenance release with small bug fixes.

**New**

- Added new `toc` filter that will pick out headings from given HTML and make them into an unordered list

**Change**

- All HTML headings now have IDs generated for them in markdown content

**Fixes**

- Parsing HTML isn't the same as XML, so filters like `summary` have been made more robust and knowledgable about HTML
- ContentItems not being a part of a collection no longer crashes the watch command
- DataItems now correctly have access to `basename`, `permalink`, `targetFile`, and `redirects` for when they are in Datasets

## 0.1.2 "Watchful Bandicoot"

For this release, all effort was focused on stabilizing the `watch` command and getting it out of its experimental phase.

Here's the changelog since the beta 1 release:

**New**

- Add new `build.preserveCase` option to the configuration file which allows you to preserve the case of a permalink path. This option defaults to false meaning all permalinks are still converted to lower case. ([#55](https://github.com/stakx-io/stakx/issues/55))
- ContentItems now support a `redirect_from` option in the FrontMatter to have custom redirects on a per ContentItem basis ([#26](https://github.com/stakx-io/stakx/issues/26))

**Fixes**

- Watching a folder with spaces in any part of the path works as intended
- The target folder is automatically excluded and will no longer have a nested version of itself ([#54](https://github.com/stakx-io/stakx/issues/54))
- The `basename` attribute is now available in ContentItems that don't have a parent PageView set ([#53](https://github.com/stakx-io/stakx/issues/53))

## 0.1.2 Beta 1

**New**

- You can define custom syntax highlighting definitions for your own languages ([#51](https://github.com/stakx-io/stakx/issues/51))
- Added new `zip` Twig filter
- Added new `ignore_null` option to the `select` Twig filter
- Added new `sha1` and `md5` Twig filters

**Changes**

- Improve Twig dependency detection in PageViews
    - Collections and datasets with an underscore in the name are now handled correctly
- Add more languages to our syntax highlighter ([#50](https://github.com/stakx-io/stakx/issues/50))
- More helpful error messages are now outputted during the watch command
- The `url` Twig filter has received a new `absolute` boolean
    - When set to true, the generated URL takes `url` from your `_config.yml` and prepends it to the URL

**Fixes**

- The `where` Twig filter correctly checks against ArrayAccess objects
- Correctly handle file paths in Twig's `{% extends %}` during watch while on Windows
- Fix asset tracking on Windows during the watch command
- Files that were `{% import %}`'d in Twig templates now rebuild during watch
- Empty files created during watch no longer through an error. Errors are only thrown now after you're editing the file
- Markdown headings created with `===` or `---` now have IDs rendered in the HTML

**Development**

- Internal interfaces have been renamed
    - `JailedDocumentInterface` -> `JailableDocument`
    - `TwigDocumentInterface` -> `TwigDocument`

## 0.1.1 "Eccentric Wallaby"

**New**

- Introduced new `import` keyword, which allows you to import other configuration files for different dev environments ([#42](https://github.com/stakx-io/stakx/pull/42))
- Introduce new `--use-drafts` option to the build command, which looks for `draft: true` in the FrontMatter of ContentItems. Any "draft" will not have a file written to the build folder and will not appear in the `collections` array in Twig ([#45](https://github.com/stakx-io/stakx/pull/45))
- The `watch` function now supports more external tools in addition to polling the file system
    - [watchmedo](https://pythonhosted.org/watchdog/) (Cross platform pything shell utility)
    - [fswatch](http://emcrisostomo.github.io/fswatch/) (Cross-platform file change monitor with multiple backends)
    - [inotifywait](http://linux.die.net/man/1/inotifywait) (Linux shell utility)
    - [inotify](http://php.net/manual/en/book.inotify.php) (Php PECL extension)
- Add new `--profile` option to show stats on time spent on each Twig template
- Two new default FrontMatter keys have been added to all FrontMatter documents
    - `filename` - the full name of the file
    - `basename` - the name of the file without the extension
- All data is now housed in DataItem objects that can be used for more information about the data file ([#48](https://github.com/stakx-io/stakx/pull/48))
- Dynamic PageViews now support a new `dataset` FrontMatter key that will allow you to use DataItems in a DataSet the same way as ContentItems in a Collection ([#48](https://github.com/stakx-io/stakx/pull/48))
- Introduce new `select()` Twig filter that will extract the values from the respective keys of an array of elements and flatten the items in addition to removing duplicate values
- In addition to DataItem folders, ContentItem and PageView folders can now use the `.example` extension ([#33](https://github.com/stakx-io/stakx/issues/33))

**Changes**

- The name on Packagist has been changed to `stakx/stakx`. Since there wasn't any usage, the previous `stakx-io/stakx` package has been deleted.
- `JailObject` have been renamed to `JailedDocument`
- Generic Twig runtime errors have been improved to provide more user-friendly information

**Fixes**

- Permalinks with several periods or FrontMatter variables containing periods would fail to build
- stakx now sets exit values greater than 0 when a website fails to build
- Using `watch` will now compile changes to parent templates now
- Line numbers shown in Twig errors have been corrected to be accurate
- Assets are now copied correctly during the `watch` process; a notice of an undefined index of 'prefix' has been silenced
- PageViews accessing either `data` or `collections` directly are now recompiled when the respective content is updated ([#16](https://github.com/stakx-io/stakx/issues/16))

**Development**

- Restructured the project to be PSR-4 complaint
- Change visibility of tracking class functions
- Always use `realpath()` in FileExplorer
- The `menu` Twig variable is now handled by its own class
- All file writing has been moved to a dedicated Compiler class
- PageManager class has been refactored to solely handle PageViews and nothing else
- Explicit file locks aren't used for writing files anymore
- A lot of namespaces for internal classes have been changed
- Build settings have begun to be moved to `Service` singleton for global access to settings

## 0.1.0 "Immortal Wombat"

**New**

- Added new `pages` variable which provides access to all static pages a stakx website has
- The `url()` Twig function now accepts any PageView or CollectionItem for generating URLs
- Data files with a `.example` extension are ignored (#35)
- Errors occurring due to a file's syntax now display the path to the file to the console (#34)
- Add Markdown Extra support
- All generated code blocks now have the `hljs` class added to it

**Changes**

- Ensure only JailObjects are given to Twig
- Children of hidden parents in the `menu` variable are no longer accessible
- All FrontMatter objects implement `ArrayAccess`
- License file has been changed to MIT
- The `group` Twig filter has been improved with several crash bugs fixed
    - If a value is not set in the Front Matter, it will be discarded in the `group` result
    - Grouping by booleans is now possible; a 'true' or 'false' literal will be used
- All FrontMatter objects no longer make use of magic methods (this should have no affect on websites)
- The `menu` variable only contains pages with a `title` FrontMatter key
- More errors thrown contain information regarding the path of the file that triggered the error instead of just the error

**Fixes**

- Fix calls to jailed functions in JailObjects
- Parsing FrontMatter files has improved cross-platform support
- The `where` Twig filter works better with null values
- Don't crash when using `watch` and no theme is present
- Nested siblings in the `menu` variable no longer override each other
- An error is now thrown when an unknown collection is referenced in a dynamic PageView instead of crashing

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
- All objects passed to Twig as `this` are now *JailObjects* which restrict the functions that can be called from Twig; this will prevent undefined behavior
- Improved messages thrown by exceptions

**Fixes**

- Fix broken Watch command where nothing would rebuild
- The `order` Twig filter now works with PageViews
- Fixed fenced code blocks wouldn't render as escaped HTML
- RST include vulnerability has been fixed; everything is now jailed to the current working directory while building the website
- Fix `composer build` functionality on Windows
- Fix file paths used internally to be Windows friendly
- Fix automatic permalink generation based on relative paths
- `this` in Twig will always refer to an object now, instead of just FrontMatter (which PageView was an offender of and didn't allow functions to be called)
- Twig error line numbers now take into account the offset of the FrontMatter in the document
- Twig errors now show the correct relative file path instead of just the filename
- The `where` Twig filter now works with any PageView type in addition to ContentItems
- Declaring both `baseurl` and `base` in the site's configuration leads to `baseurl` taking precedence
- Dates or timestamps evaluated from the `date` field (and the respective `year`, `month`, and `day` fields) in Front Matter are evaluated with respect to the timezone set in *php.ini*
- Fix issue of cache creation when running from a PHAR

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
    - e.g. `node_modules` is ignored by default; now, `node_modules` will be skipped entirely instead of being
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

A very early tag of stakx with only the `build` command mostly functional
