# Stakx

[![Build Status](https://travis-ci.org/stakx-io/stakx.svg?branch=master)](https://travis-ci.org/stakx-io/stakx)
[![Coverage Status](https://coveralls.io/repos/github/stakx-io/stakx/badge.svg?branch=master)](https://coveralls.io/github/stakx-io/stakx?branch=master)

Stakx is a static website generator written in PHP as a powerful alternative to Jekyll or Sculpin. This project is still a work in progress and has yet to see a stable release but is mostly functional.

## Sample Usage

This project is still under heavy development and a lot of things are still changing under the hood, but building websites is functional! There are still a lot of things planned and more features to come but a sample Stakx website is provided in the `example` directory. Here's how to use Stakx to build the website.

```bash
git clone https://github.com/stakx-io/stakx.git
composer install --no-dev
php ../bin/stakx build
cd example/
```

## License

LGPL