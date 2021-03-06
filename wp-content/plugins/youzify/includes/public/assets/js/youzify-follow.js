if ( typeof jq == "undefined" ) {
	var jq = jQuery;
}

jq( function() {
	var profileHeader   = jq(".youzify-social-buttons");
	var memberLoop      = jq("#youzify-members-list").parent();
	var groupMemberLoop = jq("#youzify-member-list").parent();

	profileHeader.on("click", ".follow-button a", function() {
		bp_follow_button_action( jq(this), 'profile' );
		return false;
	});

	memberLoop.on("click", ".follow-button a", function() {
		bp_follow_button_action( jq(this), 'member-loop' );
		return false;
	});

	groupMemberLoop.on("click", ".follow-button a", function() {
		bp_follow_button_action( jq(this) );
		return false;
	});
} );

function bp_follow_button_action( scope, context ) {
	var link = scope,
		uid = link.attr('id'),
		nonce  = link.attr('href'),
		action = '';

	uid    = uid.split('-');
	action = uid[0];
	uid    = uid[1];

	nonce = nonce.split('?_wpnonce=');
	nonce = nonce[1].split('&');
	nonce = nonce[0];

	link.addClass( 'loading' );

	link.trigger( 'bpFollow:beforeAjax', {
		action: action,
		context: context
	} );

	jq.post( ajaxurl, {
		action: 'bp_' + action,
		'uid': uid,
		'link_class': link.attr( 'class' ).replace( 'loading', '' ),
		'_wpnonce': nonce
	},
	function(response) {
		jq( link.parent()).fadeOut(200, function() {
			// toggle classes
			if ( action == 'unfollow' ) {
				link.parent().removeClass( 'following' ).addClass( 'not-following' );
			} else {
				link.parent().removeClass( 'not-following' ).addClass( 'following' );
			}

			// add ajax response
			link.parent().html( response.data.button );

			// increase / decrease counts
			var count_wrapper = false;
			if ( context == 'profile' ) {
				count_wrapper = jq("#user-members-followers span");

			} else if ( context == 'member-loop' ) {
				// this means we're on the member directory
				if ( jq(".dir-search").length ) {
					count_wrapper = jq("#members-following span");

				// a user is on their own profile
				} else if ( ! jq.trim( profileHeader.text() ) ) {
					count_wrapper = jq("#user-members-following span");
				}
			}

			if ( count_wrapper.length ) {
				if ( action == 'unfollow' ) {
					count_wrapper.text( ( count_wrapper.text() >> 0 ) - 1 );
				} else if ( action == 'follow' ) {
					count_wrapper.text( ( count_wrapper.text() >> 0 ) + 1 );
				}
			}

			jq(this).fadeIn(200);

			jq(this).trigger( 'bpFollow:complete', {
				action: action,
				context: context,
				response: response.data
			} );
		});
	});
}