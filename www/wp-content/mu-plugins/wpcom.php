<?php

// Disable automatic creation of intermediate images
add_filter( 'intermediate_image_sizes', function() {
    return array();
});
