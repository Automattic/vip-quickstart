#VIP Quickstart

##Setup

1. Download and install the latest version of Vagrant from http://downloads.vagrantup.com/
2. Clone this repository
3. Navigate to the repo in a terminal
4. Run `./bin/vip-init`
5. Add "10.86.73.80 wp.dev" to your hosts file
6. Go to http://wp.dev in your browser

##Tips

* There are scripts in the bin directory, run them with `./bin/<script-name>` or add "./bin" to your system PATH
* If you don't want to check out the VIP shared plugins repository and VIP helper code, you can just run `vagrant up` to start the virtual machine instead of using the `vip-init` script
* You can use the `wp` script in the bin directory to run simple WP-CLI scripts without needing to SSH into the virtual machine

##Submodules

The puppet modules are all set up as submodules in `puppet/modules`. In general, you shouldn't have to worry about this because the Vagrantfile will update the submodules with a bash script when you provision. Just noting here for documentation purposes at this point.
