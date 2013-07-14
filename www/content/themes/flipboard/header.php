<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
    <title><?php
        if ( is_single() ) { single_post_title(); }       
        elseif ( is_home() || is_front_page() ) { bloginfo('name'); print ' | '; bloginfo('description'); get_page_number(); }
        elseif ( is_page() ) { single_post_title(''); }
        elseif ( is_search() ) { bloginfo('name'); print ' | Search results for ' . wp_specialchars($s); get_page_number(); }
        elseif ( is_404() ) { bloginfo('name'); print ' | Not Found'; }
        else { bloginfo('name'); wp_title('|'); get_page_number(); }
    ?></title>
     
    <meta http-equiv="content-type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
     
    <link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>" />

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <link rel="shortcut icon" href="<?php bloginfo('template_directory'); ?>/images/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="ico/apple-touch-icon-57-precomposed.png">

    <?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>
    
    <?php wp_enqueue_script("jquery"); ?>
    <?php wp_head(); ?>
     
    <link rel="alternate" type="application/rss+xml" href="<?php bloginfo('rss2_url'); ?>" title="<?php printf( __( '%s latest posts', 'flipboard' ), wp_specialchars( get_bloginfo('name'), 1 ) ); ?>" />
    <link rel="alternate" type="application/rss+xml" href="<?php bloginfo('comments_rss2_url') ?>" title="<?php printf( __( '%s latest comments', 'flipboard' ), wp_specialchars( get_bloginfo('name'), 1 ) ); ?>" />
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

    <script type="text/javascript" src="http://fast.fonts.com/jsapi/d34e3f76-678a-447e-a024-f6b2e5185713.js"></script>

</head>
<body>

    <nav id="global-nav">
      <ul>
        <li class="plus">
          <a href="http://flipboard.wrkbench.in">
            <img src="<?php bloginfo('template_directory'); ?>/images/flipboard-logo-fullcolor-tiny.png" alt="Flipboard" />
          </a>
        </li>
        <li><a href="http://flipboard.wrkbench.in/app-tour">App Tour</a></li>
        <li><a href="http://flipboard.wrkbench.in/publishers">Publishers</a></li>
        <li><a href="http://flipboard.wrkbench.in/advertisers">Advertisers</a></li>
        <li><a href="/" class="selected">Blog</a></li>
        <li><a href="http://flipboard.wrkbench.in/support">Support</a></li>
        <li><a href="#" class="download">Get the APP</a></li>
      </ul>

      <section id="download-wrap">
        <div class="container">
          <div class="centerbox">
              <h1>Download Flipboard for free on<br>
              iPad, iPhone, Android, Kindle Fire &amp; NOOK.</h1>
          <div class="app-downloads">
            <a href="http://ax.itunes.apple.com/us/app/flipboard/id358801284?mt=8" class="app-store">Available on the App Store</a>
            <a href="https://play.google.com/store/apps/details?id=flipboard.app" class="google-play">Get it on Google Play</a>
          </div>
          <!-- remove temporary
          <p>Get the free download link<br /> 
          via SMS or Email</p>
          <form action="#" method="post">
            <input type="text" placeholder="Enter your phone number or email" />
            <input type="submit" class="icons-submit-arrow" />
          </form>
          -->
          </div>
        </div>
      </section>
    </nav>

    <div class="container">
    <section id="publishing-on-flipboard" class="impression light recessed">

    <img src="<?php bloginfo('template_directory'); ?>/images/corner-shadow-top-left.png" alt="[shadow]" class="corner-shadow-top-left" />
    <img src="<?php bloginfo('template_directory'); ?>/images/corner-shadow-top-right.png" alt="[shadow]" class="corner-shadow-top-right" />
    <img src="<?php bloginfo('template_directory'); ?>/images/corner-shadow-bottom-left.png" alt="[shadow]" class="corner-shadow-bottom-left" />
    <img src="<?php bloginfo('template_directory'); ?>/images/corner-shadow-bottom-right.png" alt="[shadow]" class="corner-shadow-bottom-right" />

    <div class="containerx">

        <div class="branding">
            <h1><a href="<?php bloginfo( 'url' ) ?>/" title="<?php bloginfo( 'name' ) ?>" rel="home"><?php bloginfo( 'name' ) ?></a></h1>
        </div>
    </div>

