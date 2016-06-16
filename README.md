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

## Videos ##

The plugin provides `Cgit\Sonic\Videos` to handle embedding videos. The purpose of this class is to take an uncertain input and return a predictable result.

~~~ php
use Cgit\Sonic\Video;

$video = new Video($youtube_video_uri);

echo $video->uri; // Full URI of the video on the web
echo $video->embedUri; // URI of the video used in iframes
echo $video->image; // Thumbnail image for the video
echo $video->embed; // Full embed code (HTML iframe)
echo $video->link; // Thumbnail image linked the full video
~~~

The constructor accepts any reasonable YouTube or Vimeo URI format or `<iframe>` embed code (i.e. anything a user might paste into a custom field). You can change the input string using the `$video->update($new_input_string)` method and all the instance properties will update automatically.
