
Update Diaspora from your Wordpress!


Bad things that are true because I don't actually know PHP:

- it still uses Diaspora's token auth.  We should switch to OAuth once it
gets rolled out.

- since it was a hack job from a twitter plugin, I only replaced what
  was absolutely necessary to change.



*****WISH LIST****

Do not store the Diaspora password as plain-text.

Allow for custom structure of post.

Anything to make this a better plugin.  I am just hacking hacking
hacking this away, so if you know anything about Wordpress plugins, you know better than I do.

Also, I'd love some info on better Wordpress development workflow.  This
was a pain to make!

** TO-DO ITEMS **

2.  Add field validation to the Post to Diaspora settings page.
3.  Add a Diaspora button on the Add New Post page that a user can click on to "Share with Diaspora"
    when the post is published to their blog.  Have it operate in a similiar manner that the
    "Share with <services>" buttons work within the Diaspora application.
4.  Add a Diaspora button on the Edit Post page that a user can click on to "Share with Diaspora"
    in the event that they did not do that on item 3.
5.  After items 3 and 4 are done, remove the "What are you doing?" section on the Post to Diaspora
    settings page.
6.  Add a warning to WordPress if they installed the plug-in but have not configured it.
7.  Add support for url shorteners that require API authentication (e.g., bit.ly).

***things I [Maxwell] will be fixing***
the json structure that Diaspora accepts is rapidly changing.  I will be
updating it ASAP.  Anything around that is up for grabs to make better.

I am going to be using this as soon as possible from my blog
http://blog.sourcedecay.net



based completely on:
http://www.skidoosh.co.uk/wordpress-plugins/wordpress-plugin-wp-post-to-twitter/


