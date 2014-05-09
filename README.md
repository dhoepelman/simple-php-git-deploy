# PHP Git deploy script
_Automatically deploy the code using PHP and Git._

Can deploy through console or webbrowser call, or automatically when you push to your git repository.

## Requirements

* `git` and `rsync` are required on the server that's running the script
  (_server machine_).
  - Optionally, `tar` is required for backup functionality (`BACKUP_DIR` option).
  - Optionally, `composer` is required for composer functionality (`USE_COMPOSER`
  option).
* The system user running PHP (e.g. `www-data`) needs to have the necessary
  access permissions for the `TMP_DIR` and `TARGET_DIR` locations on
  the _server machine_.
* If the Git repo you wish to deploy is private, the system user running PHP
  also needs to have the right SSH keys to access the remote repository. See [the Github deploy keys help page](https://help.github.com/articles/managing-deploy-keys)

## Usage

 * Usually you'll want to put the script somewhere that's accessible from the
   Internet. You can either `git clone git@github.com:sintjansbrug/php-git-deploy.git`
   or download one of the releases.
 * Rename `deploy-config.example.php` to `deploy-config.php` and edit the
   configuration options there to suit your needs.
 * Configure your git repository to call this script when the code is updated.
   The instructions for GitHub and Bitbucket are below.

### GitHub

 1. _(This step is only needed for private repositories)_ Give your server access to your repository. You'll usually want to do this with a [https://help.github.com/articles/managing-deploy-keys#deploy-keys](deploy key).
 1. Go to `https://github.com/USERNAME/REPOSITORY/settings/hooks`.
 1. Click **Add webhook** in the **Webhooks** panel.
 1. Enter the **Payload URL** for your deployment script e.g. `http://example.com/deploy.php?sat=YourSecretAccessTokenFromDeployFile`.
 1. _Optional_ Choose which events should trigger the deployment.
 1. Make sure that the **Active** checkbox is checked.
 1. Click **Add webhook**.

### Bitbucket

 1. _(This step is only needed for private repositories)_ Go to
    `https://bitbucket.org/USERNAME/REPOSITORY/admin/deploy-keys` and add your
    server SSH key.
 1. Go to `https://bitbucket.org/USERNAME/REPOSITORY/admin/services`.
 1. Add **POST** service.
 1. Enter the URL to your deployment script e.g. `http://example.com/deploy.php?sat=YourSecretAccessTokenFromDeployFile`.
 1. Click **Save**.

### Generic Git

 1. Configure the SSH keys.
 1. Add a executable `.git/hooks/post_receive` script that calls the script e.g.

```sh
#!/bin/sh
echo "Triggering the code deployment ..."
php /path/to/deploy.php
```

## Done!

Next time you push the code to the repository that has a hook enabled, it's
going to trigger the `deploy.php` script which is going to pull the changes and
update the code on the _server machine_.

For more info, read the source of `deploy.php`.

## Tips'n'Tricks

 * Because `rsync` is used for deployment, the `TARGET_DIR` doesn't have to be
   on the same server that the script is running e.g. `define('TARGET_DIR',
   'username@example.com:/full/path/to/target_dir/');` is going to work as long
   as the user has the right SSH keys and access permissions.
 * You can deploy multiple branches with the same script even based on regular expressions! Take a look at the `$DEPLOYMENTS` configuration option.
   If you want to deploy from different repositories, you can copy `deploy.php` to something else, for example `deploy_site2.php`. In that case, the configuration files needs to be named `deploy_site2-config.php`

---

_Forked from [markomarkovic/simple-git-deploy](https://github.com/markomarkovic/simple-php-git-deploy)
_Inspired by [a Gist by oodavid](https://gist.github.com/1809044)_
