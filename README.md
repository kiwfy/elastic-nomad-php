# PHP Elastic Nomad

[![Latest Version](https://img.shields.io/github/v/release/kiwfy/elastic-nomad-php.svg?style=flat-square)](https://github.com/kiwfy/elastic-nomad-php/releases)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square&label=PRs%20Welcome)](http://makeapullrequest.com)

PHP script to execute Elasticsearch backups and restorations using S3.

### Installation

Requires [PHP](https://php.net) 8.1

### Sample

it's a good idea to look in the sample folder to understand how it works.

First you need to building a correct environment to install dependences

```sh
docker build -t kiwfy/elastic-nomad-php -f contrib/Dockerfile .
```

Access the container
```sh
docker run -v ${PWD}/:/var/www/html -it kiwfy/elastic-nomad-php bash
```

Verify if all dependencies is installed (if need anyelse)
```sh
composer install --no-dev --prefer-dist
```

and run
```sh
php index.php
```

### Development

Want to contribute? Great!

The project using a simple code.
Make a change in your file and be careful with your updates!
**Any new code will only be accepted with all viladations.**

To ensure that the entire project is fine:

First you need to building a correct environment to install/update all dependences
```sh
docker build -t kiwfy/elastic-nomad-php -f contrib/Dockerfile .
```

Access the container
```sh
docker run -v ${PWD}/:/var/www/html -it kiwfy/elastic-nomad-php bash
```

Install all dependences
```sh
composer install --dev --prefer-dist
```

Run all validations
```sh
composer check
```

**Kiwfy - Open your code, open your mind!**
