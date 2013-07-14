<div class="secondary">
    <?php if ( is_sidebar_active('primary_widget_area') ) : ?>
        <ul>
            <?php dynamic_sidebar('primary_widget_area'); ?>
        </ul>
    <?php endif; ?>       
             
    <?php if ( is_sidebar_active('secondary_widget_area') ) : ?>
        <ul>
            <?php dynamic_sidebar('secondary_widget_area'); ?>
        </ul>
    <?php endif; ?>
</div>
