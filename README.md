# Stakx

[![Build Status](https://img.shields.io/travis/stakx-io/stakx.svg?maxAge=2592000)](https://travis-ci.org/stakx-io/stakx)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/stakx-io/stakx.svg?maxAge=2592000)](https://scrutinizer-ci.com/g/stakx-io/stakx/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/stakx-io/stakx.svg?maxAge=2592000)](https://scrutinizer-ci.com/g/stakx-io/stakx/?branch=master)
[![Dependency Status](https://img.shields.io/versioneye/d/user/projects/57b8ba4e090d4d0039befe69.svg?maxAge=2592000)](https://www.versioneye.com/user/projects/57b8ba4e090d4d0039befe69)

Stakx is a static website generator built in PHP as a powerful alternative to Jekyll or Sculpin. If you have PHP installed on your computer, download Stakx from our Releases and run it! There is no need to download dependencies (`bundle install` or `composer install`) like other tools, everything is bundled in a single executable that just works.

## Philosophy

Stakx's philosophy is to be treated as a model-view-controller (MVC) setup where Stakx itself is the controller, Twig makes up the views, and your content makes up the models. Keeping this mind, writing websites with Stakx will allow for a lot of truly reusable code for you to use on multiple websites or share with the community.

## Building the Sample Project

Stakx provides a sample project for people to learn from. The sample project can be built in one of two ways.

### Download a Release

Get the latest PHAR from the [Releases](https://github.com/stakx-io/stakx/releases) page, download the repository, put the PHAR in the `example` directory and run the following command from within the `example` directory:

```
./stakx.phar build
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

See the [LICENSE](https://github.com/stakx-io/stakx/blob/master/LICENSE.md) file.
