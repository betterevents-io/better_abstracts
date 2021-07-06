# Better Abstracts installation profile

An installation profile for an [Abstracts Review](https://betterevents.io/abstracts-review) system suitable for scientific conferences.

Provides an import process for Abstracts and Reviewers, an automated abstract assignment to reviewers, multiple evaluation criteria mechanism, review result extraction, notifications and more...

## Usage
We suggest to install this profile as part of the [Better Abstracts distribution](https://github.com/betterevents/better-abstracts-project) using Composer.

However, if you want to install it as part of any Drupal project, please follow the instructions below.

First you need to [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos).

After that you can create the Drupal project:

```
composer create-project drupal/recommended-project my_site_name_dir --stability dev
```

Open the `composer.json` file and make sure the following properties are set as shown:
```
"minimum-stability": "dev",
"prefer-stable": true,
```

Run `composer require "betterevents/better_abstracts:^v1.0.5-alpha"` to download all the modules, themes and configuration that come with the installation profile:

```
cd my_site_name_dir
composer require "betterevents/better_abstracts:^v1.0.5-alpha"
```

Done! Visit `/web/core/install.php` and run the installation of your Abstracts Review site.

## What does the installation profile do?

* Drupal is installed in the `web` directory.
* Profile is placed in `web/profiles/contrib/`
* Modules that come with the installation profile are placed in `web/profiles/contrib/better_abstracts/modules/`
* Theme is placed in `web/profiles/contrib/better_abstracts/themes/`
