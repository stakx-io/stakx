# Stakx

[![Build Status](https://travis-ci.org/allejo/stakx.svg?branch=master)](https://travis-ci.org/allejo/stakx)
[![Coverage Status](https://coveralls.io/repos/github/allejo/stakx/badge.svg?branch=master)](https://coveralls.io/github/allejo/stakx?branch=master)

Stakx is a static website generator written in PHP as a powerful alternative to Jekyll or Sculpin. This project is still a work in progress and has yet to see a stable release but is mostly functional.

## Sample Usage

This project is still under heavy development and a lot of things are still changing under the hood, but building websites is functional! There are still a lot of things planned and more features to come but a sample Stakx website is provided in the `example` directory. Here's how to use Stakx to build the website.

```bash
cd example/
composer install --no-dev
php ../bin/stakx.php build
```

## License

LGPL