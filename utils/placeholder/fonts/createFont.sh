#!/bin/bash
#
# Generate the 10x10 hex font from a folder of 8x16 glyphs A-F.
#
set -exu -o pipefail
rm -Rf tmp/
cp -R src tmp/
mogrify -bordercolor white -border 1x1 tmp/*.pbm
convert +append tmp/*.pbm -crop 160x10+0+5 +repage 10x10hex.pbm
rm -Rf tmp/
