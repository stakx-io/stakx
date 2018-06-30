<p align="center"><img alt="stakx logo" src=".github/brand.png"></p>

[![*nix build Status](https://img.shields.io/travis/stakx-io/stakx.svg)](https://travis-ci.org/stakx-io/stakx)
[![Windows build status](https://ci.appveyor.com/api/projects/status/0otqsetd0079jipd?svg=true)](https://ci.appveyor.com/project/allejo/stakx)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/stakx-io/stakx.svg)](https://scrutinizer-ci.com/g/stakx-io/stakx/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/stakx-io/stakx.svg)](https://scrutinizer-ci.com/g/stakx-io/stakx/?branch=master)

stakx is a static website generator built in PHP inspired by Jekyll and Sculpin. Unlike its alternatives, stakx is distributed as a single executable so you don't need to worry about silly `bundle install` or `composer install` commands to build your website.

## Philosophy

stakx's philosophy is to be treated as a model-view-controller (MVC) setup where stakx itself is the controller, Twig makes up the views, and your content makes up the models. Following this philosophy will allow you to have truly reusable content making migration to and from stakx a breeze.

## Building the Sample Project

stakx provides a sample project for people to learn from. The sample project can be built in one of two ways.

### Download a Release

Get the latest PHAR from the [Releases](https://github.com/stakx-io/stakx/releases) page, download the repository, put the PHAR in the `example` directory and run the following command from within the `example` directory:

```
php ./stakx.phar build
```

### Build from Git

Clone the repository, fetch the dependencies, and compile.

```bash
git clone https://github.com/stakx-io/stakx.git
composer install --no-dev
cd example/
php ../bin/stakx build
```

## License

[MIT](./LICENSE.md)
