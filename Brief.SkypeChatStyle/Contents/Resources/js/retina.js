/**
 * @fileoverview Skype Retina
 * @author Thibault Martin-Lagardette
 * @version 1.0
*/

function changeSKCGPropertyToUseRetina(el, property, isPropertyCSS, useRetina)
{
	var src;
	if (isPropertyCSS === false) {
		src = el[property];
	}
	else {
		src = $(el).css(property);
	}

	if (typeof src === "undefined") {
		console.log("Could not find property: " + property);
		return ;
	}

	var newSrc = null;

	if (src.search("skcg") !== -1) {
		oldRegexp = new RegExp("retina=" + (useRetina === 1 ? 0 : 1));

		if (src.search(oldRegexp) !== -1) {
			newSrc = src.replace(oldRegexp, "retina=" + useRetina);
		}
		else {
			console.log("Could not find retina in: " + src);
		}
	}
	else {
		if (useRetina === 1 && src.search("@2x.png") === -1) {
			newSrc = src.replace(/\.png/, "@2x.png");
		}
		else if (useRetina === 0 && src.search("@2x.png") !== -1) {
			newSrc = src.replace(/@2x\.png/, ".png");
		}
		else {
			console.log("Could not find how to switch retinaness of: " + src);
		}
	}

	if (newSrc !== null) {
		if (isPropertyCSS === true) {
			$(el).css(property, newSrc);
		}
		else {
			el[property] = newSrc;
		}
	}
}

function updateImagesForCurrentScaleFactor(scaleFactor) {
	scaleFactor = scaleFactor || window.devicePixelRatio;
	if (scaleFactor >= 2) {
		$("img.avatar").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "src", false, 1);
		});
		$("img.icon").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "src", false, 1);
		});
		$(".flag").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "src", false, 1);
		});
		$(".emoticon").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "background-image", true, 1);
		});
	}
	else {
		$("img.avatar").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "src", false, 0);
		});
		$("img.icon").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "src", false, 0);
		});
		$(".flag").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "src", false, 0);
		});
		$(".emoticon").each(function(idx, el) {
			changeSKCGPropertyToUseRetina(el, "background-image", true, 0);
		});
	}
}

$(function() {
	updateImagesForCurrentScaleFactor();
});