<?php

namespace Cgit\Sonic;

class Post
{
    /**
     * Instance of the original WordPress post object
     *
     * @var WP_Post
     */
    public $post;

    /**
     * Post ID
     *
     * @var int
     */
    public $id;

    /**
     * Post title
     *
     * @var string
     */
    public $title;

    /**
     * Post permalink
     *
     * @var string
     */
    public $url;

    /**
     * Post content
     *
     * @var string
     */
    public $content;

    /**
     * Post excerpt
     *
     * @var string
     */
    public $excerpt;

    /**
     * Constructor
     *
     * Sets the values of post-related properties to their final, filtered
     * versions instead of the raw values taken from the database.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->post = get_post($id);
        $this->title = get_the_title($id);
        $this->url = get_permalink($id);

        $this->updateContent();
        $this->updateExcerpt();
    }

    /**
     * Set post content
     *
     * Applies filters so that the HTML content is exactly the same as the
     * content output by the_content() in the loop.
     *
     * @return void
     */
    private function updateContent()
    {
        $this->content = apply_filters(
            'the_content',
            $this->post->post_content
        );
    }

    /**
     * Set post excerpt
     *
     * Attempts to use the manual excerpt for the post before falling back to an
     * excerpt generated from the main content. Unlike the_excerpt(), this will
     * work outside the loop. Unlike get_the_excerpt(), this will not fall back
     * to the excerpt of the current post object if the target post excerpt is
     * not found.
     *
     * This function uses the same process and the same default values as
     * WordPress to generate the excerpt. The same filters are applied to edit
     * the length of the excerpt and the "more" string.
     *
     * @return void
     */
    private function updateExcerpt()
    {
        $excerpt = $this->post->post_excerpt;
        $length = apply_filters('excerpt_length', 55);
        $more = apply_filters('excerpt_more', ' [&hellip;]');

        if (!$excerpt) {
            $excerpt = wp_trim_words($this->content, $length, $more);
        }

        $this->excerpt = apply_filters('the_excerpt', $excerpt);
    }
}
