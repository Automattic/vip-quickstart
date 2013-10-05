# VIP Quickstart

## What You Get

*   Ubuntu 12.04
*   WordPress trunk
*   WordPress.com VIP Shared Plugins repository
*   WordPress multisite
*   WordPress Developer Plugin and all VIP recommended plugins
*   Custom WordPress.com modifications
*   WP-CLI
*   MySQL
*   PHP
*   Nginx

## Requirements

* [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
* [Vagrant](http://downloads.vagrantup.com/)
* [Git](http://git-scm.com/downloads)
* [Subversion](http://subversion.apache.org/packages.html)

## Getting Started

### Unix

If you’re on a Unix-based machine with a Bash shell, setup is pretty easy:

1.  Clone the [VIP Quickstart Github repo](https://github.com/Automattic/vip-quickstart)
2.  Run the VIP init script: `./bin/vip-init`
3.  Go to http://vip.dev in your browser, login with username: wordpress, password: wordpress

The init script is setup such that you can run it multiple times and nothing will break. This means that you can also use it to update your environment in the future. If parts of the system are already up-to-date it will just skip those parts of the installer. So if you manually keep WordPress trunk up-to-date by running `svn up`, the init script will just show a message that the WordPress install is already at the latest changeset.

### Windows

If you’re on a Windows machine, the setup is a little more complicated for the moment. Since the VIP init script won’t work in that environment, you’ll have to complete the following tasks manually:

1.  Clone the [VIP Quickstart Github repo](https://github.com/Automattic/vip-quickstart)
2.  Set up the submodules with `git submodule init` and `git submodule update`
3.  Check out WordPress trunk (http://core.svn.wordpress.org/trunk/) to `www/wp`
4.  Check out a copy of the Shared Plugins Repository (https://vip-svn.wordpress.com/plugins/) to `www/wp-content/themes/vip/plugins`
5.  Start the vagrant box: `vagrant up`
6.  Add a hosts file entry for: “10.86.73.80 vip.dev”
7.  Go to http://vip.dev in your browser

Note: The first time you run the init script will be the slowest. It’s also the most dependent on the speed of your internet connection. This is because it has to download the virtual machine image, Ubuntu package updates, the full checkout of WordPress trunk, and the full VIP Plugins repository. Subsequent runs will only update this base.

## Usernames and Passwords

### MySQL
* root:blank
* wordpress:wordpress

### WordPress
* wordpress:wordpress

## Submodules

The puppet modules are all set up as submodules in `puppet/modules`. In general, you shouldn't have to worry about this because the `vip-init` script will update the submodules automatically. Just noting here for documentation purposes at this point.

## Acknowledgements

Thanks to the following projects that VIP Quickstart is built on:

* [Vagrant](http://vagrantup.com/)
* [Puppet](http://puppetlabs.com/)
* [Varying Vagrant Vagrants](https://github.com/10up/varying-vagrant-vagrants)
* [WP-CLI](http://wp-cli.org)
* [puppet-mysql](https://github.com/example42/puppet-mysql)
* [puppet-nginx](https://github.com/example42/puppet-nginx)
* [puppet-php](https://github.com/jippi/puppet-php)
* [puppi](https://github.com/example42/puppi)
* [puppet-wp](https://github.com/rmccue/puppet-wp)
