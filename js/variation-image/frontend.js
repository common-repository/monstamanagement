/*!
 * Additional Variation Images Gallery for WooCommerce v1.1.36 
 * 
 * Author: Emran Ahmed ( emran.bd.08@gmail.com ) 
 * Date: 1/14/2020, 2:02:57 PM
 * Released under the GPLv3 license.
 */
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(1);
__webpack_require__(3);
__webpack_require__(4);
__webpack_require__(5);
__webpack_require__(6);
__webpack_require__(7);
module.exports = __webpack_require__(8);


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

jQuery(function ($) {
    Promise.resolve().then(function () {
        return __webpack_require__(2);
    }).then(function () {

        // For Single Product

        $('.woo-variation-gallery-wrapper:not(.woo-variation-gallery-product-type-variable)').WooVariationGallery();

        // Ajax and Variation Product
        $(document).on('wc_variation_form', '.variations_form', function () {
            $('.woo-variation-gallery-wrapper').WooVariationGallery();
        });

        // Support for Jetpack's Infinite Scroll,
        $(document.body).on('post-load', function () {
            $('.woo-variation-gallery-wrapper').WooVariationGallery();
        });
		
        // YITH Quickview
        $(document).on('qv_loader_stop', function () {
            $('.woo-variation-gallery-wrapper:not(.woo-variation-gallery-product-type-variable)').WooVariationGallery();
        });
    });
}); // end of jquery main wrapper

/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

// ================================================================
// WooCommerce Variation Gallery
/*global wc_add_to_cart_variation_params, woo_variation_gallery_options */
// ================================================================

var WooVariationGallery = function ($) {

    var Default = {};

    var WooVariationGallery = function () {
        function WooVariationGallery(element, config) {
            _classCallCheck(this, WooVariationGallery);
            // Assign
            this._el = element;
            this._element = $(element);
            this._config = $.extend({}, Default, config);

            this.$product = this._element.closest('.product');
            this.$variations_form = this.$product.find('.variations_form');
            this.$target = this._element.parent();
            this.$slider = $('.woo-variation-gallery-slider', this._element);
            this.$thumbnail = $('.woo-variation-gallery-thumbnail-slider', this._element);
            this.thumbnail_columns = this._element.data('thumbnail_columns');
            this.product_id = this.$variations_form.data('product_id');
            this.is_variation_product = this.$variations_form.length > 0;
            this.initial_load = true;

            // Call
            this.defaultGallery();

            if (!!woo_variation_gallery_options.enable_gallery_preload) {
                this.initVariationImagePreload();
            }

            this.initEvents();
            this.initVariationGallery();

            if (this.is_variation_product) {
                this.initSlick();
                this.initZoom();
                this.initPhotoswipe();
            }

            if (!this.is_variation_product) {
                this.imagesLoaded();
            }

            this._element.data('woo_variation_gallery', this);
            $(document).trigger('woo_variation_gallery_init', [this]);
        }

        _createClass(WooVariationGallery, [{
            key: 'init',
            value: function init() {
                var _this = this;

                return _.debounce(function () {
                    _this.initSlick();
                    _this.initZoom();
                    _this.initPhotoswipe();
                }, 500);
            }
        }, {
            key: 'dimension',
            value: function dimension() {

                //this._element.css('min-height', '0px');
                //this._element.css('min-width', '0px');

                //return _.debounce(() => {
                //this._element.css('min-height', this.$slider.height() + 'px');
                //this._element.css('min-width', this.$slider.width() + 'px');
                //}, 400);
            }
        }, {
            key: 'initEvents',
            value: function initEvents() {
                this._element.on('woo_variation_gallery_image_loaded', this.init());
            }
        }, {
            key: 'initSlick',
            value: function initSlick() {
                var _this2 = this;

                if (this.$slider.hasClass('slick-initialized')) {
                    this.$slider.slick('unslick');
                }

                this.$slider.off('init');
                this.$slider.off('beforeChange');
                this.$slider.off('afterChange');

                this._element.trigger('woo_variation_gallery_before_init', [this]);

                // Slider
                this.$slider.on('init', function (event) {
                    if (_this2.initial_load) {
                        _this2.initial_load = false;
                        // this._element.css('min-height', this.$slider.height() + 'px');
                    }
                }).on('beforeChange', function (event, slick, currentSlide, nextSlide) {

                    _this2.$thumbnail.find('.wvg-gallery-thumbnail-image').not('.slick-slide').removeClass('current-thumbnail');
                    _this2.$thumbnail.find('.wvg-gallery-thumbnail-image').not('.slick-slide').eq(nextSlide).addClass('current-thumbnail');
                }).on('afterChange', function (event, slick, currentSlide) {
                    _this2.stopVideo(_this2.$slider);
                    _this2.initZoomForTarget(currentSlide);
                }).slick();

                // Thumbnails

                this.$thumbnail.find('.wvg-gallery-thumbnail-image').not('.slick-slide').first().addClass('current-thumbnail');

                this.$thumbnail.find('.wvg-gallery-thumbnail-image').not('.slick-slide').each(function (index, el) {
                    $(el).find('div, img').on('click', function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        _this2.$slider.slick('slickGoTo', index);
                    });
                });

                _.delay(function () {
                    _this2._element.trigger('woo_variation_gallery_slider_slick_init', [_this2]);
                }, 1);

                _.delay(function () {
                    _this2.removeLoadingClass();
                }, 100);
            }
        }, {
            key: 'initZoomForTarget',
            value: function initZoomForTarget(currentSlide) {

                if (!woo_variation_gallery_options.enable_gallery_zoom) {
                    return;
                }

                var galleryWidth = parseInt(this.$target.width()),
                    zoomEnabled = false,
                    zoomTarget = this.$slider.slick('getSlick').$slides.eq(currentSlide);

                $(zoomTarget).each(function (index, target) {
                    var image = $(target).find('img');

                    if (parseInt(image.data('large_image_width')) > galleryWidth) {
                        zoomEnabled = true;

                        return false;
                    }
                });

                // If zoom not included.
                if (!$().zoom) {
                    return;
                }

                // But only zoom if the img is larger than its container.
                if (zoomEnabled) {
                    var zoom_options = $.extend({
                        touch: false
                    }, wc_single_product_params.zoom_options);

                    if ('ontouchstart' in document.documentElement) {
                        zoom_options.on = 'click';
                    }

                    zoomTarget.trigger('zoom.destroy');
                    zoomTarget.zoom(zoom_options);
                }
            }
        }, {
            key: 'initZoom',
            value: function initZoom() {
                var currentSlide = this.$slider.slick('slickCurrentSlide');
                this.initZoomForTarget(currentSlide);
            }
        }, {
            key: 'initPhotoswipe',
            value: function initPhotoswipe() {
                var _this3 = this;

                if (!woo_variation_gallery_options.enable_gallery_lightbox) {
                    return;
                }

                this._element.off('click', '.woo-variation-gallery-trigger');
                this._element.off('click', '.wvg-gallery-image a');

                this._element.on('click', '.woo-variation-gallery-trigger', function (event) {
                    _this3.openPhotoswipe(event);
                });

                this._element.on('click', '.wvg-gallery-image a', function (event) {
                    _this3.openPhotoswipe(event);
                });
            }
        }, {
            key: 'openPhotoswipe',
            value: function openPhotoswipe(event) {
                var _this4 = this;

                event.preventDefault();

                if (typeof PhotoSwipe === 'undefined') {
                    return false;
                }

                var pswpElement = $('.pswp')[0],
                    items = this.getGalleryItems();

                var options = $.extend({
                    index: this.$slider.slick('slickCurrentSlide')
                }, wc_single_product_params.photoswipe_options);

                // Initializes and opens PhotoSwipe.

                var photoswipe = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);

                // Gallery starts closing
                photoswipe.listen('close', function () {
                    _this4.stopVideo(pswpElement);
                });

                photoswipe.listen('afterChange', function () {
                    _this4.stopVideo(pswpElement);
                });

                photoswipe.init();
            }
        }, {
            key: 'stopVideo',
            value: function stopVideo(element) {
                $(element).find('iframe, video').each(function () {
                    var tag = $(this).prop("tagName").toLowerCase();
                    if (tag === 'iframe') {
                        var src = $(this).attr('src');
                        //   $(this).attr('src', src);
                    }

                    if (tag === 'video') {
                        $(this)[0].pause();
                    }
                });
            }
        }, {
            key: 'addLoadingClass',
            value: function addLoadingClass() {
                this._element.addClass('loading-gallery');
            }
        }, {
            key: 'removeLoadingClass',
            value: function removeLoadingClass() {
                this._element.removeClass('loading-gallery');
            }
        }, {
            key: 'getGalleryItems',
            value: function getGalleryItems() {
                var $slides = this.$slider.slick('getSlick').$slides,
                    items = [];

                if ($slides.length > 0) {
                    $slides.each(function (i, el) {
                        var img = $(el).find('img, iframe, video');
                        var tag = $(img).prop("tagName").toLowerCase();

                        var src = void 0,
                            item = void 0;
                        switch (tag) {
                            case 'img':
                                var large_image_src = img.attr('data-large_image'),
                                    large_image_w = img.attr('data-large_image_width'),
                                    large_image_h = img.attr('data-large_image_height');
                                item = {
                                    src: large_image_src,
                                    w: large_image_w,
                                    h: large_image_h,
                                    title: img.attr('data-caption') ? img.attr('data-caption') : img.attr('title')
                                };
                                break;
                            case 'iframe':
                                src = img.attr('src');
                                item = {
                                    html: '<iframe class="wvg-lightbox-iframe" src="' + src + '" style="width: 100%; height: 100%; margin: 0;padding: 0; background-color: #000000" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>'
                                };
                                break;
                            case 'video':
                                src = img.attr('src');
                                item = {
                                    html: '<video preload="auto" class="wvg-lightbox-video" disablePictureInPicture controls controlsList="nodownload" src="' + src + '" style="width: 100%; height: 100%; margin: 0;padding: 0; background-color: #000000"></video>'
                                };
                                break;
                        }

                        items.push(item);
                    });
                }
                return items;
            }
        }, {
            key: 'destroySlick',
            value: function destroySlick() {

                this.$slider.html('');
                this.$thumbnail.html('');

                if (this.$slider.hasClass('slick-initialized')) {
                    this.$slider.slick('unslick');
                }

                this._element.trigger('woo_variation_gallery_slick_destroy', [this]);
            }
        }, {
            key: 'defaultGallery',
            value: function defaultGallery() {
                var _this5 = this;

                if (this.is_variation_product) {
                    wp.ajax.send('wvg_get_default_gallery', {
                        data: {
                            product_id: this.product_id
                        },
                        success: function success(data) {
                            _this5._element.data('woo_variation_gallery_default', data);
                            _this5._element.trigger('woo_variation_default_gallery_loaded', [_this5, data]);
                        },
                        error: function error(e) {
                            _this5._element.data('woo_variation_gallery_default', []);
                            _this5._element.trigger('woo_variation_default_gallery_loaded', [_this5, e]);
                            console.error('Variation Gallery not available on variation id ' + _this5.product_id + '.');
                        }
                    });
                }
            }
        }, {
            key: 'initVariationImagePreload',
            value: function initVariationImagePreload() {
                var _this6 = this;

                //return;
                if (this.is_variation_product) {
                    wp.ajax.send('wvg_get_available_variation_images', {
                        data: {
                            product_id: this.product_id
                        },
                        success: function success(images) {
                            if (images.length > 1) {
                                _this6.imagePreload(images);
                            }
                            _this6._element.data('woo_variation_gallery_variation_images', images);
                            _this6._element.trigger('woo_variation_gallery_variation_images', [_this6, images]);
                        },
                        error: function error(e) {
                            _this6._element.data('woo_variation_gallery_variation_images', []);
                            console.error('Variation Gallery variations images not available on variation id ' + _this6.product_id + '.');
                        }
                    });
                }
            }
        }, {
            key: 'imagePreload',
            value: function imagePreload(images) {
                for (var i = 0; i < images.length; i++) {
                    try {

                        // Note: this won't work when chrome devtool is open and 'disable cache' is enabled within the network panel
                        var _img = new Image();
                        var _gallery = new Image();
                        var _full = new Image();
                        var _thumbnail = new Image();

                        _img.src = images[i].src;

                        if (images[i].srcset) {
                            _img.srcset = images[i].srcset;
                        }

                        _gallery.src = images[i].gallery_thumbnail_src;

                        _full.src = images[i].full_src;

                        _thumbnail.src = images[i].archive_src;

                        var video_link = $.trim(images[i].video_link);

                        if (video_link && images[i].video_embed_type === 'video') {

                            var req = new XMLHttpRequest();
                            req.open('GET', video_link, true);
                            req.responseType = 'blob';

                            req.onload = function () {
                                // Onload is triggered even on 404
                                // so we need to check the status code
                                if (this.status === 200) {
                                    var videoBlob = this.response;
                                    var vid = URL.createObjectURL(videoBlob); // IE10+
                                    // Video is now downloaded
                                    // and we can set it as source on the video element
                                    // video.src = vid;
                                }
                            };
                            req.onerror = function () {
                                // Error
                            };

                            req.send();
                        }

                        // Append Content
                        /*let _img_src    = images[i].src;
                        let _img_srcset = images[i].srcset;
                         let _gallery_src   = images[i].gallery_thumbnail_src;
                        let _full_src      = images[i].full_src;
                        let _thumbnail_src = images[i].archive_src;
                         let template = `<div style="display: none"><img aria-hidden="true" style="display: none" src="${_img_src}" /><img style="display: none" src="${_gallery_src}" /><img style="display: none" src="${_thumbnail_src}" /><img style="display: none" src="${_full_src}" /></div>`;
                         if (_img_srcset) {
                            template = `<div style="display: none"><img aria-hidden="true" style="display: none" src="${_img_src}" srcset="${_img_srcset}" /><img style="display: none" src="${_gallery_src}" /><img style="display: none" src="${_thumbnail_src}" /><img style="display: none" src="${_full_src}" /></div>`;
                        }
                         // let template = `<div style="display: none"><img aria-hidden="true" style="display: none" src="${_img_src}" srcset="${_img_srcset}" /><img style="display: none" src="${_gallery_src}" /><img style="display: none" src="${_thumbnail_src}" /><img style="display: none" src="${_full_src}" /></div>`;
                        $('body').append(template)*/
                    } catch (e) {
                        console.error(e);
                    }
                }
            }
        }, {
            key: 'initVariationGallery',
            value: function initVariationGallery() {
                var _this7 = this;

                // show_variation, found_variation

                this.$variations_form.off('reset_image.wvg');
                this.$variations_form.off('click.wvg');
                this.$variations_form.off('show_variation.wvg');

                if (woo_variation_gallery_options.gallery_reset_on_variation_change) {
                    this.$variations_form.on('reset_image.wvg', function (event) {
						//Custom code
							//var monsta_size = jQuery( 'input[name="attribute_pa_monstasize"]' ).val();
							//if( jQuery.trim( monsta_size ) == '' ){
								//return false;
							//}
						//end
                        _this7.addLoadingClass();
                        _this7.galleryReset();
                    });
                } else {
                    this.$variations_form.on('click.wvg', '.reset_variations', function (event) {
                        _this7.addLoadingClass();
                        _this7.galleryReset();
                    });
                }

                this.$variations_form.on('show_variation.wvg', function (event, variation) {
                    _this7.addLoadingClass();
                    _this7.galleryInit(variation.variation_gallery_images);
                });
            }
        }, {
            key: 'galleryReset',
            value: function galleryReset() {
                var _this8 = this;

                var $default_gallery = this._element.data('woo_variation_gallery_default');
				//Custom code
					//if( typeof default_variation_galary_images != 'undefined' ){
						//var default_gallery = JSON.parse( default_variation_galary_images );
					//}
					//console.log( '------default_gallery----------' )
					//console.log( default_gallery )
				//End
                if ($default_gallery && $default_gallery.length > 0) {
                    this.galleryInit($default_gallery);
                } else {
                    _.delay(function () {
                        _this8.removeLoadingClass();
                    }, 100);
                }
            }
        }, {
            key: 'galleryInit',
            value: function galleryInit(images) {
                var _this9 = this;
			
                var hasGallery = images.length > 1;

                this._element.trigger('before_woo_variation_gallery_init', [this, images]);

                this.destroySlick();

                var slider_inner_html = images.map(function (image) {
					//console.log( '-------slider_inner_html---------' )
					//console.log( image )
                    var template = wp.template('woo-variation-gallery-slider-template');
                    return template(image);
                }).join('');

                var thumbnail_inner_html = images.map(function (image) {
					//console.log( '-------thumbnail_inner_html---------' )
					//console.log( image )
                    var template = wp.template('woo-variation-gallery-thumbnail-template');
                    return template(image);
                }).join('');

                if (hasGallery) {
                    this.$target.addClass('woo-variation-gallery-has-product-thumbnail');
                } else {
                    this.$target.removeClass('woo-variation-gallery-has-product-thumbnail');
                }

                this.$slider.html(slider_inner_html);

                if (hasGallery) {
                    this.$thumbnail.html(thumbnail_inner_html);
                } else {
                    this.$thumbnail.html('');
                }

                //this._element.trigger('woo_variation_gallery_init', [this, images]);

                _.delay(function () {
                    _this9.imagesLoaded();
                }, 1);

                //this._element.trigger('after_woo_variation_gallery_init', [this, images]);
            }
        }, {
            key: 'imagesLoaded',
            value: function imagesLoaded() {
                var _this10 = this;

                // Some Script Add Custom imagesLoaded Function
                if (!$().imagesLoaded.done) {
                    this._element.trigger('woo_variation_gallery_image_loading', [this]);
                    this._element.trigger('woo_variation_gallery_image_loaded', [this]);
                    return;
                }

                this._element.imagesLoaded().progress(function (instance, image) {
                    _this10._element.trigger('woo_variation_gallery_image_loading', [_this10]);
                }).done(function (instance) {
                    _this10._element.trigger('woo_variation_gallery_image_loaded', [_this10]);
                    _this10._element.trigger('woo_variation_gallery_image_loaded', [_this10]);
                });
            }
        }], [{
            key: '_jQueryInterface',
            value: function _jQueryInterface(config) {
                return this.each(function () {
                    new WooVariationGallery(this, config);
                });
            }
        }]);

        return WooVariationGallery;
    }();

    /**
     * ------------------------------------------------------------------------
     * jQuery
     * ------------------------------------------------------------------------
     */

    $.fn['WooVariationGallery'] = WooVariationGallery._jQueryInterface;
    $.fn['WooVariationGallery'].Constructor = WooVariationGallery;
    $.fn['WooVariationGallery'].noConflict = function () {
        $.fn['WooVariationGallery'] = $.fn['WooVariationGallery'];
        return WooVariationGallery._jQueryInterface;
    };
	

    return WooVariationGallery;
}(jQuery);
/* harmony default export */ __webpack_exports__["default"] = (WooVariationGallery);

/***/ }),
/* 3 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 4 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 5 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 6 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 7 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 8 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ })
/******/ ]);
//
