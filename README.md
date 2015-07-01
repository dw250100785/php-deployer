# php-github-deploy
An interface to deploy repository files to a web server using GitHub Webhook requests.

## Server Requirements

These are the currently tested and verified server types that this deployment interface can run on.

* [Nginx](http://nginx.org/)

## Nginx - Setup

Run these commands as the root user in your web server.

* `sudo mkdir /var/www/.ssh`

* `sudo -u www-data ssh-keygen -t rsa`

* `sudo chown -R www-data:www-data /var/www`

* `sudo chown -R www-data:www-data /tmp`

Make sure you add the new SSH key as a [deploy key](https://developer.github.com/guides/managing-deploy-keys/) in the repository that you want to be deployed.
