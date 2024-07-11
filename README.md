![](https://cdn.daminik.com/dam-2024-91b1f1b/daminik/og-i/og-image.png)

# Daminik

Daminik is a simple & scalable Digital Asset Manager with a build in Content Delivery Network. The single source of truth for all your assets.

## Requirements

* PHP 8.2 or higher with ctype, exif, gd and iconv
* Node 18 or higher to build the frontend assets
* [ImageMagick](https://imagemagick.org/) with PNG, JPG, WebP and GIF
* MySQL 8
* [Composer](https://getcomposer.org/)
* A server with full CLI access and cronjobs
* A mail account for service mails
* A way to run background workers
* A domain with wildcard subdomains
* S3-compatible storage

## Installation

This is just a basic way of installing and deploying Daminik. If you have experience
deploying Symfony projects you'll be able to adapt these to your needs.

```shell
git clone https://github.com/daminikhq/daminik.git
cd daminik
composer install
```

Now create a `.env.local` in the `app` directory and add the configuration for
your installation, overwriting the values in `.env` - the most common ones will be:

`DATABASE_URL` - follow the format of the setting in the `.env` file. For more details,
have a look [at the Symfony docs](https://symfony.com/doc/current/doctrine.html#configuring-the-database).
(Note: only MySQL is supported and tested, MariaDB might work.)

`MAILER_DSN` - follow the format of the setting in the `.env` file. For more details,
have a look [at the Symfony docs](https://symfony.com/doc/current/mailer.html#transport-setup).
(Note: only SMTP and Mailjet have been tested.)

`EMAIL_SENDER` - this is the sender address for all transactional email. This address
will also be mentioned as support address on error pages and at some UI elements. Make
sure that the transport you use in `MAILER_DSN` is allowed to send mails from this address.

`DEFAULT_FILESYSTEM` - set this to `s3` - the alternative would be `local` in which case
all assets will just be put on the same server as the code. This is strictly meant for
local development environments. (Hence the name.)

`DEFAULT_S3_KEY`, `DEFAULT_S3_SECRET`, `DEFAULT_S3_ENDPOINT` and `DEFAULT_S3_REGION` are
the config settings for your S3-compatible storage.

`DEFAULT_S3_TYPE` - this has two settings for now: `single` and `year`
If you pick `single` it will put all assets into one bucket, which you have to set
with `DEFAULT_S3_BUCKET`
If you pick `year` your S3 account must have the capability of programmatically create
new buckets. It will create a new bucket for each year. You need to
set `DEFAULT_S3_BUCKET_PREFIX`

`REGISTRATION_SECRET` - a code someone has to enter to register on your installation

`DEFAULT_URI`, `DOMAIN` and `TLD` of your domain. Yes, all three and the `DEFAULT_URI`
has to have the full protocol and no trailing slash.

Once everything is set, you can finish the installation with these commands:

Database migration

```shell
bin/console doctrine:migrations:migrate --no-interaction
```

Frontend build

```shell
yarn install
yarn encore production
```

Now point your domain and the wildcard subdomains to the `app/public` directory and
you should be all set. Well, almost - you need to make sure the worker for the background
processes is running. [Have a look at the Symfony docs](https://symfony.com/doc/current/messenger.html#consuming-messages-running-the-worker)
to find more details and a sample configuration for Supervisor.

It's a good idea to run `bin/console app:clean-deleted-assets` once per day - it will clean
out soft-deleted assets after 30 days.
