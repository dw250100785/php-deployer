# php-github-deploy
An interface to deploy repository files to a web server using GitHub Webhook requests.

## Server - Requirements

These are the currently tested and verified server types that this interface can run on.

* [Nginx](http://nginx.org/)

## Repository - Setup

Add a `deploy` folder containing your `deploy.php` file and the `php-github-deploy.php` file into your web server and repository to start out. You need to add these same files to both your web server and repository because any conflicting files in the web server will be deleted upon a successful deployment.

Add a [Webhook](https://developer.github.com/webhooks/) in your repository to send data to the interface so that it can deploy any changes in the repository to your web server.

In the payload url field, enter in something like this. `http://www.yourwebsite.com/deploy/deploy.php`.

If you want to add a secret access token to prevent unwanted outside requests to your interface, which is recommended, enter a strong password in the secret field that you will use as your secret access token.

## Nginx - Setup

Create a new ssh key for the `www-data` user. Remember to add this new ssh key as a [deploy key](https://developer.github.com/guides/managing-deploy-keys/) in your repository.
* `sudo -u www-data ssh-keygen -t rsa`

Grant privileges to the `www-data` user to read and write to both of these directories.
* `sudo chown -R www-data:www-data /var/www`
* `sudo chown -R www-data:www-data /tmp`

Trigger the first time authentication message and verify you are able to pull as the `www-data` user.
* `sudo -u www-data git clone git@github.com:yourusername/yourrepository.git`
