# Castlegate IT Sonic Screwdriver #

The Castlegate IT Sonic Screwdriver plugin is a collection of utilities for making WordPress theme development a bit easier. Strictly speaking, it does not have to be used with WordPress; it could also be used with any front end development project.

## Static methods ##

The `Cgit\Sonic` class provides a number of static methods to solve common front end problems:

*   `Sonic::contains($obj, $term)`. Does `$obj` contain `$term`? Works with strings and arrays.

*   `Sonic::startsWith($obj, $term)`. Does `$obj` start with `$term`? Works with strings and arrays.

*   `Sonic::endsWith($obj, $term)`. Does `$obj` end with `$term`? Works with strings and arrays.

*   `Sonic::currentUri()`. Returns the URI of the current page.

*   `Sonic::dataUri($file, $type = false)`. Returns a base64-encoded data URI from the file found at `$file`. If the media type `$type` is not specified, it will try to determine the type from the file extension.

*   `Sonic::formatUri($str, $human = false)`. Formats a URI-like string to be a valid URI. If `$human` is true, it removes the scheme from the URI.

*   `Sonic::formatLink($str, $text = false)`. Returns a HTML link based on a URI-like string and with the content `$text`. If `$text` is not specified, a human-readable version of the URI is used instead.

*   `Sonic::telephoneLink($tel, $text = false)`. Returns a HTML telephone link using the `tel:` protocol with the content `$text`. If `$text` is not specified, the original telephone number string is used. Telephone numbers with spaces and parenthesis are accepted and converted if required.

*   `Sonic::formatTelephone($tel)` Returns a `tel:` protocol friendly telephone number, for use in links. Parenthesis and spaces are removed.

*   `Sonic::normalizeHeadings($content, $limit = 2)`. Promote or demote headings to fit with the surrounding document outline.

*   `Sonic::timeSince($time, $suffix = 'ago', $now = 'Just now')`. Returns a human-readable description of the time since the Unix timestamp `$time`, followed by `$suffix`. For example: "3 minutes ago" or "4 years ago". Returns `$now` if `$time` is the current time.

*   `Sonic::ordinal($number)`. Returns a number with its ordinal suffix, such as "1st", "2nd", or "23rd".

*   `Sonic::truncate($str, $limit, $after = ' &#8230;')`. Truncates a string to `$limit` characters and appends `$after` if the string has been truncated. Avoids breaking words.

*   `Sonic::truncateWords($str, $limit, $after = ' &#8230;')`. Truncates a string to `$limit` words and appends `$after` if the string has been truncated.

*   `Sonic::formatAttributes($atts)`. Returns a string containing HTML attributes based on an associative array. Supports nested arrays, which are converted to space-separated attribute values.

## Posts ##

The `Cgit\Sonic\Post` provides slightly easier access to the final, filtered values of various WordPress post properties, including content and excerpts. It works inside or outside the loop. The constructor requires the post ID:

~~~ php
use Cgit\Sonic\Post;

$sonic_post = new Post(16);

echo $sonic_post->id;
echo $sonic_post->title;
echo $sonic_post->url;
echo $sonic_post->content;
echo $sonic_post->excerpt;
~~~

It also provides access to the raw `WP_Post` object via the `post` property.

## Images ##

The `Cgit\Sonic\Image` class provides a slightly more consistent way of accessing images, featured images, and ACF image fields. The constructor lets you specify the image (or post) ID:

~~~ php
use Cgit\Sonic\Image;

$image = new Image($image_id); // use image with ID
$image = new Image($post_id); // use featured image for post with ID
$image = new Image(); // try to use featured image for current $post
~~~

You can also specify the image after creating the instance:

~~~ php
$image->useImage($image_id); // use image
$image->usePost($post_id); // use featured image from post
$image->useField($field_name, $post_id); // use ACF image field
~~~

Getting image data:

~~~ php
$image->getUrl($size); // get image URL (size optional)
$image->getMeta(); // get all image data (alt, caption, etc.)
$image->getMeta('alt'); // get single image data field
$image->getElement($size, $atts); // get HTML <img> or <picture>
~~~

The following metadata is available:  url, file_name, file_path, mime_type, title, alt, caption, description.

Example `<img>` element:

~~~ php
echo $image->getElement('medium', [
    'alt' => 'Example image',
    'class' => 'example',
]);
~~~

Example responsive `<picture>` image element, where the size keys are the image sizes and the size values are the corresponding media queries:

~~~ php
echo $image->getElement([
    'medium' => '(max-width: 480px)',
    'large' => '(max-width: 960px)',
], [
    'alt' => 'Example image',
    'class' => 'example',
]);
~~~

## Videos ##

The plugin provides `Cgit\Sonic\Videos` to handle embedding videos. The purpose of this class is to take an uncertain input and return a predictable result.

~~~ php
use Cgit\Sonic\Video;

$video = new Video($youtube_video_uri);

echo $video->id; // Video ID used by YouTube or Vimeo
echo $video->uri; // Full URI of the video on the web
echo $video->embedUri; // URI of the video used in iframes
echo $video->image; // Thumbnail image for the video
echo $video->embed; // Full embed code (HTML iframe)
echo $video->link; // Thumbnail image linked the full video
~~~

The constructor accepts any reasonable YouTube or Vimeo URI format or `<iframe>` embed code (i.e. anything a user might paste into a custom field). You can change the input string using the `$video->update($new_input_string)` method and all the instance properties will update automatically.

## Dates and times ##

The `Cgit\Sonic\DateTime` class provides some convenient ways of interpreting and formatting dates and date ranges. The constructor takes one or two arguments; if two arguments are provided, they are assumed to be the start and end times in a range of times. If integers are provided, they are assumed to be Unix times; otherwise, the input will be converted to a time via `strtotime()`.

~~~ php
use Cgit\Sonic\DateTime;

$date = new DateTime(1420070400); // create time from Unix time
$date = new DateTime(1420070400, 1420071000); // create range from Unix times
$date = new DateTime('2015-01-01'); // create time from string
$date = new DateTime('2015-01-01', '2015-01-02'); // create range from string
~~~

You can also set the time, or the start and end times using methods:

~~~ php
$date->set($start);
$date->set($start, $end);
$date->setStart($start);
$date->setEnd($end);
~~~

You can return the values in their default formats:

~~~ php
$date->get();
$date->getStart();
$date->getEnd();
$date->getRange(); // return a range of times or dates
$date->getInterval(); // return the interval between the times
~~~

The default formats can be modified:

~~~ php
$date->setFormat('j F Y'); // single date format
$date->setRangeFormats([
    'time'  => ['H:s',   '&ndash;', 'H:s d F Y'],
    'day'   => ['d',     '&ndash;', 'd F Y'    ],
    'month' => ['d F',   '&ndash;', 'd F Y'    ],
    'year'  => ['d F Y', '&ndash;', 'd F Y'    ],
]);
~~~

Each array in the range formats represents the start date, the string to separate the two times, and the end date. In addition to the usual [PHP date formats](http://php.net/manual/en/function.date.php), you can use the string `MySQL` to get a string suitable for MySQL `DATETIME` fields.
