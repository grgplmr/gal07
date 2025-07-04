(function( $ ) {
    // Initialisation Masonry après chargement des images
    $( function() {
        var galleries = $( '.masonry-gallery' );
        galleries.each( function() {
            var $gal = $( this );
            $gal.imagesLoaded( function() {
                $gal.masonry({
                    itemSelector: '.masonry-item',
                    percentPosition: true
                });
            });
        } );
    } );

    // Admin : sélection et tri des images
    $( document ).on( 'click', '#mpg-add-images', function( e ) {
        e.preventDefault();
        var frame = wp.media({
            title: mpg_gallery.select,
            button: { text: mpg_gallery.use },
            multiple: true
        });
        frame.on( 'select', function() {
            var ids = [];
            var list = $( '#mpg-images' ).empty();
            frame.state().get( 'selection' ).map( function( attachment ) {
                attachment = attachment.toJSON();
                ids.push( attachment.id );
                list.append( '<li class="mpg-image" data-id="' + attachment.id + '"><img src="' + attachment.sizes.thumbnail.url + '" alt="" /><span class="dashicons dashicons-no-alt mpg-remove" title="'+mpg_gallery.remove+'"></span></li>' );
            } );
            $( '#mpg-images-input' ).val( ids.join( ',' ) );
            list.sortable({
                update: function() {
                    var order = [];
                    list.find( 'li' ).each( function() {
                        order.push( $( this ).data( 'id' ) );
                    } );
                    $( '#mpg-images-input' ).val( order.join( ',' ) );
                }
            });
        });
        frame.open();
    } );

    $( document ).on( 'click', '.mpg-remove', function(){
        var li = $( this ).closest( 'li' );
        li.remove();
        var order = [];
        $( '#mpg-images li' ).each( function(){
            order.push( $( this ).data( 'id' ) );
        });
        $( '#mpg-images-input' ).val( order.join( ',' ) );
    });
})( jQuery );

// Enregistrement du bloc Gutenberg
( function( wp ) {
    if ( ! wp || ! wp.blocks ) {
        return;
    }
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody, RangeControl, ToggleControl, SelectControl } = wp.components;
    const { __ } = wp.i18n;
    const { useSelect } = wp.data;
    const { Fragment } = wp.element;

    registerBlockType( 'masonry/gallery', {
        title: __( 'Masonry Gallery', 'masonry-photo-gallery' ),
        icon: 'format-gallery',
        category: 'media',
        attributes: {
            id: { type: 'number' },
            columns: { type: 'number', default: 3 },
            gutter: { type: 'number', default: 10 },
            manual: { type: 'boolean', default: true }
        },
        edit: ( props ) => {
            const { attributes, setAttributes } = props;
            const { id, columns, gutter, manual } = attributes;

            const galleries = useSelect( ( select ) => {
                return select( 'core' ).getEntityRecords( 'postType', 'masonry_gallery', { per_page: -1 } );
            }, [] );

            const images = useSelect( ( select ) => {
                if ( ! id ) return [];
                const post = select( 'core' ).getEntityRecord( 'postType', 'masonry_gallery', id );
                if ( ! post || ! post.meta ) return [];
                const ids = post.meta._masonry_gallery_images ? post.meta._masonry_gallery_images.split(',').map( Number ) : [];
                return ids.map( ( imgId ) => select( 'core' ).getMedia( imgId ) );
            }, [ id ] );

            return (
                <Fragment>
                    <InspectorControls>
                        <PanelBody title={ __( 'Options', 'masonry-photo-gallery' ) }>
                            <SelectControl
                                label={ __( 'Galerie', 'masonry-photo-gallery' ) }
                                value={ id }
                                options={ [ { label: __( 'Sélectionner', 'masonry-photo-gallery' ), value: 0 } ].concat( ( galleries || [] ).map( ( g ) => ( { label: g.title.rendered, value: g.id } ) ) ) }
                                onChange={ ( value ) => setAttributes( { id: parseInt( value, 10 ) } ) }
                            />
                            <ToggleControl
                                label={ __( 'Utiliser le tri manuel', 'masonry-photo-gallery' ) }
                                checked={ manual }
                                onChange={ ( val ) => setAttributes( { manual: val } ) }
                            />
                            <RangeControl
                                label={ __( 'Colonnes', 'masonry-photo-gallery' ) }
                                value={ columns }
                                onChange={ ( val ) => setAttributes( { columns: val } ) }
                                min={ 1 }
                                max={ 6 }
                            />
                            <RangeControl
                                label={ __( 'Espacement', 'masonry-photo-gallery' ) }
                                value={ gutter }
                                onChange={ ( val ) => setAttributes( { gutter: val } ) }
                                min={ 0 }
                                max={ 50 }
                            />
                        </PanelBody>
                    </InspectorControls>
                    <div className="masonry-gallery" style={ { '--mpg-columns': columns, '--mpg-gutter': gutter + 'px' } }>
                        { images && images.length ? images.map( ( img ) => {
                            if ( ! img ) return null;
                            return <img key={ img.id } src={ img.media_details.sizes.thumbnail.source_url } alt={ img.alt_text } className="masonry-item" />;
                        } ) : __( 'Sélectionnez une galerie', 'masonry-photo-gallery' ) }
                    </div>
                </Fragment>
            );
        },
        save: () => null
    } );
} )( window.wp );

