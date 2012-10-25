/**
 * @fileoverview Skype Emoticons
 * @author Thibault Martin-Lagardette
 * @version 1.0
*/

/*
 * Copyright (c) 2012 Skype Technologies S.A. All rights reserved.
 */

function elementIsInViewport(elm) {
	var r = elm.getBoundingClientRect();

	return (
		(r.top + r.height) >= 0 && (r.bottom - r.height) <= window.innerHeight &&
		(r.left + r.width) >= 0 && (r.right - r.width) <= window.innerWidth
	);
}

// This is also called in -[SkypeChatDisplay SK_processNewMessages];
// If you rename or move it, be careful :)
function updateEmoticonSprites() {
	var elementsToSprite = [],
		elementsToUnsprite = [];
	$(".emoticon").each(function (idx, elm) {
		if (elementIsInViewport(elm)) {
			//$(elm).removeClass("offscreen");
			elementsToSprite.push($(elm));
		}
		else {
			//$(elm).addClass("offscreen");
			elementsToUnsprite.push($(elm));
		}
	});
	$(elementsToSprite).sprite();
	$(elementsToUnsprite).unsprite();
}

function pauseAllEmoticonAnimations() {
	$.pauseAnimations();
}

function resumeAllEmoticonAnimations() {
	$.resumeAnimations();
}

// Scroll timer
// When the view scroll, make sure to updateEmoticonSprites (aka, stop animating
// any hidden ones and start animating visible ones)
// For performance, do it with a timer, because `scroll' is called for every
// scrolled pixel
spriteScrollTimer = null;
$(window).load(function() {
	$(window).scroll(function() {
		if (spriteScrollTimer) {
			clearTimeout(spriteScrollTimer);
		}
		spriteScrollTimer = setTimeout(updateEmoticonSprites, 100);
	});
});

