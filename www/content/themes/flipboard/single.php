<?php get_header(); ?>
     
<section id="base">
	<div class="primary">
		<div class="content-single">
			<?php the_post() ?>
        	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        		<div class="entry-meta">
        			<div class="avatar">
        				<a href="<?php echo get_author_link( false, $authordata->ID, $authordata->user_nicename ); ?>" title="<?php printf( __( 'View all posts by %s', 'flipboard' ), $authordata->display_name ); ?>">
        					<?php echo get_avatar( $post->post_author, $size = '70', $default = '' ); ?>
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
						<?php the_content(); ?>
						<?php wp_link_pages('before=<div class="page-link">' . __( 'Pages:', 'flipboard' ) . '&after=</div>') ?> 
	                	<?php if(function_exists('kc_add_social_share')) kc_add_social_share(); ?>
	            	</div>
					<div class="entry-utility-single">
			        	<?php printf( __( 'This entry was posted in %1$s%2$s.', 'flipboard' ),
			            get_the_category_list(', '),
			            get_the_tag_list( __( ' and tagged ', 'flipboard' ), ', ', '' ),
			            get_permalink(),
			            the_title_attribute('echo=0'),
			            comments_rss() ) ?>
			            <!-- Shortened Category Print
						<?php if ( ('open' == $post->comment_status) && ('open' == $post->ping_status) ) : // Comments and trackbacks open ?>
						                        <?php printf( __( '<a class="comment-link" href="#respond" title="Post a comment">Post a comment</a>', 'flipboard' )) ?>
						<?php elseif ( !('open' == $post->comment_status) && ('open' == $post->ping_status) ) : // Only trackbacks open ?>
						                        <?php printf( __( 'Comments are closed, but you can leave a trackback.', 'flipboard' ), get_trackback_url() ) ?>
						<?php elseif ( ('open' == $post->comment_status) && !('open' == $post->ping_status) ) : // Only comments open ?>
						                        <?php _e( '<a class="comment-link" href="#respond" title="Post a comment">post a comment</a>.', 'flipboard' ) ?>
						<?php elseif ( !('open' == $post->comment_status) && !('open' == $post->ping_status) ) : // Comments and trackbacks closed ?>
						                        <?php _e( 'Comments and pings are both closed.', 'flipboard' ) ?>
						<?php endif; ?>
						-->
					</div><!-- .entry-utility-single -->
			    </div><!-- .entry-utility -->
			</div><!-- #post-<?php the_ID(); ?> -->
 		</div><!--.content-->
 		<?php comments_template('', true); ?>
 	</div><!-- .primary -->
	<div class="seperator"></div>

 <?php get_sidebar(); ?>
	</section>
	<div class="nav-below">
		<span class="nav-previous"><?php previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span> Previous post', 'flipboard' ) ); ?></span>
		<span class="nav-next"><?php next_post_link( '%link', __( 'Next post <span class="meta-nav">&rarr;</span>', 'flipboard' ) ); ?></span>
	</div><!-- #nav-below -->
<?php get_footer(); ?>