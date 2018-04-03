# montage-sort

This utility is used to prepare commands to make a 'montage' of defined glyphs.

It sorts a file of inputs by code point, filling blanks with 'null:', so that we
have 65536 correctly-ordered inputs to ImageMagick's 'montage' command.

