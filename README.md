# Masonry Photo Gallery Plugin

This plugin adds a Masonry based gallery with a Gutenberg block.

## Building JavaScript

The uncompiled source is located in `src/masonry-photo-gallery.js`. The built file used by WordPress is `build/masonry-photo-gallery.js`.

To compile the script you need Node.js installed. Run:

```bash
npm install
npm run build
```

This uses `@wordpress/scripts` to transpile the source into the `build` directory.

