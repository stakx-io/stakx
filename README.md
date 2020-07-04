<p align="center"><img alt="stakx logo" src=".github/brand.png"></p>

[![Unit Tests](https://github.com/stakx-io/stakx/workflows/Unit%20Tests/badge.svg)](https://github.com/stakx-io/stakx/actions)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/stakx-io/stakx.svg)](https://scrutinizer-ci.com/g/stakx-io/stakx/?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/stakx-io/stakx.svg)](https://scrutinizer-ci.com/g/stakx-io/stakx/?branch=master)

Stakx is a static website generator built in PHP inspired by Jekyll and Sculpin. Unlike its alternatives, Stakx is distributed as a single executable so you don't need to worry about silly `bundle install` or `composer install` commands to build your website.

## Philosophy

Stakx's philosophy is to be treated as a model-view-controller (MVC) setup where Stakx itself is the controller, Twig makes up the views, and your content makes up the models. Following this philosophy will allow you to have truly reusable content making migration to and from Stakx a breeze.

## Building the Sample Project

Stakx provides a sample project for people to learn from. The sample project can be built in one of two ways.

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

### Locally Serving a Site

Stakx includes a built-in web server, which you can run with the following commands. Access your site at `http://0.0.0.0:8000/`.

```bash
cd example/
php ../bin/stakx serve
```

Note that the `serve` command will only build the requested page, and not the entire site. The `serve` command is ideal for quick development and testing.

To build your site for production, use the `build` command.

## License

[MIT](./LICENSE.md)
