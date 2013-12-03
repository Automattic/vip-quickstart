$script_path = Split-Path -Parent -Path $MyInvocation.MyCommand.Definition
$root_path = Split-Path -Parent -Path $script_path
$vip_theme_path = $root_path + "\www\wp-content\themes\vip"
$git_repo = "" 
$has_git = (Get-Command git -errorAction SilentlyContinue)

echo "==================================="
echo "= Setting up PMC Themes source code"
echo "==================================="

if ( -Not ($has_git) ) {
	echo "Please install git (if already install, update PATH environment to include path to git\bin folder)"
	exit
}

if ( -NOT ( Test-Path $vip_theme_path ) ) {
	mkdir -Force $vip_theme_path
}

foreach ( $theme in @( 'bgr', 'pmc-deadline', 'pmc-plugins', 'pmc-variety', 'pmc-tvline', 'pmc-hollywoodlife', 'pmc-awardsline', 'pmc-411', 'pmc-movieline', 'pmc-tvline-mobile', 'pmc-movieline-mobile' ) ) {
	if ( -NOT ( Test-Path ( $vip_theme_path + "\" + $theme  ) ) ) {
		echo ("Cloning theme folder: " + $theme)
		mkdir -Force ( $vip_theme_path + "\" + $theme )
		git clone  ("git@bitbucket.org:penskemediacorp/" + $theme + ".git") ($vip_theme_path + "\" + $theme)
	} else {
		echo ("Theme folder already exist: " + $theme)
	}
}
echo ""
