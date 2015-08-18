# Add following to aws startup script
sudo apt-get update
sudo apt-get upgrade -y
sudo apt-get install -y git puppet unzip tar gzip
sudo git clone https://github.com/Penske-Media-Corp/vip-quickstart.git /srv/
sudo /srv/bin/vip-init --server --domain=qa.pmc.com
sudo /srv/pmc/setup-server.sh
