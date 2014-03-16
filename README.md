# VIP Quickstart

## Overview

VIP Quickstart is a local development environment for WordPress.com VIP developers. The goal is to provide developers with an environment that closely mirrors WordPress.com along with all the tools we recommend developers use.

## What You Get

*   Ubuntu 12.04
*   WordPress trunk
*   WordPress.com VIP Shared Plugins repository
*   WordPress multisite
*   WordPress Developer Plugin and all VIP recommended plugins
*   WordPress unit tests
*   Custom WordPress.com modifications
*   WP-CLI
*   MySQL
*   PHP
*   Nginx
*   PHPUnit

## Requirements

### Local

* [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
* [Vagrant](http://www.vagrantup.com/downloads.html)
* [Git](http://git-scm.com/downloads)

### Server

* Ubuntu 12.04
* Git
* Puppet

## Getting Started

The first time you run the installer will be the slowest. It’s also the most dependent on the speed of your internet connection. This is because it has to download the virtual machine image, Ubuntu package updates, the full checkout of WordPress trunk, and the full VIP Plugins repository. Subsequent runs will only update this base.

If Subversion is installed to your local PATH, the init script (no matter what operating system you're using) will use that. If not, no worries; we'll just offload SVN to the VM.

### Unix

If you’re on a Unix-based machine with a Bash shell, setup is pretty easy:

1.  Clone the [VIP Quickstart Github repo](https://github.com/Automattic/vip-quickstart)
2.  Run the VIP init script: `./bin/vip-init`
3.  Go to http://vip.dev in your browser, login with username: wordpress, password: wordpress

The init script is setup such that you can run it multiple times and nothing will break. This means that you can also use it to update your environment in the future. If parts of the system are already up-to-date it will just skip those parts of the installer. So if you manually keep WordPress trunk up-to-date by running `svn up`, the init script will just show a message that the WordPress install is already at the latest changeset.

### Windows

After installing all the requirements, complete the following steps to install VIP Quickstart.

Note: When you run the Git installer, make sure to install Git to your system PATH as the VIP Quickstart installer requires it.

1.  Clone the [VIP Quickstart Github repo](https://github.com/Automattic/vip-quickstart)
2.  Run the `vip-init.bat` file in `wbin`
3.  Go to http://vip.dev in your browser

If you receive a File cannot be loaded because the execution of scripts is disabled on this system error. Make sure you're using a PowerShell interface. Use tools -> options to manage your default shell. (Right click on the repository and choose "Open a shell here")

### Server

1. Add user with SSH key
2. Install Puppet and Git
3. Clone Quickstart into `/srv`
4. /srv/bin/vip-init --server [--domain=<domain>]

Since we turn off root logins and password logins via SSH, you'll need to create another use and add an SSH key so you don't get locked out of your server. `ssh-copy-id` is useful for copying ssh keys on Linux. There are similar tools for other platforms.

This has been tested with Ubuntu 12.04.

## Usernames and Passwords

### MySQL
* root:(blank)
* wordpress:wordpress

### WordPress
* wordpress:wordpress

## Vagrant Primer

The [Vagrant CLI documentation](http://docs.vagrantup.com/v2/cli/index.html) will be useful for developers that haven't used Vagrant before. Since VIP Quickstart is built on top of Vagrant, the Vagrant CLI commands will also work.

Some useful commands:

* `vagrant up` - Start and provisions the VM
* `vagrant halt` - Stops the VM
* `vagrant reload` - Restarts and provisions the VM
* `vagrant provision` - Provisions the VM
* `vagrant ssh` - Logs into the VM with ssh
* `vagrant destroy` - Deletes the VM

## Unit Testing

VIP Quickstart comes with a checkout of the [WordPress-Tests automated testing framework](http://make.wordpress.org/core/handbook/automated-testing/). You can use this to run the unit tests for WordPress itself or for any plugin or theme that has phpunit tests defined.

#### To run the WordPress unit tests

1. CD to `/srv/www/wp-tests` from within the VM.
2. Run `phpunit`

#### To create unit tests for your plugin/theme

1. Navigate to your theme or plugin within the VM. (eg. `/srv/www/wp-content/plugins/my-plugin`)
2. Use WP CLI to the generate the plugin test files. Eg. `/srv/www/wp-cli/bin/wp scaffold plugin-tests my-plugin`
3. Run `phpunit` inside your theme or plugin directory.
4. Start building your tests within the newly created `tests` directory.

## Customize

If you want to add custom packages, install custom dotfiles, or make any other customizations, there are a few methods.

First, you can add a [Vagrantfile](http://docs.vagrantup.com/v2/vagrantfile/index.html) to the `~/.vagrant.d` directory. Vagrant loads this before the Vagrantfile for the project so you can do things like install text editors or other tools, sync local directories to the VM, or anything else you might want to do to customize the environment. You can even add a [shell provisioner](http://docs.vagrantup.com/v2/provisioning/shell.html) to run your own shell script. One of the great parts about this method is that it will run on every Vagrant machine you used so all of your projects can benefit from it.

Another method is to add new manifests to `puppet/manifests/sections` and they'll be automatically loaded. You'll probably want to add them to your `.gitignore` file so they don't interfere with other git operations.

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

If you're not developing for WordPress.com VIP, you might want to check out these other Vagrant/WordPress projects

* [VVV](https://github.com/Varying-Vagrant-Vagrants/VVV)
* [Salty WordPress](https://github.com/humanmade/Salty-WordPress)
* [Vagrant Genesis](https://github.com/genesis/wordpress/)
* [VagrantPress](https://github.com/chad-thompson/vagrantpress)
