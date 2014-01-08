cls

@'

 _   __(_)___     ____ ___  __(_)____/ /_______/ /_____ ______/ /_
| | / / / __ \   / __ `/ / / / / ___/ //_/ ___/ __/ __ `/ ___/ __/
| |/ / / /_/ /  / /_/ / /_/ / / /__/ ,< (__  ) /_/ /_/ / /  / /_
|___/_/ .___/   \__, /\__,_/_/\___/_/|_/____/\__/\__,_/_/   \__/
     /_/          /_/

'@

# =====================================
# Change to the VIP Quickstart dir
# =====================================
$dir = Split-Path $MyInvocation.MyCommand.Path
cd $dir
cd ..

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
git submodule sync
git submodule update --init --recursive
echo ""

if ( Get-Command svn -errorAction SilentlyContinue ) {
	# =====================================
	# Checkout the VIP shared plugins repo
	# =====================================
	echo "=================================="
	echo "= Updating VIP Shared plugins"
	echo "=================================="

	if ( Test-Path "www/wp-content/themes/vip" ) {
		svn up www/wp-content/themes/vip/plugins
	} else {
		mkdir -Force www/wp-content/themes/vip
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

$command = @'
$file = Join-Path -Path $env:WINDIR -ChildPath "system32\drivers\etc\hosts";

if ( -not ( Get-Content $file | Select-String vip.dev ) ) {
	$data = Get-Content $file;
	$data += '';
	$data += '# VIP Quickstart';
	$data += '10.86.73.80 vip.dev';
	Set-Content -Value $data -Path $file -Force -Encoding ASCII;
}
'@

$exitCode = -1;

$pinfo = New-Object System.Diagnostics.ProcessStartInfo "PowerShell"
$pinfo.Verb = "runas"
$pinfo.Arguments = "-command $command"

$p = New-Object System.Diagnostics.Process
$p.StartInfo = $pinfo

try { # Start() may throw an error if the user declines the UAC prompt
	$p.Start() | Out-Null
	$p.WaitForExit()
	$exitCode = $p.ExitCode
} catch {
	# User declined UAC prompt, set exit code to prompt failed
	$exitCode = 1
}

$hostFileSuccess = $false;
if ( $exitCode -eq 0 ) {
	$hostFileSuccess = $true;
	echo "* hosts file successfully configured"
} elseif ( $exitCode -eq 1 ) {
	$file = Join-Path -Path $env:WINDIR -ChildPath "system32\drivers\etc\hosts"
	echo "* The hosts file wasn't updated because it requires admin permission"
	echo "* Please set vip.dev to 10.86.73.80 in $file or re-run this script with administrator permissions"
} elseif ( $exitCode -eq 2 ) {
	$hostFileSuccess = $true;
	echo "* No update needed for hosts file"
} else {
	echo "* Unknown error updating hosts file"
}



echo ""

# =====================================
# Outro/next steps
# =====================================
echo "=================================="
echo "= Next Steps"
echo "=================================="

if ( $hostFileSuccess ) {
	echo "* Go to http://vip.dev in your browser"
	echo ""
	Start-Process "http://vip.dev"
} else {
	echo "* Please fix the hosts file then go to http://vip.dev in your browser"
	echo ""
}
