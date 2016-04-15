<?php

namespace Cgit\Sonic;

class Video
{
    /**
     * Initial URI extracted from the code supplied
     *
     * @var string URI
     */
    private $input;

    /**
     * Video URI on the web
     *
     * @var string
     */
    public $uri;

    /**
     * Video embed URI used in iframes
     *
     * @var string
     */
    public $embedUri;

    /**
     * Video thumbnail image
     *
     * @var string
     */
    public $image;

    /**
     * Video HTML embed code
     *
     * @var string
     */
    public $embed;

    /**
     * Video HTML link with thumbnail image
     *
     * @var string
     */
    public $link;

    /**
     * Constructor
     *
     * @param string $code Anything that looks like a video embed code
     */
    public function __construct($code)
    {
        $this->update($code);
    }

    /**
     * Update video data
     *
     * @param string $code
     *
     * @return bool
     */
    public function update($code)
    {
        // If embed code supplied, extract URI
        $pattern = '/.*<iframe.*src=["\']([^"\']+)["\'].*/i';
        $uri = preg_replace($pattern, '$1', $code);
        $method = false;

        // Check input URI for signs of known video services
        if (preg_match('/vimeo\.com/i', $uri)) {
            $method = 'updateVimeo';
        } elseif (preg_match('/youtu\.?be(\.com)?/i', $uri)) {
            $method = 'updateYoutube';
        }

        // If there is no known video service, do nothing
        if (!$method) {
            return false;
        }

        // Reset defaults and update values
        $this->reset();
        $this->input = $uri;
        $this->$method();
        $this->updateEmbed();

        return true;
    }

    /**
     * Reset all properties
     *
     * @return void
     */
    private function reset()
    {
        $this->input = null;
        $this->uri = null;
        $this->embedUri = null;
        $this->image = null;
        $this->embed = null;
        $this->link = null;
    }

    /**
     * Update Vimeo video
     *
     * @return void
     */
    private function updateVimeo()
    {
        $id = preg_replace('/.*\/(\w+)/', '$1', $this->input);
        $image_path = 'http://vimeo.com/api/v2/video/' . $id . '.php';
        $image_file = $file = file_get_contents($image_path);

        $this->uri = '//player.vimeo.com/video/' . $id;
        $this->embedUri = $this->uri;

        if ($image_file) {
            $this->image = unserialize($image_file)[0]['thumbnail_large'];
        }
    }

    /**
     * Update YouTube video
     *
     * @return void
     */
    private function updateYoutube()
    {
        $id = preg_replace('/.*[\/=](\w+)/', '$1', $this->input);

        $this->uri = '//www.youtube.com/watch?v=' . $id;
        $this->embedUri = '//www.youtube.com/embed/' . $id;
        $this->image = '//i.ytimg.com/vi/' . $id . '/hqdefault.jpg';
    }

    /**
     * Update embed code and link
     *
     * @return void
     */
    private function updateEmbed()
    {
        $this->embed = '<iframe src="' . $this->embedUri
            . '" frameborder="0" allowfullscreen></iframe>';
        $this->link = '<a href="' . $this->uri . '"><img src="'. $this->image
            . '" alt="" /></a>';
    }
}
