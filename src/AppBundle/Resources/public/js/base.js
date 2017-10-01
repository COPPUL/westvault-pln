
/**
 * Make sure links out of the PLN open in a new window, and add a confirm
 * popup for any link with a .js-confirm class. Useful for getting confirmation
 * on delete links.
 */
$(document).ready(function () {
    $("a.js-confirm").each(function () {
        var $this = $(this);
        $this.click(function () {
            return window.confirm($this.data('confirm'));
        });
    });

    $("a[href^='http://']").attr("target","_blank");
});
