<?php

/**
 * @file
 * Contains \Netzstrategen\WoocommerceMultisiteShop\Plugin.
 */

namespace Netzstrategen\WoocommerceMultisiteShop;

/**
 * Main front-end functionality.
 */
class Plugin {

  /**
   * Prefix for naming.
   *
   * @var string
   */
  const PREFIX = 'woocommerce-multisite-shop';

  /**
   * Gettext localization domain.
   *
   * @var string
   */
  const L10N = self::PREFIX;

  /**
   * Name of the taxonomy used to filter shops by site.
   *
   * @var string
   */
  const TAXONOMY_NAME = Plugin::PREFIX . '_site';

  /**
   * @var string
   */
  public static $shop_sitename_id = '';

  /**
   * @var array
   */
  public static $post_types = ['post', 'page', 'product'];

  /**
   * @implements init
   */
  public static function init() {
    static::loadTextdomain();
    static::registerTaxonomy(static::TAXONOMY_NAME, static::$post_types);

    // Defines an identifier for the current site name.
    static::$shop_sitename_id = explode('.', $_SERVER['SERVER_NAME'])[0];

    if (!is_admin()) {
      // Sets the site menus depending on the site name and the menu position or name.
      add_filter('wp_nav_menu_args', __CLASS__ . '::wp_nav_menu_args');

      // Loads a custom styling file depending on the site name.
      add_action('wp_enqueue_scripts', __CLASS__ . '::wp_enqueue_scripts', 100);

      // Adds the taxonomy term that identifies the site as a WP_Query parameter.
      add_action('pre_get_posts', __CLASS__ . '::pre_get_posts');

      // Sets the site frontpage. Its slug should be 'frontpage-prefix' where prefix is the taxonomy term that identifies the site.
      if ('page' === get_option('show_on_front') && 'frontpage-' . static::$shop_sitename_id !== get_post_field('post_name', get_option('page_on_front'))) {
        add_filter('pre_option_page_on_front', __CLASS__ . '::pre_option_page_on_front');
      }
    }
  }

  /**
   * Loads the plugin textdomain.
   */
  public static function loadTextdomain() {
    load_plugin_textdomain(static::L10N, FALSE, static::L10N . '/languages/');
  }

  /**
   * Registers a custom taxonomy.
   *
   * @param string $taxonomy_name
   *   Taxonomy name.
   * @param array $post_types
   *   Post types to assign the taxonomy to.
   */
  public static function registerTaxonomy(string $taxonomy_name, array $post_types) {
    register_taxonomy($taxonomy_name, $post_types, [
      'labels' => [
        'name' => _x('Shop site names', 'taxonomy', Plugin::L10N),
        'singular_name' => _x('Shop site name', 'taxonomy', Plugin::L10N),
        'add_new_item' => _x('Add New shop site name', 'taxonomy', Plugin::L10N),
        'edit_item' => __('Edit shop site name', Plugin::L10N),
      ],
      'hierarchical' => TRUE,
      'public' => TRUE,
      'show_ui' => TRUE,
      'show_in_menu' => FALSE,
      'show_in_nav_menus' => FALSE,
      'show_tagcloud' => FALSE,
      'show_in_quick_edit' => TRUE,
      'show_admin_column' => TRUE,
      'query_var' => TRUE,
      'rewrite' => [
        'slug' => 'shop-site-name',
      ],
    ]);
  }

  /**
   * @implements wp_nav_menu_args
   */
  public static function wp_nav_menu_args($args) {
    $site_suffix = '-' . static::$shop_sitename_id;

    if (!empty($args['theme_location'])) {
      $args['menu'] = $args['theme_location'] . $site_suffix;
    } elseif (isset($args['menu']) && $site_suffix !== substr($args['menu'], -strlen($site_suffix))) {
      $args['menu'] = $args['menu'] . $site_suffix;
    }

    return $args;
  }

  /**
   * @implements wp_enqueue_styles
   */
  public static function wp_enqueue_scripts() {
    $baseDirectory = get_stylesheet_directory() . '/' . static::$shop_sitename_id;
    $baseUri = get_stylesheet_directory_uri() . '/' . static::$shop_sitename_id;

    if (file_exists($baseDirectory . '/css/styles.css')) {
      wp_enqueue_style(static::$shop_sitename_id . '_styles', $baseUri. '/css styles.css');
    }
  }

  /**
   * @implements pre_option_page_on_front
   */
  public static function pre_option_page_on_front($pageId) {
    return static::getPageIdBySlug('frontpage-' . static::$shop_sitename_id) ?? $pageId;
  }

  /**
   * @implements parse_tax_query
   */
  public static function pre_get_posts(\WP_Query $query) {
    if (!in_array($query->get('post_type'), static::$post_types)) {
      return;
    }

    $tax_query = $query->get('tax_query');
    $new_tax_query = [
      'taxonomy' => static::TAXONOMY_NAME,
      'field' => 'slug',
      'terms' => static::$shop_sitename_id,
    ];

    if (is_array($tax_query)) {
      $tax_query[] = $new_tax_query;
      $tax_query['relation'] = 'AND';
    } else {
      $tax_query = [$new_tax_query];
    }

    $query->set('tax_query', $tax_query);
  }

  /**
   * Returns the id of a page, given its slug.
   *
   * @param string $slug
   *   Page slug
   *
   * @return int|null
   */
  public static function getPageIdBySlug(string $slug) {
    if ($page = get_page_by_path($slug)) {
      return $page->ID;
    } else {
      return null;
    }
  }

}
