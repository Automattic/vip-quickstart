# =====================================
# Check for requirements
# =====================================
if ( (-Not (Get-Command git -errorAction SilentlyContinue)) -or (-Not (Get-Command vagrant -errorAction SilentlyContinue)) -or (-Not (Get-WmiObject -Class Win32_Product | Select-Object -Property Name | Select-string "VirtualBox")) ) {
	echo "Please install requirements"
	echo "* Git"
	echo "* Vagrant"
	echo "* VirtualBox"
	exit
}


# =====================================
# Automatically update the repo
# =====================================
echo "=================================="
echo "= Updating VIP Quickstart"
echo "=================================="

git pull
git submodule update --init --recursive
echo ""

if ( Get-Command svn -errorAction SilentlyContinue ) {
	# =====================================
	# Checking out latest WordPress
	# =====================================
	echo "=================================="
	echo "= Updating WordPress"
	echo "=================================="

	if ( Test-Path "www/wp" ) {
		svn up www/wp
	} else {
		mkdir -p www/wp
		svn co http://core.svn.wordpress.org/trunk/ www/wp
	}
	echo ""


	# =====================================
	# Checkout the VIP shared plugins repo
	# =====================================
	echo "=================================="
	echo "= Updating VIP Shared plugins"
	echo "=================================="

	if ( Test-Path "www/wp-content/themes/vip" ) {
		svn up www/wp-content/themes/vip/plugins
	} else {
		mkdir -p www/wp-content/themes/vip
		svn co https://vip-svn.wordpress.com/plugins/ www/wp-content/themes/vip/plugins
	}
	echo ""
} else {
	$username = Read-Host 'Enter your WordPress.com username'
	$password = Read-Host 'Enter your WordPress.com password' -AsSecureString

	$env:SVN_USERNAME = $username
	$env:SVN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($password));

	echo ""
}


# =====================================
# Start the VM (always provision, even if it's already running)
# =====================================
echo "=================================="
echo "= Provisioning the VM"
echo "=================================="

vagrant up --no-provision
vagrant provision
echo ""


# =====================================
# Add vip.dev entry to hosts file
# =====================================
echo "=================================="
echo "= Configuring the hosts file"
echo "=================================="

$file = Join-Path -Path $env:WINDIR -ChildPath "system32\drivers\etc\hosts"

if ( -not ( Get-Content $file | Select-String vip.dev ) ) {
	$data = Get-Content $file
	$data += ""
	$data += "# VIP Quickstart"
	$data += "10.86.73.80 vip.dev"
	Set-Content -Value $data -Path $file -Force -Encoding ASCII
}
echo ""


# =====================================
# Outro/next steps
# =====================================
echo "=================================="
echo "= Next Steps"
echo "=================================="

echo "* Go to http://vip.dev in your browser"
echo ""
