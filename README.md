# Nested Color Parser Extension for phpBB
phpBB 3.1 does not allow nested color tags. For example `[color=#ff0000]Hello [color=#00ff00]there[/color]![/color]` does not work. The first opener and the first closing bbcode are taken coloring everything from *Hello* to *there* in red leaving the second `[color=#00ff00]` and the most right `[/color]` unparsed.


### Some remarks for other coders
* I am no pro phpBB extensions coder. My stuff mostly is created on customer request and by looking inside other extensions and the phpBB Developers Wiki.
* The `get_tpl_parts` method is used to be sure to get the correct *color bbcode*  and split it into the opener and closer.