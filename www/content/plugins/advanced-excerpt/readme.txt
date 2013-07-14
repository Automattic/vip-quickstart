=== Advanced Excerpt ===
Contributors: basvd
Tags: excerpt, advanced, post, posts, template, formatting
Donate link: http://basvd.com/code/advanced-excerpt/
Requires at least: 3.2
Tested up to: 3.3
Stable tag: 4.1.1

Several improvements over WP's default excerpt. The size can be limited using character or word count, and HTML markup is not removed.

== Description ==

This plugin adds several improvements to WordPress' default way of creating excerpts.

1. Keeps HTML markup in the excerpt (and you get to choose which tags are included)
2. Trims the excerpt to a given length using either character count or word count
3. Only the 'real' text is counted (HTML is ignored but kept)
4. Customizes the excerpt length and the ellipsis character that are used
5. Completes the last word or sentence in an excerpt (no weird cuts)
6. Adds a *read-more* link to the text
7. Ignores custom excerpts and use the generated one instead
8. Theme developers can use `the_advanced_excerpt()` for even more control (see the FAQ)

Most of the above features are optional and/or can be customized by the user or theme developer.

== Installation ==

After you've downloaded and extracted the files:

1. Upload the complete `advanced-excerpt` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Excerpt' under the 'Settings' menu and configure the plugin

== Frequently Asked Questions ==

= What's an excerpt? =

A short version of a post that is usually displayed wherever the whole post would be too much (eg. search results, news feeds, archives). You can write them yourself, but if you don't, WordPress will make a very basic one instead.

= Why do I need this plugin? =

The default excerpt created by WordPress removes all HTML. If your theme uses `the_excerpt()` to view excerpts, they might look weird because of this (smilies are removed, lists are flattened, etc.) This plugin fixes that and also gives you more control over excerpts.

= Does it work for WordPress version x.x.x? =

During development, the plugin is tested with the most recent version(s) of WordPress. The range of tested versions is listed on this page (3.2 - 3.3 at the moment). It might work on older versions, but it's better to just keep your installation up-to-date.

The plugin requires PHP 5 to work. So if you are using WordPress before 3.2, make sure you have it (WP 3.2 and higher require PHP 5 already).

= Is this plugin available in my language? / How do I translate this plugin? =

The plugin comes bundled with a few (2) languages. The correct language will automatically be selected to match your [WordPress locale](http://codex.wordpress.org/WordPress_in_Your_Language).

More information on translation will be added in the future.

= Does this plugin support multibyte characters, such as Chinese? =

Before 4.1, multibyte characters were supported directly by this plugin. This feature has been removed because it added irrelevant code for a 'problem' that isn't actually specific to the plugin.

If you require multibyte character support on your website, you can [override the default text operations](http://www.php.net/manual/en/mbstring.overload.php) in PHP.

= Can I manually call the filter in my WP theme or plugin? =

The plugin automatically hooks on `the_excerpt()` function and uses the parameters specified in the options panel.

If you want to call the filter with different options, you can use `the_advanced_excerpt()` template tag provided by this plugin. This tag accepts [query-string-style parameters](http://codex.wordpress.org/Template_Tags/How_to_Pass_Tag_Parameters#Tags_with_query-string-style_parameters) (theme developers will be familiar with this notation).

The following parameters can be set:

* `length`, an integer that determines the length of the excerpt
* `use_words`, if set to `1`, the excerpt length will be in words; if set to `0`, characters will be used for the count
* `no_custom`, if set to `1`, an excerpt will be generated even if the post has a custom excerpt; if set to `0`, the custom excerpt will be used
* `no_shortcode`, if set to `1`, shortcodes are removed from the excerpt; if set to `0`, shortcodes will be parsed
* `finish_word`, if set to `1`, the last word in the excerpt will not be cut off; if set to `0`, no effort is made to finish the word
* `finish_sentence`, if set to `1`, the last sentence in the excerpt will not be cut off; if set to `0`, no effort is made to include the full sentence
* `ellipsis`, the string that will substitute the omitted part of the post; if you want to use HTML entities in the string, use `%26` instead of the `&` prefix to avoid breaking the query
* `read_more`, the text used in the read-more link
* `add_link`, if set to `1`, the read-more link will be appended; if `0`, no link will be added
* `allowed_tags`, a comma-separated list of HTML tags that are allowed in the excerpt. Entering `_all` will preserve all tags.
* `exclude_tags`, a comma-separated list of HTML tags that must be removed from the excerpt. Using this setting in combination with `allowed_tags` makes no sense

A custom advanced excerpt call could look like this:

`the_advanced_excerpt('length=320&use_words=0&no_custom=1&ellipsis=%26hellip;&exclude_tags=img,p,strong');`

= Does this plugin work outside the Loop? =

No, this plugin fetches the post from The Loop and there is currently no way to pass a post ID or any custom input to it.
However, you can [start The Loop manually](http://codex.wordpress.org/The_Loop#Multiple_Loops) and apply the plugin as usual.

== Changelog ==

= 4.1 =
* Fix: Template function with custom options works again
* Fix: Data before header bug (retro-fixed in 4.0)
* Feature: Template function also works with array-style parameters
* Removed multibyte support
* Removed PHP 4 support (WP 3.2+ users should be fine, others should update)
* Better code testing before release!

= 4.0 =
* Feature: Brand new parsing algorithm which should resolve some running time issues
* Feature: Options to finish a word or sentence before cutting the excerpt
* Fix: A few small bugs

= 3.1 =

* Fix: A few bugs with custom and character-based excerpts

= 3.0 =

* First major release since 0.2.2 (also removed the `0.` prefix from the version number)
* Feature: Shortcodes can be removed from the excerpt
* Feature: Virtually any HTML tag may now be stripped
* Feature: A read-more link with custom text can be added
* Fix: Word-based excerpt speed improved
* Fix: Template tag function improved
* Fix: Better ellipsis placement