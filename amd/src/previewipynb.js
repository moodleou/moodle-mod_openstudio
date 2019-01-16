define(['jquery',
    'mod_openstudio/ansi_up',
    'mod_openstudio/marked',
    'mod_openstudio/es5-shim',
    'mod_openstudio/notebook',
    'mod_openstudio/prism'], function($, ansiup, marked, es5shim, notebook, prism) {
    var t;
    t = {
        /**
         * Preview the ipynb file content when we view the files.
         */
        init: function() {
            var root = this;
            var renderOneTime = false;
            var wrapper = $('#openstudio-content-previewipynb');
            if (wrapper.length > 0) {
                t.renderView(wrapper, root);
            }

            $('#openstudio_content_view_maximize').on('click', function() {
                var wrapper = $('.openstudio-modal-content-body #openstudio-content-previewipynb');
                if (wrapper.length > 0) {
                    if (!renderOneTime) {
                        t.renderView(wrapper, root);
                    }
                    renderOneTime = true;
                }
            });
        },
        /**
         * Render view for ipynb content.
         *
         * @param wrapper
         * @param root
         */
        renderView: function(wrapper, root) {
            var notebook = root.notebook = nb.parse(JSON.parse(wrapper.text()));
            wrapper.html(notebook.render());
            prism.highlightAll();
            wrapper.show();
        }
    };
    return t;
});
