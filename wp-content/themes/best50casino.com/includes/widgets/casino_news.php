<?php
/**
 * Widget API: WP_Widget_Recent_Posts class
 *
 * @package WordPress
 * @subpackage Widgets
 * @since 4.4.0
 */

/**
 * Core class used to implement a Recent Posts widget.
 *
 * @since 2.8.0
 *
 * @see WP_Widget
 */
add_action( 'widgets_init', function(){
    register_widget( 'Casino_News' );
});
class Casino_News extends WP_Widget {

    /**
     * Sets up a new Recent Posts widget instance.
     *
     * @since 2.8.0
     * @access public
     */
    public function __construct() {
        $widget_ops = array(
            'classname' => 'news_list',
            'description' => __( 'Your site&#8217;s most recent news.' ),
            'customize_selective_refresh' => true,
        );
        parent::__construct( 'recent-posts', __( 'Casino News' ), $widget_ops );
        $this->alt_option_name = 'news_list';
    }

    /**
     * Outputs the content for the current Recent Posts widget instance.
     *
     * @since 2.8.0
     * @access public
     *
     * @param array $args     Display arguments including 'before_title', 'after_title',
     *                        'before_widget', and 'after_widget'.
     * @param array $instance Settings for the current Recent Posts widget instance.
     */
    public function widget( $args, $instance ) {
        if ( ! isset( $args['widget_id'] ) ) {
            $args['widget_id'] = $this->id;
        }

        $title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Posts' );

        /** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
        $title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

        $number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
        if ( ! $number )
            $number = 5;
        $show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

        /**
         * Filters the arguments for the Recent Posts widget.
         *
         * @since 3.4.0
         *
         * @see WP_Query::get_posts()
         *
         * @param array $args An array of arguments used to retrieve the recent posts.
         */
        $r = new WP_Query( apply_filters( 'widget_posts_args', array(
            'post_type' => array( 'kss_news' ),
            'posts_per_page'      => $number,
            'no_found_rows'       => true,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'suppress_filters' => true,
        ) ) );

        if ($r->have_posts()) :
            ?>
            <?php echo $args['before_widget']; ?>
            <?php if ( $title ) {
            echo $args['before_title'] . $title . $args['after_title'];
        } ?>

            <?php while ( $r->have_posts() ) : $r->the_post(); ?>
            <?php
//		$typeCategory = get_post_meta($post->ID, "posts_custom_meta_cat_wid", true);
//		if (!$typeCategory){
//			$test = get_post_type_object(get_post_type());
//			$typeCategory = $test->label ;
//		}

            ?>
            <article class="mb-0 d-flex overflow-hidden align-items-center" style="border-bottom: 1px solid #cdcdcd;">
                <time class="timedate pl-7p pr-7p">
                    <?php echo get_the_date('d M'); ?>
                </time>
                <div style="display: contents; line-height: 1.2">
                    <a class="text-decoration-none pr-5p text-left mt-0 mb-0 text-13" style="color: #003238;" href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
                </div>
            </article>
        <?php endwhile; ?>

            <?php echo $args['after_widget']; ?>
            <?php
            // Reset the global $the_post as this query will have stomped on it
            wp_reset_postdata();

        endif;
    }

    /**
     * Handles updating the settings for the current Recent Posts widget instance.
     *
     * @since 2.8.0
     * @access public
     *
     * @param array $new_instance New settings for this instance as input by the user via
     *                            WP_Widget::form().
     * @param array $old_instance Old settings for this instance.
     * @return array Updated settings to save.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['number'] = (int) $new_instance['number'];
        $instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
        return $instance;
    }

    /**
     * Outputs the settings form for the Recent Posts widget.
     *
     * @since 2.8.0
     * @access public
     *
     * @param array $instance Current settings.
     */
    public function form( $instance ) {
        $title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
        $number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
        ?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo $number; ?>" size="3" /></p>

        <p><input class="checkbox" type="checkbox"<?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
            <label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display post date?' ); ?></label></p>
        <?php
    }
}
