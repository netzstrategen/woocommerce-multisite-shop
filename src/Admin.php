<?php

/**
 * @file
 * Contains \Netzstrategen\WoocommerceMultisiteShop\Admin.
 */

namespace Netzstrategen\WoocommerceMultisiteShop;

/**
 * Administrative back-end functionality.
 */
class Admin {

  const SHOP_SITE_NAMES_MENU_ICON = 'dashicons-admin-multisite';
  const SHOP_SITE_NAMES_MENU_POSITION = 30;

  /**
   * @implements admin_init
   */
  public static function init() {
    // Adds a posts filter select list with the taxonomy terms.
    add_action('restrict_manage_posts', __CLASS__ . '::restrict_manage_posts');

    // Assigns the site name identifier to new created content.
    add_action('wp_insert_post', __CLASS__ . '::wp_insert_post', 11, 3);
  }

  /**
   * Adds an admin menu entry for the Shop site names taxonomy edit page.
   */
  public static function admin_menu() {
    add_menu_page(
      _x('Shop site names', 'taxonomy', Plugin::L10N),
      _x('Shop site names', 'taxonomy', Plugin::L10N),
      'manage_options',
      'shop-site-names',
      __CLASS__ . '::redirectToTaxonomyEditPage',
      static::SHOP_SITE_NAMES_MENU_ICON,
      static::SHOP_SITE_NAMES_MENU_POSITION
    );

    add_submenu_page(
      'shop-site-names',
      '',
      '',
      'manage_options',
      'shop-site-names'
    );
  }

  /**
   * Redirects to the Shop site names taxonomy edit page.
   */
  public static function redirectToTaxonomyEditPage() {
    wp_redirect('edit-tags.php?taxonomy=' . Plugin::TAXONOMY_NAME);
  }

  public static function restrict_manage_posts($post_type) {
    if (!in_array($post_type, Plugin::$post_types)) {
      return;
    }

    $taxonomy_slug = Plugin::TAXONOMY_NAME;
    $taxonomy_obj = get_taxonomy($taxonomy_slug);
		$terms = get_terms($taxonomy_slug);

		echo "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
		echo '<option value="">' . $taxonomy_obj->labels->all_items . '</option>';
		foreach ($terms as $term) {
			printf(
				'<option value="%1$s" %2$s>%3$s (%4$s)</option>',
				$term->slug,
				((isset($_GET[$taxonomy_slug]) && ($_GET[$taxonomy_slug] == $term->slug)) ? ' selected="selected"' : ''),
				$term->name,
				$term->count
			);
		}
		echo '</select>';
  }

  /**
   * @implements wp_insert_post
   */
  public static function wp_insert_post(int $post_id, \WP_Post $post, bool $update) {
    if ($update || !in_array($post->post_type, Plugin::$post_types)) {
      return;
    }
    wp_set_object_terms($post_id, Plugin::$shop_sitename_id, Plugin::TAXONOMY_NAME, true);
  }

}
