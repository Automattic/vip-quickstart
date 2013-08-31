# VIP Quickstart

## Setup

1. Download and install the latest version of [VirtualBox](https://www.virtualbox.org/wiki/Downloads) and [Vagrant](http://downloads.vagrantup.com/)
2. Clone this repository
3. Navigate to the repo in a terminal
4. Run `./bin/vip-init`
5. Go to http://vip.dev in your browser, login with username: wordpress, password: wordpress

If you're not able to run a bash script, there are a couple extra steps to complete everything that the `vip-init` script does.

1. `git submodule init && git submodule update`
2. `vagrant up`
3. `svn co https://vip-svn.wordpress.com/plugins/ www/wp-content/themes/vip/plugins`
4. Add "10.86.73.80 vip.dev" to your hosts file

## Tips

* There are scripts in the bin directory, run them with `./bin/<script-name>` or add "./bin" to your system PATH
* You can use `vagrant up` to start the virtual machine instead of `vip-init`, but you'll have to manually clone the submodules with `git submodule init && git submodule update`
* You can use the `wp` script in the bin directory to run simple WP-CLI scripts without needing to SSH into the virtual machine
* Run `vagrant help` to see a list of commands

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
