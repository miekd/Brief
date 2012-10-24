/**
 * sprite plugin
 *
 * @copy	Copyright (c) 2012 Skype Limited
 * @author  Martin Kapp <skype:kappmartin>
 * @author	Thibault Martin-Lagardette
 */
(function($) {

    /**
     * @constructor Extend jQuery elements set here with 'sprite' function
     */
    $.fn.extend({
        sprite: function(options) {
            // Apply to each element matched with selector
            return this.each(function() {
				var newOptions = $.extend({}, $.sprite.defaults, options);
                $.sprite(this, newOptions);
            });
        },
        unsprite: function() {
			return this.each(function() {
				$.unsprite(this);
			});
        },
		deleteSprite: function() {
			return this.each(function() {
				$.deleteSprite(this);
			});
        },
		sprite_debug: function() {
			console.log(SpriteCore);
		}
    });


	var SpriteCore = {
		animations: [],
		timer: null,
		addAnimation: function(el, options) {
			// Add animation timer if it doesn't already exist
			SpriteCore.resumeAnimations();

			var sprite_id = $(el).data('sprite_id');

			// If the animation already exist, just run it :)
			// Otherwise, let's create it.
			if ((typeof sprite_id !== "undefined") && (typeof this.animations[sprite_id] !== "undefined")) {
				this.animations[sprite_id].running = true;
			}
			else {
				var obj = {
					element: el,
					current_frame: 0,
					no_of_frames: options.no_of_frames,
					frame_width: $(el).width(),
					running: true
				};
				if ((typeof sprite_id === "undefined")) {
					var index = this.animations.push(obj) - 1;
					$(el).data('sprite_id', index);
				}
				else {
					this.animations[sprite_id] = obj;
				}
			}
		},
		removeAnimation: function(el) {
			var sprite_id = $(el).data('sprite_id');
			if (typeof sprite_id !== "undefined" && typeof this.animations[sprite_id] !== "undefined") {
				this.animations[sprite_id].running = false;
			}
		},
		deleteSprite: function(el) {
			var sprite_id = $(el).data('sprite_id');
			if (typeof sprite_id !== "undefined" && typeof this.animations[sprite_id] !== "undefined") {
				this.animations[sprite_id] = undefined;
			}
		},
		pauseAnimations: function() {
			if (this.timer) {
				clearTimeout(this.timer);
				this.timer = null;
			}
		},
		resumeAnimations: function() {
			if (this.timer === null) {
				this.timer = setTimeout(function() {
					SpriteCore.updateFrame();
				}, 1000 / $.sprite.defaults.fps);
			}
		},
		updateFrame: function() {
			for (var x in this.animations) {
				if (!this.animations.hasOwnProperty(x)) {
					continue;
				}

				var d = this.animations[x];
				if (typeof d !== "undefined" && d.running === true) {
					if (++d.current_frame >= d.no_of_frames) {
						d.current_frame = 0;
					}
					$(this.animations[x].element).css('backgroundPosition', '0 ' + (-(d.current_frame * d.frame_width)) + 'px');
					this.animations[x].current_frame = d.current_frame;
				}
			}

			this.timer = null;
			SpriteCore.resumeAnimations();
		},
		x: 0
	};

    /**
     * $.sprite class that is applied to all the elements found with the selectors.
     *
     * @class The actual sprite class
     * @param element                 The input field to attach the sprite results
     * @param options                 Options for the sprite class
     */
    $.sprite = function(/**HTMLElement*/ element, /**Object*/ options) {
		options.current_frame = 0;
		options.frame_height = $(element).height();
		options.frame_width = $(element).width();

		var str = $(element).css("background-image");
		var i = new Image();
		var h, w;
		var expectedWidth = parseInt($(element).css("width"), 10);

		i.src = str.replace(/^url\(\"?([^"]+)\"?\)$/, "$1");
		i.onload = function() {
			divider = parseInt(i.width / expectedWidth, 10);
			h = i.height / divider;
			w = i.width / divider;

			options.no_of_frames = (options.dir == 'vertical') ? (h / options.frame_height)|0 : (w / options.frame_width)|0;
			SpriteCore.addAnimation(element, options);
		};
    };

    $.unsprite = function(/**HTMLElement*/ element) {
		SpriteCore.removeAnimation(element);
    };

    $.deleteSprite = function(/**HTMLElement*/ element) {
		SpriteCore.deleteSprite(element);
    };

    $.pauseAnimations = function() {
		SpriteCore.pauseAnimations();
    };

    $.resumeAnimations = function() {
		SpriteCore.resumeAnimations();
    };

    $.sprite.defaults = {
        fps: 24,
		dir: 'vertical'
    };
})(jQuery);