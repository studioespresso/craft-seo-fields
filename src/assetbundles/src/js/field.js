/**
 * Test Field plugin for Craft CMS
 *
 * TestFieldField Field JS
 *
 * @author    test
 * @copyright Copyright (c) 2019 test
 * @link      test.com
 * @package   TestField
 * @since     1.0.0TestFieldTestFieldField
 */

;(function ( $, window, document, undefined ) {

    var pluginName = "SeoField",
        defaults = {
        };

    // Plugin constructor
    function Plugin( element, options ) {
        this.element = element;

        this.options = $.extend( {}, defaults, options) ;

        this._defaults = defaults;
        this._name = pluginName;

        this.init();
    }

    Plugin.prototype = {

        init: function(id) {
            var _this = this;

            $(function () {
                console.log(this);
            });
        }
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function ( options ) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName,
                    new Plugin( this, options ));
            }
        });
    };

})( jQuery, window, document );
