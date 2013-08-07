#VIP Quickstart

##Setup

1. Clone the repository
2. `vagrant up`
3. Add "192.168.50.4 wp.dev" to your hosts file
4. Build awesome stuff

##Scripts

There are some scripts in the bin directory. If you add `./bin` to your system path you can use these scripts by name. Otherwise, `./bin/wp-init` will run the init script, for example.

* `aliases.bash` is a set of bash aliases to help you interact with the virtual machine. Notably, the `wp` command tunnels a wp-cli command through the `vagrant ssh -c` command to let you run wp-cli commands quickly without the need to log into the machine
* `wp-init` sets up the virtual machine and checks out the VIP shared plugins repo. It will ask for your WordPress.com login information to checkout the shared plugins
* `wp-pc` is a set of pre-commit checks to make sure your theme is able to be committed to SVN.

##Submodules

The puppet modules are all set up as submodules in `puppet/modules`. In general, you shouldn't have to worry about this because the Vagrantfile will update the submodules with a bash script when you provision. Just noting here for documentation purposes at this point.
