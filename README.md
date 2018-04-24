# Chimera - routing/expressive

[![Total Downloads](https://img.shields.io/packagist/dt/lcobucci/chimera-routing-expressive.svg?style=flat-square)](https://packagist.org/packages/lcobucci/chimera-routing-expressive)
[![Latest Stable Version](https://img.shields.io/packagist/v/lcobucci/chimera-routing-expressive.svg?style=flat-square)](https://packagist.org/packages/lcobucci/chimera-routing-expressive)
[![Unstable Version](https://img.shields.io/packagist/vpre/lcobucci/chimera-routing-expressive.svg?style=flat-square)](https://packagist.org/packages/lcobucci/chimera-routing-expressive)

![Branch master](https://img.shields.io/badge/branch-master-brightgreen.svg?style=flat-square)
[![Build Status](https://img.shields.io/travis/lcobucci/chimera-routing-expressive/master.svg?style=flat-square)](http://travis-ci.org/#!/lcobucci/chimera-routing-expressive)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/lcobucci/chimera-routing-expressive/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/lcobucci/chimera-routing-expressive/?branch=master)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/lcobucci/chimera-routing-expressive/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/lcobucci/chimera-routing-expressive/?branch=master)

> The term Chimera (_/kɪˈmɪərə/_ or _/kaɪˈmɪərə/_) has come to describe any
mythical or fictional animal with parts taken from various animals, or to
describe anything composed of very disparate parts, or perceived as wildly
imaginative, implausible, or dazzling.

There are many many amazing libraries in the PHP community and with the creation
and adoption of the PSRs we don't necessarily need to rely on full stack
frameworks to create a complex and well designed software. Choosing which
components to use and plugging them together can sometimes be a little
challenging.

The goal of this set of packages is to make it easier to do that (without
compromising the quality), allowing you to focus on the behaviour of your
software.

This package just provides adapters for Zend Expressive v3.0, so that it
can be used as HTTP application. 

## Installation

Package is available on [Packagist](http://packagist.org/packages/lcobucci/chimera-routing-expressive),
you can install it using [Composer](http://getcomposer.org).

```shell
composer require lcobucci/chimera-routing-expressive
```

### PHP Configuration

In order to make sure that we're dealing with the correct data, we're using `assert()`,
which is a very interesting feature in PHP but not often used. The nice thing
about `assert()` is that we can (and should) disable it in production mode so
that we don't have useless statements.

So, for production mode, we recommend you to set `zend.assertions` to `-1` in your `php.ini`.
For development you should leave `zend.assertions` as `1` and set `assert.exception` to `1`, which
will make PHP throw an [`AssertionError`](https://secure.php.net/manual/en/class.assertionerror.php)
when things go wrong.

Check the documentation for more information: https://secure.php.net/manual/en/function.assert.php


## Usage

To use this package you need to configured your Zend Expressive application to
use our packages (as explained [here](https://github.com/lcobucci/chimera-routing#usage))
and register instances of `Lcobucci\Chimera\Routing\Expressive\UriGenerator` and
`Lcobucci\Chimera\Routing\Expressive\RouteParamsExtractor` in your DI container. 

## License

MIT, see [LICENSE file](https://github.com/lcobucci/chimera-routing-expressive/blob/master/LICENSE).
