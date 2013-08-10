# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

  # Default Ubuntu Box
  #
  # This box is provided by Vagrant at vagrantup.com and is a nicely sized (290MB)
  # box containing the Unbuntu 12.0.4 Precise 32 bit release.
  config.vm.box = "std-precise32"
  config.vm.box_url = "http://files.vagrantup.com/precise32.box"

  config.vm.hostname = "precise32-dev"
  config.vm.network :private_network, ip: "10.86.73.80"

  # Address a bug in an older version of Puppet
  #
  # Once precise32 ships with Puppet 2.7.20+, we can safely remove
  # See http://stackoverflow.com/questions/10894661/augeas-support-on-my-vagrant-machine
  config.vm.provision :shell, :inline => "if dpkg --compare-versions `puppet --version` 'lt' '2.7.20'; then sudo apt-get update --quiet --yes && sudo apt-get install puppet --yes; fi"

  # Automatically set up submodules
  config.vm.provision :shell, :inline => "cd /vagrant; sudo apt-get install git-core --quiet --yes && git submodule init && git submodule update"

  # Provision everything we need with Puppet
  config.vm.provision :puppet do |puppet|
    puppet.module_path = "puppet/modules"
    puppet.manifests_path = "puppet/manifests"
    puppet.manifest_file  = "init.pp"
    puppet.options = ['--templatedir', '/vagrant/puppet/files']
  end

end
