# Hacking

Here's an introduction to developing VIP Quickstart. If you have any additional questions, feel free to open an issue so we can write the proper documentation.

## Testing

At this point we only only test that the syntax is correct with puppet-lint.

## Structure

* bin - Scripts
* logs - Reserved for a shared directory with the VM that gets mapped to `/var/log`
* puppet - All Puppet manifests, modules, and template
* wbin - Short for Windows bin. Windows ports of the scripts necessary for VIP Quickstart.
* www - The web root, this is where all WordPress files live.
    * config - Configuration files
    * wp - SVN checkout of http://core.svn.wordpress.org/trunk/
    * wp-cli - The latest stable version of WP-CLI
    * wp-content - Just like the normal wp-content directory, this is where all themes and plugins live
        * themes/vip - The VIP themes directory
        * themes/vip/plugins - The VIP shared plugins directory
    * local-config.php - A local WordPress configuration file that's not under version control
    * wp-cli.yml - The WP-CLI config
    * wp-tests - SVN checkout of WordPress-Tests from http://develop.svn.wordpress.org/trunk/

## Bin scripts

Any changes to the vip-init script needs to be mirrored to the wbin versions. Generally, we're trying to keep the init script as minimal as possible. If everything could be done in Puppet, that would be ideal.

## Puppet

Manifests that live in `puppet/manifests/sections` get loaded automatically. If you're working on something that doesn't fit into any of the current sections, create a new one there.

When adding a module, add it as a git submodule in `puppet/modules`. Actively developed repositories are prefered to stale ones.

## WordPress

Plugins can be added in Puppet by adding it to the list in `puppet/manifests/sections/wp.pp`
