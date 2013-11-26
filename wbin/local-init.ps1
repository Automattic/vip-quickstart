$script_path = Split-Path -Parent -Path $MyInvocation.MyCommand.Definition
$root_path = Split-Path -Parent -Path $script_path
$vip_theme_path = $root_path + "\www\wp-content\themes\vip"
$git_repo = "" 

if ( Test-Path $vip_theme_path ) {
	mkdir -Force $vip_theme_path
}

foreach ( $theme in @( 'bgr', 'pmc-deadline', 'pmc-plugins', 'pmc-variety', 'pmc-tvline', 'pmc-hollywoodlife', 'pmc-awardsline', 'pmc-411', 'pmc-tvline-mobile', 'pmc-movieline-mobile' ) ) {
	if ( -NOT ( Test-Path ( $vip_theme_path + "\" + $theme  ) ) ) {
		mkdir -Force ( $vip_theme_path + "\" + $theme )
		git clone  ("git@bitbucket.org:penskemediacorp/" + $theme + ".git") ($vip_theme_path + "\" + $theme)
	}
}
