
beta Diapora plugin

update Diaspora from your hosted Diaspora account



Bad things that are true because I don't actually know PHP:
joindiaspora.com is hardcoded.

- it still uses Diaspora's token auth.  We should switch to Ouath once it
gets rolled out.

- since it was a hack job from a twitter plugin, I only replaced what
  was absoultely nessisary to change, so some internal variables have
names like "tweet".



*****WISH LIST****

make the settings ask for Diaspora handle(not username), so the server it posts to is
dynamic.  split on the server, and maybe you need an http or https
option?

give link shortener choices.

allow for custom structure of post

anything to make this a better plugin.  I am just hacking hacking
hacking this away, so you know better than I do.



Also, I'd love some info on better Wordpress Development workflow.  This
was a pain to make!


***things I will be fixing***
the json structure that Diaspora accepts is rapidly changing.  I will be
updating it ASAP.

I am going to be using this as soon as possible from my blog

http://blog.sourcedecay.net



based completely on:
http://www.skidoosh.co.uk/wordpress-plugins/wordpress-plugin-wp-post-to-twitter/


