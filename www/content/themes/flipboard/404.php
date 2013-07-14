<?php get_header(); ?>

<section id="base">
	<div class="primary">
		<div id="post-0" class="post error404 not-found">
	        <h2><?php _e( 'Not Found', 'flipboard' ); ?></h2>
	        <div class="entry-content">
	            <p><?php _e( 'Apologies, but we were unable to find what you were looking for. Perhaps searching will help.', 'flipboard' ); ?></p>
	        </div><!-- .entry-content -->
	    </div><!-- #post-0 -->
	</div>
	<div class="seperator"></div>
<?php get_sidebar(); ?>
</section>
<?php get_footer(); ?>