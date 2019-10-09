/**
 * phpBB Studio's WRW extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019 phpBB Studio <https://www.phpbbstudio.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

(function($) { // Avoid conflicts with other libraries

	'use strict';

	// https://stackoverflow.com/a/51646813

	/**
	 * @param {array}	wrw							// Who Read What container
	 * @param {array}	wrw.data					// Array containing all the Who Read What data
	 * @param {bool}	wrw.data.seq				// Boolean indicating if only one post at a time can be considered as "in view"
	 * @param {int}		wrw.data.cpw				// Integer defining how many characters there are in a word on average
	 * @param {int}		wrw.data.wpm				// Integer defining how many words per minute a user reads
	 * @param {int}		wrw.data.pct				// Integer defining percentage of the post that has to be in view, to consider it as as "in view".
	 * @param {int}		wrw.data.int				// Integer defining how often the function should run
	 * @param {array}	wrw.data.posts				// Array containing all the unread posts on the page
	 * @param {int} 	wrw.data.posts.id			// Integer defining the post's identifier
	 * @param {object}	wrw.data.posts.this			// Object containing the jQuery element
	 * @param {bool}	wrw.data.posts.view			// Boolean indicating if this post is considered "in view" or not
	 * @param {int}		wrw.data.posts.viewLen		// Integer indicating how long this post has already been considered "in view"
	 * @param {int}		wrw.data.posts.viewTime		// Integer indicating at what time this posts was first considered "in view"
	 * @param {int} 	wrw.data.posts.readTime		// Integer defining how long this post has to be "in view" in order to consider it as read
	 * @param {bool}	wrw.view					// Global WRW View indicator
	 */
	let wrw = {
		data: $('#wrw_read_data').data(),
		posts: [],
		view: false,
	};

	let timeout,
		interval,
		intTimer = wrw.data.int * 1000; // Time in between we run the function

	$(function() {
		// Grab all the posts on this page
		$('div.post[id^="p"]').not(":has(div.wrw-read)").each(function() {
			wrw.posts.push({
				id: this.id.substring(1),			// This post's identifier ("p"-part removed)
				this: $(this),
				view: false,
				viewLen: 0,
				viewTime: 0,
				readTime: wrw.getReadTime($(this)),
			});
		});

		$(window).on('load scroll touchmove whoReadWhat', function() {
			timeout = setTimeout(function() {

				// Iterate over the posts
				$.each(wrw.posts, function(i, post) {
					// If it is in view, mark it as so and add the start time
					if (wrw.inView(post.this) && !post.view && (!wrw.data.seq || (wrw.data.seq && !wrw.view))) {
						post.view = true;
						post.viewTime = wrw.getTime();

						if (wrw.data.seq) {
							wrw.view = true;
						}
					}
				});
			}, 200);
		});

		interval = setInterval(function() {
			$.each(wrw.posts, function(i, post) {
				if (post) {
					let len = wrw.getViewedTime(post.viewTime);

					if (wrw.inView(post.this) && post.view) {

						// Uncomment if you want information in the browser console
					//	console.log('Post id ' + post.id + ' has been in view for ' + ((post.viewLen + len) / 1000) + 'seconds, ' + (post.readTime / 1000) + ' seconds required.');

						if ((post.viewLen + len) >= post.readTime) {
							$.ajax({
								method: 'POST',
								url: decodeURIComponent(wrw.data.url),
								data: {
									post: post.id ,
									topic: wrw.data.topic,
									forum: wrw.data.forum,
								},
								dataType: 'json',
							}).done(function(response) {
								post.this.find('p.author').before(response.tpl);
							});

							// It has been read, so remove it from the list.
							wrw.posts.splice(i, 1);

							// Reset the the overall bool and rerun the function
							wrw.reset();
						}
					} else {
						// If it was in view before, add the view length
						if (post.view) {
							post.viewLen = post.viewLen + len;

							// Reset the the overall bool and rerun the function
							wrw.reset();
						}

						post.view = false;	// Set this post as no longer in view
						post.viewTime = 0;	// Reset the view start time
					}
				}
			});
		}, intTimer);
	});

	wrw.reset = function() {
		if (wrw.data.seq) {
			wrw.view = false;
			$(document).trigger('whoReadWhat');
		}
	};

	wrw.getReadTime = function (element) {
		let content = element.find('.content'),
			words = wrw.wordCount(content);

		return wrw.readTime(words);
	};

	wrw.readTime = function(words) {
		return (60000 / wrw.data.wpm * words);
	};

	wrw.wordCount = function(element) {
		if (wrw.data.quote) {
			element = element.clone();

			element.find('blockquote').remove();
		}

		let chars = element.text().trim().replace(/[\s]+/g, "").length;

		return Math.round(chars / wrw.data.cpw);
	};

	wrw.getTime = function() {
		return new Date().getTime();
	};

	wrw.getViewedTime = function(viewStart) {
		return (wrw.getTime() - viewStart);
	};

	wrw.inView = function(element) {
		if (typeof jQuery === "function" && element instanceof jQuery) {
			element = element[0];
		}

		let pct = (100 - wrw.data.pct) / 100,
			rect = element.getBoundingClientRect();

		return (
			(rect.top >= -(pct * rect.height) &&
			rect.left >= -(pct * rect.width) &&
			(rect.bottom - (pct * rect.height)) <= $(window).height() &&
			(rect.right - (pct * rect.width)) <= $(window).width()) ||
			((rect.top < 0) && (rect.bottom > $(window).height()))
		);
	};

})(jQuery); // Avoid conflicts with other libraries

