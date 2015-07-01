# php-github-deploy
An interface to deploy repository files to a web server using GitHub Webhook requests.

## Server Requirements

These are the currently tested and verified server types that this deployment interface can run on.

* [Nginx](http://nginx.org/)

## Nginx - Setup

Create a `.ssh` directory in `/var/www/` to hold ssh keys that the `www-data` user will use.
* `sudo mkdir /var/www/.ssh`

Create a new ssh key for the `www-data` user. Remember to add this new ssh key as a [deploy key](https://developer.github.com/guides/managing-deploy-keys/) in your repository.
* `sudo -u www-data ssh-keygen -t rsa`

Grant privileges to the `www-data` user to read and write to both of these directories.
* `sudo chown -R www-data:www-data /var/www`
* `sudo chown -R www-data:www-data /tmp`

Trigger the first time authentication message and verify you are able to pull as the `www-data` user.
* `sudo -u www-data git clone git@github.com:yourusername/yourrepository.git`

Make sure you add the new SSH key as a [deploy key](https://developer.github.com/guides/managing-deploy-keys/) in the repository that you want to be deployed.
