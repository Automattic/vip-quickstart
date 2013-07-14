<?php get_header(); ?>
     
<section id="base">
	<div class="primary">
             
	<?php the_post(); ?>
	                 
	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	    <h1 class="entry-title"><?php the_title(); ?></h1>
	    <div class="entry-content">
			<?php the_content(); ?>
			<?php wp_link_pages('before=<div class="page-link">' . __( 'Pages:', 'flipboard' ) . '&after=</div>') ?>                 
			<?php edit_post_link( __( 'Edit', 'flipboard' ), '<span class="edit-link">', '</span>' ) ?>
	    </div><!-- .entry-content -->
	</div><!-- #post-<?php the_ID(); ?> -->           
	             
	<?php if ( get_post_custom_values('comments') ) comments_template() // Add a custom field with Name and Value of "comments" to enable comments on this page ?>            
     
    </div><!-- #primary -->     
         
<?php get_sidebar(); ?>
</section>  
<?php get_footer(); ?>