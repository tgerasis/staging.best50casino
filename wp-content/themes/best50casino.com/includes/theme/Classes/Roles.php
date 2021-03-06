<?php
///**
// * Plugin Name: Geniem Roles
// * Plugin URI: https://github.com/devgeniem/wp-geniem-roles
// * Description: WordPress plugin to edit and create roles in code.
// * Version: 1.0.3
// * Author: Timi-Artturi Mäkelä / Anttoni Lahtinen / Ville Siltala / Geniem Oy
// * Author URI: https://geniem.fi
// */

namespace Geniem;

/**
 * Geniem Roles
 */
final class Roles {

    /**
     * Roles.
     *
     * @var array Array of roles.
     */
    private static $roles;

    /**
     * Singleton Geniem Roles instance.
     *
     * @var object Instance of Geniem Roles.
     */
    private static $instance;

    /**
     * Init roles singleton.
     */
    public static function instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Roles();
        }

        return self::$instance;
    }

    /**
     * Roles __constuctor
     */
    private function __construct() {

        add_action( 'setup_theme', [ __CLASS__, 'reset_roles_on_admin_page' ] );

        // Actions
        add_action( 'setup_theme', [ __CLASS__, 'load_current_roles' ] );
//        add_action( 'init', [ __CLASS__, 'add_options_page' ] );
//        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'geniem_roles_styles' ] );
    }

    /**
     * Enqueue styles
     *
     * @param string $hook Current menu slug.
     * @return void
     */
    public static function geniem_roles_styles( $hook ) {

        $allowed = [
            'toplevel_page_wp-geniem-roles',
            'geniem-roles_page_wp-geniem-roles-slugs',
        ];

        // Skip enqueue geniem-roles-styles if not on wp-geniem-roles menu page.
        if ( in_array( $hook, $allowed, true ) ) {
            wp_enqueue_style( 'geniem_roles_styles', plugin_dir_url( __FILE__ ) . 'geniem-roles-styles.css', false, '1.0.6' );
        }
    }

    /**
     * Reset roles if current screen is.
     */
    public static function reset_roles_on_admin_page() {

        if ( is_admin() ) {
            $page_param = filter_input( INPUT_GET, 'page' );

            if ( $page_param === 'wp-geniem-roles' ) {
                self::reset_roles();
            }
        }
    }

    /**
     * Loads all active roles.
     * $wp_roles isn't available before setup_theme hook.
     */
    public static function load_current_roles() {

        // Get global wp_roles containing roles, role_objects and role_names.
        global $wp_roles;

        // Loop through existing role_objects.
        foreach ( $wp_roles->role_objects as $role ) {

            $display_name = '';

            // Loop through role_names table to get display name.
            foreach ( $wp_roles->role_names as $key => $value ) {

                if ( $key === $role->name ) {
                    $display_name = $value;
                }
            }

            // Create Role instance.
            self::$roles[ $role->name ] = new Role( $role->name, $display_name );
        }
    }

    /**
     * Returns all role instances created from active roles.
     *
     * @return array $roles;
     */
    public static function get_roles() {

        $roles = self::$roles;

        return $roles;
    }

    /**
     * Create new roles
     *
     * @param string  $name Role name in lowercase.
     * @param string  $display_name Role display name.
     * @param boolean $caps Capabilities to be added.
     */
    public static function create( $name, $display_name, $caps ) {

        // If role already exists return it
        if ( self::role_exists( $name ) ) {

            $role = self::$roles[ $name ];

            return $role;
        } else {

            // Merge capabilities.
            $caps = \wp_parse_args( $caps, Role::get_default_caps() );

            // Add role.
            \add_role( $name, $display_name, $caps );

            // Create new \Geniem\Role intance.
            $role_instance = new Role( $name, $display_name );

            return $role_instance;
        }
    }

    /**
     * Check if role exists.
     *
     * @param string $name Role name.
     * @return null|object $role|null role object or null.
     */
    public static function role_exists( $name ) {

        $role = \get_role( $name );

        return $role !== null;
    }

    /**
     * Remove roles.
     *
     * @param string $name Role name.
     */
    public static function remove_role( $name ) {

        // If role exists remove role
        if ( self::role_exists( $name ) ) {
            \remove_role( $name );
            unset( self::$roles[ $name ] );
        }
    }

    /**
     * Rename a role with new_display_name.
     *
     * @param string $name Role name.
     * @param string $new_display_name New display name for role.
     * @return void
     */
    public static function rename( $name, $new_display_name ) {

        global $wp_roles;

        // Rename role
        $wp_roles->roles[ $name ]['name'] = $new_display_name;
        $wp_roles->role_names[ $name ]    = $new_display_name;

        // Update also geniem roles instance name.
        self::$roles[ $name ]->name = $new_display_name;
    }

    /**
     * Add caps to the role.
     *
     * @param string $name Role name.
     * @param string $caps Role capabilities.
     * @return false On fail returns false.
     */
    public static function add_caps( $name, $caps ) {

        // If role name is not set or caps are empty.
        if ( ! empty( $name ) || ! empty( $caps ) ) {

            // Get wp role
            $role = \get_role( $name );

            // Loop through removed caps.
            foreach ( $caps as $cap ) {

                // If cap isset for the role.
                if ( isset( self::$roles[ $name ]->capabilities[ $cap ] ) ) {

                    // Make sure that cap isn't true.
                    if ( self::$roles[ $name ]->capabilities[ $cap ] !== true ) {

                        // Add cap for a role.
                        $role->add_cap( $cap );

                    } else {

                        return false;
                    } // End if().
                } else {

                    // Add cap for a role.
                    $role->add_cap( $cap );
                } // End if().
            } // End foreach().
        } else {
            return false;
        }
    }

    /**
     * Remove capabilities from a role.
     *
     * @param string $name Role slug.
     * @param array  $caps Array of capabilities to be removed.
     * @return false On fail returns false.
     */
    public static function remove_caps( $name, $caps ) {

        // If role name is not set or caps are empty.
        if ( ! empty( $name ) || ! empty( $caps ) ) {

            // Loop through removed caps.
            foreach ( $caps as $cap ) {

                // If cap isset for the role.
                if ( isset( self::$roles[ $name ]->capabilities[ $cap ] ) ) {

                    // Make sure that cap is true.
                    if ( self::$roles[ $name ]->capabilities[ $cap ] === true ) {

                        // Get wp role
                        $role = \get_role( $name );

                        // Remove cap for a role.
                        $role->remove_cap( $cap );

                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } // End foreach().
        } else {
            return false;
        }

    }

    /**
     * If role exists return the role else returns false.
     * insert int or string
     *
     * @param string $name Role name.
     */
    public static function get( $name ) {

        // Get from cache
        if ( isset( self::$roles[ $name ] ) ) {
            return self::$roles[ $name ];
        }

        return null;
    }

    /**
     * Remove menu pages from a role.
     * note: All menu page slugs can be found from the admin Geniem Roles -> Menu slugs.
     *
     * @param string $name Role name.
     * @param string $menu_pages Menu page slugs.
     * @return false On fail returns false.
     */
    public static function remove_menu_pages( $name = '', $menu_pages = null ) {

        // Run in admin_menu hook when called outside class
        add_action( 'admin_menu', function() use ( $name, $menu_pages ) {

            // user object
            $user = wp_get_current_user();

            /**
             * Remove menu pages by role
             * Note: Some plugins cannot be removed in admin_menu -hook so we have to do it in admin_init.
             * In admin_init hook we have to check if not doing ajax to avoid errors.
             */
            if ( in_array( $name, $user->roles, true ) && ! wp_doing_ajax() ) {

                if ( ! empty( $menu_pages ) ) {

                    // If multiple menu pages in array.
                    if ( is_array( $menu_pages ) && ! empty( $menu_pages ) ) {
                        foreach ( $menu_pages as $main_lvl_key => $menu_page ) {

                            // If there are submenu pages to be removed.
                            if ( is_array( $menu_page ) ) {
                                foreach ( $menu_page as $submenu_item ) {

                                    // If we want to hide customize.php from admin menu we need to do some extra checks.
                                    if ( $submenu_item === 'customize.php' ) {

                                        // Get and form current page url ending.
                                        $current_url            = \esc_url( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                                        $url_ending             = substr( $current_url, strrpos( $current_url, '/wp-admin/' ) + 0 );
                                        $url_ending             = rawurlencode( $url_ending );
                                        $current_customizer_url = 'customize.php?return=' . $url_ending;

                                        \remove_submenu_page( $main_lvl_key, $current_customizer_url );

                                    } else {
                                        \remove_submenu_page( $main_lvl_key, $submenu_item );
                                    }
                                }
                            } else {
                                \remove_menu_page( $menu_page );
                            } // End if().
                        } // End foreach().
                        // If only one item to be removed.
                    } elseif ( is_string( $menu_pages ) ) {
                        \remove_menu_page( $menu_pages );
                        // Removed menu page isn't valid.
                    } else {
                        return false;
                    }
                } else {
                    error_log( 'remove_role_menu_pages called without valid $menu_pages' );
                }
            }
        });

        // Handle related wp_admin_bar items automatically.
        add_action( 'wp_before_admin_bar_render', function() use ( $menu_pages ) {

            global $wp_admin_bar;

            $nodes = $wp_admin_bar->get_nodes();

            if ( ! empty( $nodes ) ) {
                foreach ( $nodes as $key => $node ) {

                    $splitted_href = explode( '/', $node->href );
                    $end_of_url    = end( $splitted_href );

                    $page_param_position = strpos( $end_of_url, '?page=' );

                    // If page parameter take the end of the string.
                    if ( $page_param_position ) {
                        $end_of_url_position = $page_param_position + strlen( '?page=' );
                        $end_of_url          = substr( $end_of_url, $end_of_url_position );
                    }

                    if ( self::in_array_r( $end_of_url, $menu_pages ) ) {
                        $wp_admin_bar->remove_node( $node->id );
                    }
                }
            }
        });
    }

    /**
     * Recursive in array function.
     *
     * @param string $needle String value to be fetched.
     * @param array $haystack Multidimensional array.
     *
     * @return boolean If value was found.
     */
    public static function in_array_r( $needle, $haystack ) {

        foreach ( $haystack as $item ) {
            if ( ( $item == $needle ) || ( is_array( $item ) && self::in_array_r( $needle, $item ) ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a user to the Super admin user list in WordPress Multisite.
     *
     * @param string $user_id User ID.
     */
    public static function grant_super_admin_cap( $user_id ) {
        grant_super_admin( $user_id );
    }

    /**
     * Adds WP Ga options settings
     */
    public static function add_options_page() {

        if ( is_admin() ) {

            // Run in admin_menu hook when called outside class
            add_action( 'admin_menu', function() {

                $menu_page_capability = apply_filters( 'geniem/roles/add_menu_page_cap', 'activate_plugins' );

                \add_menu_page(
                    __( 'Geniem Roles', 'wp-geniem-roles' ), // page title
                    __( 'Geniem Roles', 'wp-geniem-roles' ), // menu title
                    $menu_page_capability,
                    'wp-geniem-roles',                       // menu slug
                    array( __CLASS__, 'geniem_roles_html' ), // render function
                    'dashicons-universal-access',
                    80
                );

                \add_submenu_page(
                    'wp-geniem-roles',                                   // parent menu slug
                    __( 'Geniem Roles: Menu slugs', 'wp-geniem-roles' ), // page title
                    __( 'Menu slugs', 'wp-geniem-roles' ),               // menu title
                    $menu_page_capability,
                    'wp-geniem-roles-slugs',                             // menu slug
                    array( __CLASS__, 'geniem_roles_slug_html' )         // render function
                );
            });
        }
    }

    /**
     * Geniem roles printable html.
     */
    public static function geniem_roles_html() {

        global $wp_roles;

        echo '<div class="geniem-roles">';
        echo '<h1 class="dashicons-before dashicons-universal-access"> ' . esc_html__( 'Geniem roles', 'geniem-roles' ) . '</h1>';
        echo '<p>' . esc_html__( 'This page lists all current roles and their enabled capabilities.', 'geniem-roles' ) . '</p>';

        // Do not list cap if in $legacy_caps
        $legacy_caps = [
            'level_10',
            'level_9',
            'level_8',
            'level_7',
            'level_6',
            'level_5',
            'level_4',
            'level_3',
            'level_2',
            'level_1',
            'level_0',
        ];

        if ( ! empty( $wp_roles->roles ) ) {

            echo '<div class="geniem-roles__wrapper">';

            // Roles
            foreach ( $wp_roles->roles as $roles_slug => $role ) {

                // Single role wrap
                echo '<div class="geniem-roles__single-role">';

                // Name
                echo '<h2>' . esc_html( $role['name'] ) . '</h2>';

                // Caps
                echo '<ul>';
                if ( ! empty( $role['capabilities'] ) ) {
                    foreach ( $role['capabilities'] as $key => $value ) {

                        $formated_cap = \str_replace( '_', ' ', $key );

                        if ( ! in_array( $key, $legacy_caps ) && $value !== false ) {
                            echo '<li>' . esc_html( $formated_cap ) . '</li>';
                        }
                    }
                }
                echo '</ul>';
                echo '</div>'; // geniem-roles__single-role

            } // foreach ends
        }
        echo '</div>';
        echo '<div>'; // wrapper ends
    }

    /**
     * Geniem roles menu items slug list.
     */
    public static function geniem_roles_slug_html() {

        $menu_list = self::get_menu_list();

        echo '<div class="geniem-roles">';
        echo '<h1 class="dashicons-before dashicons-universal-access"> ' . esc_html__( 'Geniem roles', 'geniem-roles' ) . '</h1>';
        echo '<p>' . esc_html__( 'This page lists all admin menu slugs.', 'geniem-roles' ) . '</p>';
        echo '<div class="geniem-roles__wrapper">';
        echo '<div class="geniem-roles__slugs">';
        echo '<table>';

        foreach ( $menu_list as $menu ) {
            echo '<tr>';
            echo '<td>' . esc_html( $menu->label ) . '</td>';
            echo '<td>' . esc_html( $menu->path ) . '</td>';
            echo '</tr>';

            foreach ( $menu->children as $child_menu ) {
                echo '<tr class="child-menu">';
                echo '<td>' . esc_html( $child_menu->label ) . '</td>';
                echo '<td>' . esc_html( $child_menu->path ) . '</td>';
                echo '</tr>';
            }
        }
        echo '</table>';

        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Get menu list.
     *
     * @return array An array of admin menu items.
     */
    public static function get_menu_list() {
        global $menu, $submenu;

        $menu_list = [];
        foreach ( $menu as $i => $menu_data ) {
            if ( $menu_data[0] ) {
                $parent_menu = (object) [
                    'label'    => strip_tags( $menu_data[0] ),
                    'path'     => $menu_data[2],
                    'children' => [],
                ];

                $menu_list[ $parent_menu->path ] = $parent_menu;
            }
        }
        foreach ( $submenu as $i => $menu_data ) {
            if ( array_key_exists( $i, $menu_list ) ) {
                $sub_menus = array_map( function( $menu ) {
                    $item = (object) [
                        'label' => strip_tags( $menu[0] ),
                        'path'  => $menu[2],
                    ];

                    return $item;

                }, $menu_data);
                $menu_list[ $i ]->children = $sub_menus;
            }
        }

        return $menu_list;
    }

    /**
     * Add function to map_meta_cap which disallows certain actions for role in specifed posts.
     *
     * @param string $name WP Role name.
     * @param array  $blocked_posts Blocked posts.
     * @param string $capability Capability which is disallowed for the user.
     */
    public static function restrict_post_edit( $name, $blocked_posts, $capability ) {

        $current_user = wp_get_current_user();

        // Add function to map_meta_cap which disallows certain actions for role in specifed posts.
        // Check if we need to restrict current user.
        if ( in_array( $name, $current_user->roles, true ) ) {

            /**
             * Map_meta_cap arguments.
             *
             * $caps (array) Returns the user's actual capabilities.
             * $cap (string) Capability name.
             * $user_id (int) The user ID.
             * $args (array) Adds the context to the cap. Typically the object ID.
             */
            \add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) use ( $blocked_posts, $name, $capability ) {
                // $args[0] is the post id.
                if ( $cap === $capability && in_array( $args[0], $blocked_posts, true ) ) {
                    // This is default Wordpress way to restrict access.
                    $caps[] = 'do_not_allow';
                }
                return $caps;
            }, 10, 4 );
        }
    }

    /**
     * Prevents user to create and manage users by the given user roles and capabilities.
     * Removes role from the admin side dropdowns if
     * 'edit_user' or 'promote_user' has been restricted.
     *
     * Example usage:
     * 'administrator' => [
     *     'add_user',
     *     'edit_user',
     *     'delete_user',
     *     'remove_user',
     * ],
     *
     * @param string $name Name of the role.
     * @param array  $removed_user_caps_by_role Associative array of role specific restricted caps.
     */
    public static function restrict_user_management_by_role( $name, $removed_user_caps_by_role ) {

        // Get current user.
        $current_user       = wp_get_current_user();
        $current_user_roles = $current_user->roles;

        // Remove restricted role from the role lists in the admin side.
        \add_filter( 'editable_roles', function ( $roles ) use ( $name, $removed_user_caps_by_role, $current_user_roles ) {

            // If current users role is the smae as the edited one.
            if ( in_array( $name, $current_user_roles, true ) ) {

                // Loop through restricted user roles.
                foreach ( $removed_user_caps_by_role as $role => $restricted_caps ) {

                    $edit_user_caps = [
                        'edit_user',
                        'promote_user',
                    ];

                    // Loop through restricted caps.
                    foreach ( $restricted_caps as $cap ) {
                        if ( in_array( $cap, $edit_user_caps ) && isset( $roles[ $role ] ) ) {
                            // Unset the role from editable roles.
                            unset( $roles[ $role ] );
                        }
                    }
                } // End foreach().
            } // End if().

            return $roles;

        }); // End filter editable_roles.

        // If current users role is the same as the edited one.
        if ( in_array( $name, $current_user_roles, true ) ) {

            // Restrict user to manage users with given $removed_user_caps_by_role.
            \add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) use ( $name, $removed_user_caps_by_role ) {

                // Loop through the roles and their caps.
                foreach ( $removed_user_caps_by_role as $role => $restricted_caps ) {

                    // Check that array of caps have been assigned to the role.
                    if ( ! empty( $restricted_caps ) && is_array( $restricted_caps ) ) {

                        // Loop through roles restricted capabilities.
                        foreach ( $restricted_caps as $restricted_cap ) {

                            // Map meta cap switch case for user capabilities.
                            if ( $cap === $restricted_cap ) {

                                // Currently edited user.
                                $edited_user = new \WP_User( absint( $args[0] ) );

                                if ( in_array( $role, $edited_user->roles ) ) {
                                    $caps[] = 'do_not_allow';
                                }
                            } // End if().
                        } // End foreach().
                    } // End if().
                } // End foreach().

                return $caps;

            }, 10, 4 ); // End map_meta_cap.
        } // End if().
    }

    /**
     * Helper function reset default WordPress roles.
     */
    public static function reset_to_default_roles() {
        require_once( ABSPATH . 'wp-admin/includes/schema.php' );
        \populate_roles();
    }

    /**
     * Reset roles from the database.
     * Run this before your role changes on your theme.
     *
     * @return void
     */
    public static function reset_roles() {

        global $wp_roles;

        foreach ( $wp_roles->roles as $role_name => $role ) {
            \remove_role( $role_name );
        }

        // Create and define WordPress default roles.
        Roles::reset_to_default_roles();
    }
}

/**
 * Class Role which handles a intance of a single editable role
 */
class Role {

    /**
     * Role name for role identification.
     *
     * @var string Role name.
     */
    public $name;

    /**
     * Role display name shown for the admin user.
     *
     * @var string Role display name.
     */
    public $display_name;

    /**
     * Role capabilities.
     *
     * @var array
     */
    public $capabilities;

    /**
     * Get default caps for roles
     */
    public static function get_default_caps() {

        $defaults = array(

            // Network (Super Admin)
//            'create_sites'              => false,
//            'delete_sites'              => false,
//            'manage_network'            => false,
//            'manage_sites'              => false,
//            'manage_network_plugins'    => false,
//            'manage_network_themes'     => false,
//            'manage_network_options'    => false,
//            'manage_network_users'      => false,

            // CSS
//            'edit_css'                  => false,

            /** ------------------------------------------------------
             *  Users
             *
             *  On network installation user creation needs also
             *  site option add_new_user.
             *
             *  The setting can be found from the wp-admin
             *  https://sitedomain.com/wp-admin/network/settings.php
             *  ------------------------------------------------------ */

            'add_users'                 => false,
            'create_users'              => false,
            'delete_users'              => false,
            'edit_users'                => false,
            'list_users'                => false,
            'promote_users'             => false,

            // WordPress default capabilities
            'activate_plugins'          => false,
            'delete_others_pages'       => false,
            'delete_others_posts'       => false,
            'delete_pages'              => false,
            'delete_posts'              => false,
            'delete_private_pages'      => false,
            'delete_private_posts'      => false,
            'delete_published_pages'    => false,
            'delete_published_posts'    => false,
            'edit_dashboard'            => false,
            'edit_others_pages'         => false,
            'edit_others_posts'         => false,
            'edit_pages'                => false,
            'edit_posts'                => false,
            'edit_private_pages'        => false,
            'edit_private_posts'        => false,
            'edit_published_pages'      => false,
            'edit_published_posts'      => false,
            'edit_theme_options'        => false,
            'export'                    => false,
            'import'                    => false,
            'manage_categories'         => false,
            'manage_links'              => false,
            'manage_options'            => false,
            'moderate_comments'         => false,
            'publish_pages'             => false,
            'publish_posts'             => false,
            'read_private_pages'        => false,
            'read_private_posts'        => false,
            'read'                      => false,
            'remove_users'              => false,
            'switch_themes'             => false,
            'upload_files'              => false,
        );

        // filter default caps
        return apply_filters( 'geniem/roles/default_roles', $defaults );
    }

    /**
     * Role constructor.
     *
     * @param string $name Role name.
     * @param string $display_name Display name for the role.
     */
    public function __construct( $name, $display_name ) {

        // Get WordPress role.
        $role       = \get_role( $name );

        // Get role
        if ( $role ) {
            // Set role properties..
            $this->capabilities = $role->capabilities;
            $this->name         = $role->name;
            $this->display_name = $display_name;

            // Create a new role and set role properties.
        } else {
            $this->capabilities = self::get_default_caps();
            $this->name         = $name;
            $this->display_name = $display_name;
        }
    }

    /**
     * Remove a role.
     */
    public function remove() {
        Roles::remove_role( $this->name );
    }

    /**
     * Remove menu pages.
     *
     * @param array $menu_pages Mixed array of removable admin menu items.
     * Array value can be a string or
     * assoaciative array item 'parent_slug' => [ 'submenu_item1_slug', 'submenu_item2_slug' ].
     */
    public function remove_menu_pages( $menu_pages ) {
        Roles::remove_menu_pages( $this->name, $menu_pages );
    }

    /**
     * Add capabilities for a role
     * Makes db changes do not run everytime.
     *
     * @param array $caps An array of capabilities.
     */
    public function add_caps( $caps ) {
        Roles::add_caps( $this->name, $caps );
    }

    /**
     * Remove capabilities for a role
     * Makes db changes do not run everytime.
     *
     * @param array $caps An array of capabilities to be removed.
     */
    public function remove_caps( $caps ) {
        Roles::remove_caps( $this->name, $caps );
    }

    /**
     * Get all caps from a role.
     */
    public function get_caps() {
        return \get_role( $this->name )->capabilities;
    }

    /**
     * Rename a role.
     *
     * @param string $new_display_name Display name for a role.
     */
    public function rename( $new_display_name ) {
        return Roles::rename( $this->name, $new_display_name );
    }

    /**
     * Restrict post editing capabilities by post ids.
     *
     * @param string $blocked_posts An array of blocked post ids.
     * @param string $capability Capability to restrict for the role.
     */
    public function restrict_post_edit( $blocked_posts, $capability ) {
        return Roles::restrict_post_edit( $this->name, $blocked_posts, $capability );
    }

    /**
     * Restrict user management capabilities for the given role object.
     *
     * Example usage:
     * 'administrator' => [
     *     'add_user',
     *     'edit_user',
     *     'delete_user',
     *     'remove_user',
     * ],
     *
     * @param array $removed_user_caps_by_role Associative array of role specific restricted caps.
     */
    public function restrict_user_management_by_role( $removed_user_caps_by_role ) {
        return Roles::restrict_user_management_by_role( $this->name, $removed_user_caps_by_role );
    }

}

/**
 * Returns the Geniem Roles singleton.
 *
 * @return object
 */
function roles() {
    return Roles::instance();
}

// Create Geniem role singleton.
roles();
