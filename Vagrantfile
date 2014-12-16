# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

#Vagrant.require_version ">= 1.5.0"
if `vagrant --version` < 'Vagrant 1.5.0'
    abort('Your Vagrant is too old. Please install at least 1.5.0')
end


if File.exists?('Quickstart.yaml') then
	require 'yaml'
	local_config = YAML::load_file('Quickstart.yaml')
	if defined?local_config['domain']
		QUICKSTART_DOMAIN = local_config['domain']
	end
	if defined?local_config['memory']
		QUICKSTART_VM_MEMORY = local_config['memory']
	end
end

unless defined?QUICKSTART_DOMAIN
	QUICKSTART_DOMAIN='vip.local'
end

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "precise32"
  config.vm.box_url = "http://files.vagrantup.com/precise32.box"
  config.vm.provider "vmware_fusion" do |v, override|
    override.vm.box = "precise64-vmware"
    override.vm.box_url = "http://files.vagrantup.com/precise64_vmware.box"
  end

  config.vm.hostname = QUICKSTART_DOMAIN
  config.vm.network :private_network, ip: "10.86.73.80"

  if defined?QUICKSTART_VM_MEMORY
    config.vm.provider "virtualbox" do |v|
      v.memory = QUICKSTART_VM_MEMORY
    end
	config.vm.provider "vmware_fusion" do |v|
	  v.memory = QUICKSTART_VM_MEMORY
	end
  else
    # Use 1GB of memory in virtualbox
    config.vm.provider "virtualbox" do |v|
      v.memory = 1024
    end

    # Use 1GB of memory in vmware_fusion
    config.vm.provider "vmware_fusion" do |v|
      v.memory = 1024
    end
  end
    
  config.vm.synced_folder ".", "/srv"

  # Address a bug in an older version of Puppet
  # See http://stackoverflow.com/questions/10894661/augeas-support-on-my-vagrant-machine
  config.vm.provision :shell, :inline => "if ! dpkg -s puppet > /dev/null; then sudo apt-get update --quiet --yes && sudo apt-get install puppet --quiet --yes; fi"

  # Provision everything we need with Puppet
  config.vm.provision :puppet do |puppet|
    puppet.module_path = "puppet/modules"
    puppet.manifests_path = "puppet/manifests"
    puppet.manifest_file  = "init.pp"
    puppet.options = ['--templatedir', '/vagrant/puppet/files']
    puppet.facter = {
      "quickstart_domain" => QUICKSTART_DOMAIN,
    }
  end

end
