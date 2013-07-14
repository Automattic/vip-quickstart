<?php get_header(); ?>

<section id="base">
		<div class="entry-content-category">
			<h2 class="page-title"><?php _e( 'Category Archives:', 'flipboard' ) ?> <span><?php single_cat_title() ?></span></span></h2>
			<?php $categorydesc = category_description(); if ( !empty($categorydesc) ) echo apply_filters( 'archive_meta', '<div class="archive-meta">' . $categorydesc . '</div>' ); ?>
		</div>
	<?php rewind_posts(); ?>
	<div class="primary">

		<?php while ( have_posts() ) : the_post() ?>
		<div class="content">
        	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>    
                <div class="entry-meta">
        			<div class="avatar">
        				<a href="<?php echo get_author_link( false, $authordata->ID, $authordata->user_nicename ); ?>" title="<?php printf( __( 'View all posts by %s', 'flipboard' ), $authordata->display_name ); ?>">
        					<?php echo get_avatar( $post->post_author, $size = '60', $default = '' ); ?>
        				</a>
					</div>
                    <span class="meta-prep meta-prep-author"><?php _e('', 'flipboard'); ?></span>
                    <div class="author vcard">
                    	<a href="<?php echo get_author_link( false, $authordata->ID, $authordata->user_nicename ); ?>" title="<?php printf( __( 'View all posts by %s', 'flipboard' ), $authordata->display_name ); ?>"><?php the_author(); ?></a>
                    </div>
                    <div class="published">
                    	<i class="published" title="<?php the_time('Y-m-d\TH:i:sO') ?>"><?php the_time( get_option( 'date_format' ) ); ?></i>
                    </div>
                    <div class="uscore">__</div><br>
                    <?php edit_post_link( __( 'Edit', 'flipboard' ), "<span class=\"edit-link\">", "</span>\n\t\t\t\t\t\n" ) ?>
                </div><!-- .entry-meta -->
               	<div class="entry-content-index">
               		<h2 class="content-title"><a href="<?php the_permalink(); ?>" title="<?php printf( __('Permalink to %s', 'flipboard'), the_title_attribute('echo=0') ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
               		<div class="entry-content">
						<?php the_excerpt(); ?>
					</div>
					<?php wp_link_pages('before=<div class="page-link">' . __( 'Pages:', 'flipboard' ) . '&after=</div>') ?>
					<div class="ital-link2">
						<a href="<?php the_permalink(); ?>" title="<?php printf( __('Permalink to %s', 'flipboard'), the_title_attribute('echo=0') ); ?>" rel="bookmark">Read more</a>
					</div>
	                <div class="entry-utility">
	                    <div class="ital-link">
	                    	<?php the_tags( '<span class="tag-links"><span class="entry-utility-prep entry-utility-prep-tag-links">' . __('Tags: ', 'flipboard' ) . '</span>', ", ", "</span>\n\t\t\t\t\t\t\n" ) ?>
	                	</div>
	                </div><!-- #entry-utility -->
	                <?php if(function_exists('kc_add_social_share')) kc_add_social_share(); ?>
                </div><!-- .entry-content -->
            </div><!-- #post-<?php the_ID(); ?> -->
    	</div><!--.content-->
	<?php endwhile; ?>

</div><!-- .primary -->
<div class="seperator"></div>     

<?php get_sidebar(); ?>
	</section>
	<?php global $wp_query; $total_pages = $wp_query->max_num_pages; if ( $total_pages > 1 ) { ?>
	<?php $paged = $wp_query->get( 'paged' ); ?>
		<?php if ( $paged > 1 ): ?>
		<div class="nav-below">
			<span class="nav-previous"><?php next_posts_link(__( '&larr; Older posts', 'flipboard' )) ?></span>
			<span class="nav-next"><?php previous_posts_link(__( 'Newer posts &rarr;', 'flipboard' )) ?></div>
		</div><!-- #nav-below -->
		<?php else: ?>
		<div class="nav-below">
			<span class="nav-previous"><?php next_posts_link(__( 'Older posts &rarr;', 'flipboard' )) ?></span>
		</div><!-- #nav-below -->
		<?php endif; ?>
	<?php } ?>
<?php get_footer(); ?>