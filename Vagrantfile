# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "precise32"
  config.vm.box_url = "http://files.vagrantup.com/precise32.box"
  config.vm.hostname = "vip.dev"
  config.vm.network :private_network, ip: "10.86.73.80"

  # Map log directory
  config.vm.synced_folder "logs", "/var/log", owner: "vagrant"

  # Map MySQL to local port 3306
  config.vm.network :forwarded_port, guest: 3306, host: 3306

  # Address a bug in an older version of Puppet
  # See http://stackoverflow.com/questions/10894661/augeas-support-on-my-vagrant-machine
  config.vm.provision :shell, :inline => "if ! dpkg -s puppet > /dev/null; then sudo apt-get update --quiet --yes && sudo apt-get install puppet --quiet --yes; fi"

  # Do some local stuff if we're provisioning
  if ( "#{ARGV.first}" == "provision" )
    # SSH and checkout shared plugins
    if File.exists?('www/wp-content/themes/vip/plugins')
      system "ssh vagrant@vip.dev -i ~/.vagrant.d/insecure_private_key 'cd /vagrant/www/wp-content/themes/vip/plugins/; svn up'"
    else
      system( "ssh vagrant@vip.dev -i ~/.vagrant.d/insecure_private_key 'svn co https://vip-svn.wordpress.com/plugins/ /vagrant/www/wp-content/themes/vip/plugins'" )
    end
  end

  # Provision everything we need with Puppet
  config.vm.provision :puppet do |puppet|
    puppet.module_path = "puppet/modules"
    puppet.manifests_path = "puppet/manifests"
    puppet.manifest_file  = "init.pp"
    puppet.options = ['--templatedir', '/vagrant/puppet/files']
  end

end
