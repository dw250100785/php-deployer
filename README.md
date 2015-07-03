# php-github-deploy
An interface to deploy repository files to a web server using GitHub Webhook requests.

## Requirements

You will need to have these programs installed on your web server.

* [Git](https://git-scm.com/)
* [Rsync](https://rsync.samba.org/)

## Setup

Add a `deploy` folder containing your `deploy.php` file and the `php-github-deploy.php` file into your web server and repository to start out. You need to add these same files to both your web server and repository because any conflicting files in the web server will be deleted upon a successful deployment.

Add a [Webhook](https://developer.github.com/webhooks/) in your repository to send data to the interface so that it can deploy any changes in the repository to your web server.

In the payload url field, enter in something like this. `http://www.yourwebsite.com/deploy/deploy.php`.

If you want to add a secret access token to prevent unwanted outside requests to your interface, which is recommended, enter a strong password in the secret field that you will use as your secret access token.

You will need to find out which user the interface will be using, this can be `www-data`, `apache`, or something else depending on what web server you are using. You can run this command in a php file on your web server to show which user is being used. For now, we will assume that the `www-data` user is being used.
```php
<?php echo exec('whoami'); ?>
```

Create a new ssh key for the `www-data` user. Remember to add this new ssh key as a [deploy key](https://developer.github.com/guides/managing-deploy-keys/) in your repository.
```
sudo -u www-data ssh-keygen -t rsa
```

Grant privileges to the `www-data` user to read and write to both of these directories.
```
sudo chown -R www-data:www-data /var/www/
sudo chown -R www-data:www-data /tmp/
```

Trigger the first time authentication message and verify you are able to pull as the `www-data` user.
```
sudo -u www-data git clone git@github.com:yourusername/yourrepository.git
```

## License

```
The MIT License (MIT)

Copyright (c) 2015 Zachariah T. Dzielinski

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
