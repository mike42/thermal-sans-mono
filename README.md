# Thermal Sans Mono

[![Build Status](https://travis-ci.org/mike42/thermal-sans-mono.svg?branch=master)](https://travis-ci.org/mike42/thermal-sans-mono)

**Thermal Sans Mono** is a set of specialized free bitmap fonts for use on
thermal receipt printers and printer emulators.

![Thermal Sans Mono 24](https://raw.githubusercontent.com/mike42/thermal-sans-mono/master/preview.png)

It is being produced in two sizes.

- 12x24
- 9x17

These are chosen to correspond with hardware bitmap sizes, while the boldness
and glyph dimensions are selected for use at around 180DPI.

Thermal Sans Mono is derived from GNU Unifont, and is licensed under the
GNU General Public License.

## Work in progress

See [releases](https://github.com/mike42/thermal-sans-mono/releases) for the most complete BDF and PCF font files currently available.

Many glyphs are not yet available for this font, consider running the build locally and adding definitions for glyphs that you need.

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
sources, scripting languages, and tracing tools. The entire setup is included in our [Travis CI build script](https://github.com/mike42/thermal-sans-mono/blob/master/.travis.yml).

## Contribute

Add new glyph definitons to the files under `defintions/`, and send a pull request. The definitions cover the process to produce each glyph:

- Placeholder: Draw the glyph number.
- Scale: Scale the corresponding glyph from GNU Unifont.
- Trace: Trace and re-draw the corresponding glyph from GNU Unifont.

## Related projects

Fonts:

- [GNU Unifont](http://unifoundry.com/unifont.html).

Utilities:

- [fontscale-util](https://github.com/mike42/fontscale-util) - low resolution bitmap tracer.
- [gfx-php](https://github.com/mike42/gfx-php) - graphics processing utility.

Printer libraries and emulators:

- [escpos-php](https://github.com/mike42/escpos-php) - library for generating output for thermal receipt printers.
- [escpos-tools](https://github.com/receipt-print-hq/escpos-tools) - command-line tools for converting thermal receipt printer binary files back to readable formats.
