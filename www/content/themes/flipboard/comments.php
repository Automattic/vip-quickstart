    <div id="comments">
    <?php if ( post_password_required() ) : ?>
        <p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'flipboard' ); ?></p>
    </div><!-- #comments -->
    <?php
            return;
        endif;
    ?>

    <?php if ( have_comments() ) : ?>
        <h2 id="comments-title">
            <?php
                printf( _n( 'One thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', get_comments_number(), 'flipboard' ),
                    number_format_i18n( get_comments_number() ), '<span>' . get_the_title() . '</span>' );
            ?>
        </h2>

        <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // are there comments to navigate through ?>
        <nav id="comment-nav-above">
            <h1 class="assistive-text"><?php _e( 'Comment navigation', 'flipboard' ); ?></h1>
            <div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', 'flipboard' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', 'flipboard' ) ); ?></div>
        </nav>
        <?php endif; // check for comment navigation ?>

        <ol class="commentlist">
            <?php
                /* Loop through and list the comments. */
                wp_list_comments( array( 'callback' => 'flipboard_comment' ) );
            ?>
        </ol>

        <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // are there comments to navigate through ?>
        <nav id="comment-nav-below">
            <h1 class="assistive-text"><?php _e( 'Comment navigation', 'flipboard' ); ?></h1>
            <div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', 'flipboard' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', 'flipboard' ) ); ?></div>
        </nav>
        <?php endif; // check for comment navigation ?>

    <?php
        /* If there are no comments and comments are closed. */
        elseif ( ! comments_open() && ! is_page() && post_type_supports( get_post_type(), 'comments' ) ) :
    ?>
        <div class="nocomments">
            <p><?php _e( 'Comments are closed.', 'flipboard' ); ?></p>
        </div>
    <?php endif; ?>

    <?php comment_form(); ?>

</div><!-- #comments -->

