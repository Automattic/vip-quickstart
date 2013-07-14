<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content
 * after. Calls sidebar-footer.php for bottom widgets.
 *
 * @package WordPress
 * @subpackage Twenty_Ten
 * @since Twenty Ten 1.0
 */
?>
	</div><!-- #main -->

	<div id="footer" role="contentinfo">

		<div class="container">
          <nav>
            <ul id="footer-nav">
              <li><a href="http://flipboard.com/about/">About Us</a></li>
              <li><a href="http://flipboard.com/press/">Press</a></li>
              <li><a href="http://flipboard.com/careers/">Careers</a></li>
              <li><a href="http://inside.flipboard.com">Blog</a></li>
              <li><a href="http://twitter.com/flipboard">Twitter</a></li>
              <li><a href="http://facebook.com/flipboard">Facebook</a></li>
              <li><a href="http://plus.google.com/117621824418336664774/posts">Google+</a></li>
            </ul>
          </nav>

          <div class="app-downloads">
            <a href="http://beacon.flipboard.com/redirect.php?app=flipwebbutton&amp;url=http%3A%2F%2Fitunes.apple.com%2Fus%2Fapp%2Fflipboard-your-social-news%2Fid358801284%26referrer%3Dutm_source%253Dflipweb%2526utm_medium%253Dweb_button%2526utm_term%253Dios%2526utm_content%253DUS%2526utm_campaign%253Ddownload_button_ios" class="app-store" target="_blank" data-label="Footer">Available on the App Store</a>
            <a href="http://beacon.flipboard.com/redirect.php?app=flipwebbutton&amp;url=https%3A%2F%2Fplay.google.com%2Fstore%2Fapps%2Fdetails%3Fid%3Dflipboard.app%26referrer%3Dutm_source%253Dflipweb%2526utm_medium%253Dweb_button%2526utm_term%253Dandroid%2526utm_campaign%253Ddownload_button_android" class="google-play" target="_blank" data-label="Footer">Get it on Google Play</a>
          </div>

          <h1 class="tagline">Made with Love <br/>in Palo Alto, California.</h1>
          <p class="copyright">© 2012 Flipboard, Inc.&nbsp;<br>
Flipboard and the Flipboard Logo<br>
are trademarks of Flipboard, Inc.</p>
          <p class="copyright">
            <a href="http://flipboard.com/privacy-policy/">Privacy Policy</a>
            <a href="http://flipboard.com/terms-of-use/">Terms</a>
            <a href="http://flipboard.com/copyright/">Copyright</a>
          </p>

        </div>

	</div><!-- #footer -->

</div><!-- #wrapper -->

<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/jquery.js"></script>
<script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/blog.js"></script>

<?php
	/* Always have wp_footer() just before the closing </body>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to reference JavaScript files.
	 */

	wp_footer();
?>
</body>
</html>
