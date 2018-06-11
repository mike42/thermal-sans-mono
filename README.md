# Thermal Sans Mono

[![Build Status](https://travis-ci.org/mike42/thermal-sans-mono.svg?branch=master)](https://travis-ci.org/mike42/thermal-sans-mono)

**Thermal Sans Mono** is a set of specialized free bitmap fonts for use on
thermal receipt printers and printer emulators.

It is being produced in two sizes.

- 12x24
- 9x17

These are chosen to correspond with hardware bitmap sizes, while the boldness
and glyph dimensions are selected for use at around 180DPI.

As Thermal Sans Mono is derived from GNU Unifont, it is licensed under the
GNU General Public License.

## Work in progress

There is **no release** available yet for this font. Check back soon.

## Build process

The glyphs are assembled from a variety of methods, with the build process for
each glyph set under `defintions/`.

- Tracing a Unifont glyph and then re-drawing it with the specified metrics.
- Scaling a raster
- Generating an image with a script, as in the case of placeholders.

A `Makefile` is produced from the definitions with the `./configure` script.

```
./configure
make -j
```

The dependencies of the build are currently strange and numerous, including font
sources, scripting languages, and tracing tools.

