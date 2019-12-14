jQuery(document).ready(function() {
    var $ = jQuery;
    if ($('.set_custom_images').length > 0) {
        if ( typeof wp !== 'undefined' && wp.media && wp.media.editor) {
            $('.set_custom_images').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var id = button.prev();
                var inp = button.prev().prev();
                var width = $('#post-img-num').attr('width');
                var height = $('#post-img-num').attr('height');
                wp.media.editor.send.attachment = function(props, attachment) {
                    inp.val(attachment.id);
                    id.html( '<img width="auto" height="'+height+'" src="' + attachment.url + '" alt="" />' );
                };
                wp.media.editor.open(button);
                return false;
            });
        }
    }
});