<?php

namespace Cgit\Sonic;

use Cgit\Sonic;

class Image
{
    /**
     * Image ID
     *
     * @var integer
     */
    private $id;

    /**
     * Image meta data
     *
     * @var array
     */
    private $meta;

    /**
     * Default post ID
     *
     * @var integer
     */
    private $defaultPostId = 0;

    /**
     * Valid image attributes
     *
     * @var array
     */
    private $validAttributes = [
        'alt',
        'class',
        'id',
        'style',
        'title',
    ];

    /**
     * Constructor
     *
     * Sets the default post ID to the current post and attempts to set the
     * image ID. If the featured option is true, it will attempt to use a post
     * thumbnail if it does not find an image with the ID provided.
     *
     * @param integer $id
     * @param boolean $featured
     * @return void
     */
    public function __construct($id = 0, $featured = true)
    {
        $this->setDefaultPostId();

        if (get_post_type($id) == 'attachment') {
            $this->useImage($id);
        } elseif ($featured) {
            $this->usePost($id);
        }
    }

    /**
     * Set default post ID to current post
     *
     * @return void
     */
    private function setDefaultPostId()
    {
        global $post;

        if ($post) {
            $this->defaultPostId = $post->ID;
        }
    }

    /**
     * Set image ID
     *
     * @param integer $id
     * @return void
     */
    public function useImage($id)
    {
        $this->id = intval($id);
        $this->updateMeta();
    }

    /**
     * Set image ID based on post featured image
     *
     * @param integer $id
     * @return void
     */
    public function usePost($id = 0)
    {
        if (!$id) {
            $id = $this->defaultPostId;
        }

        $this->useImage(get_post_thumbnail_id($id));
    }

    /**
     * Set image ID based on ACF custom field
     *
     * @param string $name
     * @param integer $post_id
     * @return void
     */
    public function useField($name, $id = 0)
    {
        if (!function_exists('get_field')) {
            return trigger_error('ACF not available');
        }

        if (!$id) {
            $id = $this->defaultPostId;
        }

        $image = get_field($name, $id);

        // The return value of the custom field might be the image ID or it
        // might be an array of data that includes that image ID.
        if (is_array($image)) {
            $image = $image['id'];
        }

        $this->useImage($image);
    }

    /**
     * Extract and store important image meta information
     *
     * @return void
     */
    private function updateMeta()
    {
        // Retrieve the raw post information from WordPress
        $obj = get_post($this->id);
        $obj_meta = get_post_meta($this->id);
        $obj_type = get_post_mime_type($this->id);
        $obj_file = $obj_meta['_wp_attached_file'][0];

        // Assign the relevant information to the instance
        $this->meta = [
            'url' => $this->getUrl(),
            'file_name' => basename($obj_file),
            'file_path' => wp_upload_dir()['basedir'] . '/' . $obj_file,
            'mime_type' => $obj_type,
            'title' => $obj->post_title,
            'alt' => $obj_meta['_wp_attachment_image_alt'][0],
            'caption' => $obj->post_excerpt,
            'description' => apply_filters('the_content', $obj->post_content),
        ];
    }

    /**
     * Sanitize HTML attributes
     *
     * When provided with an array of permitted attributes, removes any
     * attributes that are not permitted. Always allows HTML5 data-* attributes.
     *
     * @param array $atts
     * @param array $keys
     * @return array
     */
    private static function sanitizeAttributes($atts, $valid)
    {
        if (!$valid) {
            return $atts;
        }

        $sanitized = [];

        // Make sure only permitted attributes are present in the array
        foreach ($atts as $key => $value) {
            if (in_array($key, $valid) || strpos($key, 'data-') === 0) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get image URL
     *
     * @param string $size
     * @return string
     */
    public function getUrl($size = 'full')
    {
        return wp_get_attachment_image_src($this->id, $size)[0];
    }

    /**
     * Get image meta information
     *
     * @param string $field
     * @return mixed
     */
    public function getMeta($field = null)
    {
        if (is_null($field)) {
            return $this->meta;
        }

        return $this->meta[$field];
    }

    /**
     * Get image element
     *
     * If the first argument is a single size, this will create an HTML <img>
     * element. If it is an array of sizes and media queries, this will create a
     * responsive HTML <picture> element.
     *
     * @param string $size
     * @return string
     */
    public function getElement($size = 'full', $atts = [])
    {
        if (is_array($size)) {
            return $this->getResponsiveElement($size, $atts);
        }

        // Sanitize submitted attributes
        $atts = self::sanitizeAttributes($atts, $this->validAttributes);

        // Set image URL
        $atts['src'] = $this->getUrl($size);

        // Set alt text if it is not already set
        if (!isset($atts['alt'])) {
            $atts['alt'] = $this->getMeta('alt');
        }

        // Put the src and alt attributes at the beginning, then arrange the
        // others in alphabetical order.
        ksort($atts);
        $atts = ['alt' => $atts['alt']] + $atts;
        $atts = ['src' => $atts['src']] + $atts;

        return '<img ' . Sonic::formatAttributes($atts) . ' />';
    }

    /**
     * Get responsive image element
     *
     * @param array $sizes
     * @param array $atts
     * @return string
     */
    private function getResponsiveElement($sizes, $atts = [])
    {
        // List of source elements
        $sources = [];

        // Make sure that the alt text is in the array of image attributes, not
        // the array of picture element attributes.
        $picture_atts = array_diff_key($atts, ['alt' => 0]);
        $image_atts = [];

        if (isset($atts['alt'])) {
            $image_atts['alt'] = $atts['alt'];
        }

        ksort($picture_atts);

        // Assemble the list of source elements based on the sizes and media
        // queries submitted.
        foreach ($sizes as $size => $media) {
            $source_atts = [
                'srcset' => $this->getUrl($size),
                'media' => $media,
            ];

            $sources[] = '<source ' . Sonic::formatAttributes($source_atts)
                . ' />';
        }

        // Add an image element to the end of the list of sources, using the
        // last source size as the image size.
        $image_size = array_slice(array_keys($sizes), -1)[0];
        $sources[] = $this->getElement($image_size, $image_atts);

        // Assemble and return the HTML output
        return '<picture ' . Sonic::formatAttributes($picture_atts) . '>'
            . implode(PHP_EOL, $sources) . '</picture>';
    }
}
