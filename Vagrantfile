# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

#Vagrant.require_version ">= 1.5.0"
if `vagrant --version` < 'Vagrant 1.5.0'
    abort('Your Vagrant is too old. Please install at least 1.5.0')
end

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "precise32"
  config.vm.box_url = "http://files.vagrantup.com/precise32.box"
  config.vm.hostname = 'vip.local'
  config.vm.network :private_network, ip: "10.86.73.80"

  # Virtualbox overrides
  config.vm.provider "virtualbox" do |v|
    # Use 1GB of memory
    v.memory = 1024
  end

  # VMWare Fusion overrides
  config.vm.provider "vmware_fusion" do |v|
    # Use 1GB of memory in vmware_fusion
    v.memory = 1024

    v.vm.box = "precise64-vmware"
    v.vm.box_url = "http://files.vagrantup.com/precise64_vmware.box"
  end

  config.vm.synced_folder ".", "/srv", owner: 'www-data', group: 'www-data', mount_options: ["dmode=775", "fmode=664"]

  # Address a bug in an older version of Puppet
  # See http://stackoverflow.com/questions/10894661/augeas-support-on-my-vagrant-machine
  config.vm.provision :shell, :inline => "if ! dpkg -s puppet > /dev/null; then sudo apt-get update --quiet --yes && sudo apt-get install puppet --quiet --yes; fi"
  config.vm.provision :shell, :inline => "sudo apt-get update --quiet --yes && sudo apt-get install git --quiet --yes; cd /srv; git submodule update --init --recursive"

  # Provision everything we need with Puppet
  config.vm.provision :puppet do |puppet|
    puppet.module_path = "puppet/modules"
    puppet.manifests_path = "puppet/manifests"
    puppet.manifest_file  = "init.pp"
    puppet.options = ['--templatedir', '/srv/puppet/files']
    puppet.facter = {
      "quickstart_domain" => 'vip.local',
    }
  end

end
