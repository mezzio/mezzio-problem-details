# Problem Details for PSR-7 Applications

[![Build Status](https://travis-ci.com/mezzio/mezzio-problem-details.svg?branch=master)](https://travis-ci.com/mezzio/mezzio-problem-details)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-problem-details/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-problem-details?branch=master)

This library provides a factory for generating Problem Details
responses, error handling middleware for automatically generating Problem
Details responses from errors and exceptions, and custom exception types for
[PSR-7](http://www.php-fig.org/psr/psr-7/) applications.

## Installation

Run the following to install this library:

```bash
$ composer require mezzio/mezzio-problem-details
```

## Documentation

Documentation is [in the doc tree](docs/book/), and can be compiled using [mkdocs](https://www.mkdocs.org):

```bash
$ mkdocs build
```

You may also [browse the documentation online](https://docs.mezzio.dev/mezzio-problem-details/).
