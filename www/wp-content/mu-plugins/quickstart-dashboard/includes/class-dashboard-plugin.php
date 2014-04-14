<?php

abstract class Dashboard_Plugin {
    /**
     * Returns the friendly name of the plugin that can be shown to a user
     * @return string The friendly name
     */
	abstract function name();
    
    /**
     * Inits the plugin actions and filters.
     * 
     * This function is typically called during `admin_init`, so all `init` and `admin_init`
     * actions should be performed during this function call.
     */
    abstract function init();
}
