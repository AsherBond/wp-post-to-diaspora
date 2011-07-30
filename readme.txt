=== WP Post to Diaspora ===
Contributors:
Tags: posts, diaspora
Tested up to: 3.1.3
Requires at least: 3.0

Shares Wordpress posts to your Diaspora account.

== Description ==

Shares Wordpress posts to your Diaspora account from the Add New Post and Edit Post pages.
Click on the asterik icon above the publish button to post your blog entry to Diaspora.  The entry
will shared when Wordpress publishes it.

At this point the plugin will not work until the as_note branch of Diaspora is merged.

== Installation ==

1.  Create a directory wp-post-to-diaspora in wp-content/plugins.  Upload the contents of this plugin
    into wp-content/plugins/wp-post-to-diaspora.
2.  Download a copy of libdiaspora-php from https://github.com/untitaker/libdiaspora-php/archives/master.
    Extract the file into a wp-content/plugins/wp-post-to-diaspora/libraries directory.  Rename the
    untitaker-libdiaspora-php-<commit> directory so that it reads libdiaspora-php.
    (As a todo item get the plugin to detect that the library is missing, download and extract it to the
    correct location.)
3.  Navigate to Settings, Post to Diaspora.
4.  At minimum fill in the Diaspora ID and Access Token fields. (Note:  The WordPress needs to be
    a pre-registered application to Diaspora.  It is unknown how to do that.)
5.  Click Save Changes.

== Frequently Asked Questions ==

= Why doesn't this plugin work? =

It depends on an as_note branch of the Diaspora project.  It is a work-in-progress and is not
merged into the codebase that all pods (servers) use.

= What work is remaining? =

1. Allow for custom structure of post.
2. The JSON structure that Diaspora accepts is rapidly changing.  I [Maxwell] will be updating it
ASAP. 

