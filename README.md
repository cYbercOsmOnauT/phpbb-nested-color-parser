# Nested Color Parser Extension for phpBB
phpBB 3.1 does not allow nested color tags. For example `[color=#ff0000]Hello [color=#00ff00]there[/color]![/color]` does not work. The first opener and the first closing bbcode are taken coloring everything from *Hello* to *there* in red leaving the second `[color=#00ff00]` and the most right `[/color]` unparsed.


### Some remarks for other coders
* I am no pro phpBB extensions coder. My stuff mostly is created on customer request and by looking inside other extensions and the phpBB Developers Wiki.
* I used static methods because I was not able to connect to the **bbcode** class which has no *namespace* inside my **parser** class that has one. This is also the reason why I have `$this` as an argument. I know very well that this is not really a fine way of coding but it works. If someone knows a better way feel free to send a pull request or contact me.
* This is also the reason why there is a call of `bbcode_second_pass_color_close` inside the `preg` instead of the simple `str_replace` which is used inside the **bbcode** class.
* The `get_tpl_parts` method is used to be sure to get the correct *color bbcode*  and split it into the opener and closer.