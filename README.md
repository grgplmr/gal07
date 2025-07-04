# Masonry Photo Gallery

A WordPress plugin providing a simple way to create image galleries displayed with the Masonry layout. It registers a custom post type for galleries, offers a meta box to pick and order images, and exposes both a shortcode and a Gutenberg block.

## Features

- Responsive Masonry gallery layout
- Custom post type for managing galleries
- Manual image ordering via drag and drop
- Gutenberg block for visual editing
- Shortcode for use in classic editors

## Installation

1. Copy the plugin folder to `wp-content/plugins/masonry-photo-gallery`.
2. Inside the plugin directory, run `npm install` to install JavaScript dependencies.
3. Run `npm run build` to generate the compiled `masonry-photo-gallery.js` file.
4. Activate **Masonry Photo Gallery** from the WordPress admin.

## Usage

Insert a gallery with the `[masonry_gallery]` shortcode:

```
[masonry_gallery id="123" columns="4" gutter="20" manual="false"]
```

### Shortcode attributes

- `id` – ID of the gallery post to display (required)
- `columns` – number of columns to display (default: `3`)
- `gutter` – space in pixels between items (default: `10`)
- `manual` – `true` to keep manual order, `false` to sort automatically (default: `true`)

## License

Masonry Photo Gallery is released under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
