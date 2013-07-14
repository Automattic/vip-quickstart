<?php
/*
Plugin Name: Advanced Excerpt
Plugin URI: http://basvd.com/code/advanced-excerpt/
Description: Several improvements over WP's default excerpt. The size of the excerpt can be limited using character or word count, and HTML markup is not removed.
Version: 4.1.1
Author: Bas van Doren
Author URI: http://basvd.com/

Copyright 2007 Bas van Doren

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!class_exists('AdvancedExcerpt')):
  class AdvancedExcerpt
  {
    // Plugin configuration
    public $name;
    public $text_domain;
    public $options;
    public $default_options = array(
      'length' => 40,
      'use_words' => 1,
      'no_custom' => 1,
      'no_shortcode' => 1,
      'finish_word' => 0,
      'finish_sentence' => 0,
      'ellipsis' => '&hellip;',
      'read_more' => 'Read the rest',
      'add_link' => 0,
      'allowed_tags' => array('_all')
    );
    
    // Basic HTML tags (determines which tags are in the checklist by default)
    public static $options_basic_tags = array
    (
      'a', 'abbr', 'acronym', 'b', 'big',
      'blockquote', 'br', 'center', 'cite', 'code', 'dd', 'del', 'div', 'dl', 'dt',
      'em', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins',
      'li', 'ol', 'p', 'pre', 'q', 's', 'small', 'span', 'strike', 'strong', 'sub',
      'sup', 'table', 'td', 'th', 'tr', 'u', 'ul'
    );

    // Almost all HTML tags (extra options)
    public static $options_all_tags = array(
      'a', 'abbr', 'acronym', 'address', 'applet',
      'area', 'b', 'bdo', 'big', 'blockquote', 'br', 'button', 'caption', 'center',
      'cite', 'code', 'col', 'colgroup', 'dd', 'del', 'dfn', 'dir', 'div', 'dl',
      'dt', 'em', 'fieldset', 'font', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3',
      'h4', 'h5', 'h6', 'hr', 'i', 'iframe', 'img', 'input', 'ins', 'isindex', 'kbd',
       'label', 'legend', 'li', 'map', 'menu', 'noframes', 'noscript', 'object',
      'ol', 'optgroup', 'option', 'p', 'param', 'pre', 'q', 's', 'samp', 'script',
      'select', 'small', 'span', 'strike', 'strong', 'style', 'sub', 'sup', 'table',
      'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'tr', 'tt', 'u', 'ul',
      'var'
    );
    
    // Singleton
    private static $inst = null;
    public static function Instance($new = false)
    {
      if (self::$inst == null || $new)
      {
        self::$inst = new AdvancedExcerpt();
      }
      return self::$inst;
    }
    
    private function __construct()
    {
      $this->name = strtolower(get_class());
      $this->text_domain = $this->name;
      $this->load_options();
      
      load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)));
      register_activation_hook(__FILE__, array(
        &$this,
        'install'
      ));
      //register_deactivation_hook($file, array(&$this, 'uninstall'));

      add_action('admin_menu', array(
        &$this,
        'add_pages'
      ));

      // Replace the default filter (see /wp-includes/default-filters.php)
      //remove_filter('get_the_excerpt', 'wp_trim_excerpt');
      // Replace everything
      remove_all_filters('get_the_excerpt');
      add_filter('get_the_excerpt', array(
        &$this,
        'filter'
      ));
    }

    public function filter($text)
    {
      // Extract options (skip collisions)
      if (is_array($this->options))
      {
        extract($this->options, EXTR_SKIP);
        $this->options = null; // Reset
      }
      extract($this->default_options, EXTR_SKIP);
      
      // Avoid custom excerpts
      if (!empty($text) && !$no_custom)
        return $text;

      // Get the full content and filter it
      $text = get_the_content('');
      if (1 == $no_shortcode)
        $text = strip_shortcodes($text);
      $text = apply_filters('the_content', $text);

      // From the default wp_trim_excerpt():
      // Some kind of precaution against malformed CDATA in RSS feeds I suppose
      $text = str_replace(']]>', ']]&gt;', $text);

      // Determine allowed tags
      if(!isset($allowed_tags))
        $allowed_tags = self::$options_all_tags;
      
      if(isset($exclude_tags))
        $allowed_tags = array_diff($allowed_tags, $exclude_tags);
      
      // Strip HTML if allow-all is not set
      if (!in_array('_all', $allowed_tags))
      {
        if (count($allowed_tags) > 0)
          $tag_string = '<' . implode('><', $allowed_tags) . '>';
        else
          $tag_string = '';
        $text = strip_tags($text, $tag_string);
      }

      // Create the excerpt
      $text = $this->text_excerpt($text, $length, $use_words, $finish_word, $finish_sentence);

      // Add the ellipsis or link
      $text = $this->text_add_more($text, $ellipsis, ($add_link) ? $read_more : false);

      return $text;
    }
    
    public function text_excerpt($text, $length, $use_words, $finish_word, $finish_sentence)
    {
      $tokens = array();
      $out = '';
      $w = 0;
      
      // Divide the string into tokens; HTML tags, or words, followed by any whitespace
      // (<[^>]+>|[^<>\s]+\s*)
      preg_match_all('/(<[^>]+>|[^<>\s]+)\s*/u', $text, $tokens);
      foreach ($tokens[0] as $t)
      { // Parse each token
        if ($w >= $length && !$finish_sentence)
        { // Limit reached
          break;
        }
        if ($t[0] != '<')
        { // Token is not a tag
          if ($w >= $length && $finish_sentence && preg_match('/[\?\.\!]\s*$/uS', $t) == 1)
          { // Limit reached, continue until ? . or ! occur at the end
            $out .= trim($t);
            break;
          }
          if (1 == $use_words)
          { // Count words
            $w++;
          } else
          { // Count/trim characters
            $chars = trim($t); // Remove surrounding space
            $c = strlen($chars);
            if ($c + $w > $length && !$finish_sentence)
            { // Token is too long
              $c = ($finish_word) ? $c : $length - $w; // Keep token to finish word
              $t = substr($t, 0, $c);
            }
            $w += $c;
          }
        }
        // Append what's left of the token
        $out .= $t;
      }
      
      return trim(force_balance_tags($out));
    }
    
    public function text_add_more($text, $ellipsis, $read_more)
    {
      // New filter in WP2.9, seems unnecessary for now
      //$ellipsis = apply_filters('excerpt_more', $ellipsis);
      
      if ($read_more)
        $ellipsis .= sprintf(' <a href="%s" class="read_more">%s</a>', get_permalink(), $read_more);

      $pos = strrpos($text, '</');
      if ($pos !== false)
        // Inside last HTML tag
        $text = substr_replace($text, $ellipsis, $pos, 0);
      else
        // After the content
        $text .= $ellipsis;
      
      return $text;
    }

    public function install()
    {
      foreach($this->default_options as $k => $v)
      {
        add_option($this->name . '_' . $k, $v);
      }
    }

    public function uninstall()
    {
      // Nothing to do (note: deactivation hook is also disabled)
    }

    private function load_options()
    {
      foreach($this->default_options as $k => $v)
      {
        $this->default_options[$k] = get_option($this->name . '_' . $k, $v);
      }
    }

    private function update_options()
    {
      $length       = (int) $_POST[$this->name . '_length'];
      $use_words    = ('on' == $_POST[$this->name . '_use_words']) ? 1 : 0;
      $no_custom    = ('on' == $_POST[$this->name . '_no_custom']) ? 1 : 0;
      $no_shortcode = ('on' == $_POST[$this->name . '_no_shortcode']) ? 1 : 0;
      $finish_word     = ('on' == $_POST[$this->name . '_finish_word']) ? 1 : 0;
      $finish_sentence = ('on' == $_POST[$this->name . '_finish_sentence']) ? 1 : 0;
      $add_link     = ('on' == $_POST[$this->name . '_add_link']) ? 1 : 0;

      // TODO: Drop magic quotes (deprecated in php 5.3)
      $ellipsis  = (get_magic_quotes_gpc() == 1) ? stripslashes($_POST[$this->name . '_ellipsis']) : $_POST[$this->name . '_ellipsis'];
      $read_more = (get_magic_quotes_gpc() == 1) ? stripslashes($_POST[$this->name . '_read_more']) : $_POST[$this->name . '_read_more'];

      $allowed_tags = array_unique((array) $_POST[$this->name . '_allowed_tags']);

      update_option($this->name . '_length', $length);
      update_option($this->name . '_use_words', $use_words);
      update_option($this->name . '_no_custom', $no_custom);
      update_option($this->name . '_no_shortcode', $no_shortcode);
      update_option($this->name . '_finish_word', $finish_word);
      update_option($this->name . '_finish_sentence', $finish_sentence);
      update_option($this->name . '_ellipsis', $ellipsis);
      update_option($this->name . '_read_more', $read_more);
      update_option($this->name . '_add_link', $add_link);
      update_option($this->name . '_allowed_tags', $allowed_tags);

      $this->load_options();
?>
        <div id="message" class="updated fade"><p>Options saved.</p></div>
    <?php
    }

    public function page_options()
    {
      if ('POST' == $_SERVER['REQUEST_METHOD'])
      {
        check_admin_referer($this->name . '_update_options');
        $this->update_options();
      }

      extract($this->default_options, EXTR_SKIP);

      $ellipsis  = htmlentities($ellipsis);
      $read_more = htmlentities($read_more);

      $tag_list = array_unique(self::$options_basic_tags + $allowed_tags);
      sort($tag_list);
      $tag_cols = 5;
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php
      _e("Advanced Excerpt Options", $this->text_domain);
?></h2>
    <form method="post" action="">
    <?php
      if (function_exists('wp_nonce_field'))
        wp_nonce_field($this->name . '_update_options');
?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="<?php echo $this->name; ?>_length">
                <?php _e("Excerpt Length:", $this->text_domain); ?></label></th>
                <td>
                    <input name="<?php echo $this->name; ?>_length" type="text"
                           id="<?php echo $this->name; ?>_length"
                           value="<?php echo $length; ?>" size="2"/>
                    <input name="<?php echo $this->name; ?>_use_words" type="checkbox"
                           id="<?php echo $this->name; ?>_use_words" value="on"<?php
                           echo (1 == $use_words) ? ' checked="checked"' : ''; ?>/>
                           <?php _e("Use words?", $this->text_domain); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="<?php echo $this->name; ?>_ellipsis">
                <?php _e("Ellipsis:", $this->text_domain); ?></label></th>
                <td>
                    <input name="<?php echo $this->name; ?>_ellipsis" type="text"
                           id="<?php echo $this->name; ?>_ellipsis"
                           value="<?php echo $ellipsis; ?>" size="5"/>
                    <?php _e('(use <a href="http://www.w3schools.com/tags/ref_entities.asp">HTML entities</a>)', $this->text_domain); ?>
                    <br />
                    <?php _e("Will substitute the part of the post that is omitted in the excerpt.", $this->text_domain); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="<?php echo $this->name; ?>_length">
                <?php _e("Finish:", $this->text_domain); ?></label></th>
                <td>
                    <input name="<?php echo $this->name; ?>_finish_word" type="checkbox"
                           id="<?php echo $this->name; ?>_finish_word" value="on"<?php
                           echo (1 == $finish_word) ? ' checked="checked"' : ''; ?>/>
                           <?php _e("Word", $this->text_domain); ?><br/>
                    <input name="<?php echo $this->name; ?>_finish_sentence" type="checkbox"
                           id="<?php echo $this->name; ?>_finish_sentence" value="on"<?php
                           echo (1 == $finish_sentence) ? ' checked="checked"' : ''; ?>/>
                           <?php _e("Sentence", $this->text_domain); ?>
                    <br />
                    <?php _e("Prevents cutting a word or sentence at the end of an excerpt. This option can result in (slightly) longer excerpts.", $this->text_domain); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="<?php echo $this->name; ?>_read_more">
                <?php  _e("&lsquo;Read-more&rsquo; Text:", $this->text_domain); ?></label></th>
                <td>
                    <input name="<?php echo $this->name; ?>_read_more" type="text"
                           id="<?php echo $this->name; ?>_read_more" value="<?php echo $read_more; ?>" />
                    <input name="<?php echo $this->name; ?>_add_link" type="checkbox"
                           id="<?php echo $this->name; ?>_add_link" value="on" <?php
                           echo (1 == $add_link) ? 'checked="checked" ' : ''; ?>/>
                           <?php _e("Add link to excerpt", $this->text_domain); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="<?php echo $this->name; ?>_no_custom">
                <?php _e("No Custom Excerpts:", $this->text_domain); ?></label></th>
                <td>
                    <input name="<?php echo $this->name; ?>_no_custom" type="checkbox"
                           id="<?php echo $this->name; ?>_no_custom" value="on" <?php
                           echo (1 == $no_custom) ? 'checked="checked" ' : ''; ?>/>
                           <?php _e("Generate excerpts even if a post has a custom excerpt attached.", $this->text_domain); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="<?php echo $this->name; ?>_no_shortcode">
                <?php _e("Strip Shortcodes:", $this->text_domain); ?></label></th>
                <td>
                    <input name="<?php echo $this->name; ?>_no_shortcode" type="checkbox"
                           id="<?php echo $this->name; ?>_no_shortcode" value="on" <?php
                           echo (1 == $no_shortcode) ? 'checked="checked" ' : ''; ?>/>
                           <?php _e("Remove shortcodes from the excerpt. <em>(recommended)</em>", $this->text_domain); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e("Keep Markup:", $this->text_domain); ?></th>
                <td>
                    <table id="<?php echo $this->name; ?>_tags_table">
                        <tr>
                            <td colspan="<?php echo $tag_cols; ?>">
    <input name="<?php echo $this->name; ?>_allowed_tags[]" type="checkbox"
           value="_all" <?php echo (in_array('_all', $allowed_tags)) ? 'checked="checked" ' : ''; ?>/>
           <?php _e("Don't remove any markup", $this->text_domain); ?>
                            </td>
                        </tr>
<?php
      $i = 0;
      foreach ($tag_list as $tag):
        if ($tag == '_all')
          continue;
        if (0 == $i % $tag_cols):
?>
                        <tr>
<?php
        endif;
        $i++;
?>
                            <td>
    <input name="<?php echo $this->name; ?>_allowed_tags[]" type="checkbox"
           value="<?php echo $tag; ?>" <?php
           echo (in_array($tag, $allowed_tags)) ? 'checked="checked" ' : ''; ?>/>
    <code><?php echo $tag; ?></code>
                            </td>
<?php
        if (0 == $i % $tag_cols):
          $i = 0;
          echo '</tr>';
        endif;
      endforeach;
      if (0 != $i % $tag_cols):
?>
                          <td colspan="<?php echo ($tag_cols - $i); ?>">&nbsp;</td>
                        </tr>
<?php
      endif;
?>
                    </table>
                    <a href="" id="<?php echo $this->name; ?>_select_all">Select all</a>
                    / <a href="" id="<?php echo $this->name; ?>_select_none">Select none</a><br />
                    More tags:
                    <select name="<?php echo $this->name; ?>_more_tags" id="<?php echo $this->name; ?>_more_tags">
<?php
      foreach (self::$options_all_tags as $tag):
?>
                        <option value="<?php echo $tag; ?>"><?php echo $tag; ?></option>
<?php
      endforeach;
?>
                    </select>
                    <input type="button" name="<?php echo $this->name; ?>_add_tag" id="<?php echo $this->name; ?>_add_tag" class="button" value="Add tag" />
                </td>
            </tr>
        </table>
        <p class="submit"><input type="submit" name="Submit" class="button-primary"
                                 value="<?php _e("Save Changes", $this->text_domain); ?>" /></p>
    </form>
</div>
<?php
    }

    public function page_script()
    {
      wp_enqueue_script($this->name . '_script', WP_PLUGIN_URL . '/advanced-excerpt/advanced-excerpt.js', array(
        'jquery'
      ));
    }

    public function add_pages()
    {
      $options_page = add_options_page(__("Advanced Excerpt Options", $this->text_domain), __("Excerpt", $this->text_domain), 'manage_options', 'options-' . $this->name, array(
        &$this,
        'page_options'
      ));

      // Scripts
      add_action('admin_print_scripts-' . $options_page, array(
        &$this,
        'page_script'
      ));
    }
  }
  
  AdvancedExcerpt::Instance();

  // Do not use outside the Loop!
  function the_advanced_excerpt($args = '', $get = false)
  {
    if (!empty($args) && !is_array($args))
    {
      $args = wp_parse_args($args);

      // Parse query style parameters
      if (isset($args['ellipsis']))
        $args['ellipsis'] = urldecode($args['ellipsis']);

      if (isset($args['allowed_tags']))
        $args['allowed_tags'] = preg_split('/[\s,]+/', $args['allowed_tags']);

      if (isset($args['exclude_tags']))
      {
        $args['exclude_tags'] = preg_split('/[\s,]+/', $args['exclude_tags']);
      }
    }
    // Set temporary options
    AdvancedExcerpt::Instance()->options = $args;
    
    if ($get)
      return get_the_excerpt();
    else
      the_excerpt();
  }
endif;
