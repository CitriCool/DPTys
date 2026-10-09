/*!
 * Summernote Syntax Highlighting
 * http://epiksel.github.io/summernote-ext-highlight/
 * http://e-piksel.com
 * Original Author: http://www.hyl.pw/
 *
 * Released under the MIT license
 */
(function (factory) {
    /* global define */
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof module === 'object' && module.exports) {
        // Node/CommonJS
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals: jQuery
        factory(window.jQuery);
    }
}(function ($) {

    // Extends plugins for adding highlight.
    //  - plugin is external module for customizing.
    $.extend($.summernote.plugins, {

        /**
         * @param {Object} context - context object has status of editor.
         */
        'highlight': function (context) {

            var self = this;

            var ui = $.summernote.ui;
            var $editor = context.layoutInfo.editor;
            var options = context.options;
            var lang = options.langInfo;

            // add button
            context.memo('button.highlight', function () {
                // create button
                var button = ui.button({
                    contents: '<i class="fa fa-file-code-o"></i>',
                    tooltip: 'Insertar Código',
                    click: function () {
                        self.show();
                    }
                });

                // create jQuery object from button instance.
                var $highlight = button.render();
                return $highlight;
            });

            this.createDialog = function () {

                var $box = $('<div />');
                var $selectGroup = $('<div class="form-group" />');
                var $textGroup = $('<div class="form-group" />');
                var $select = $('<select class="form-control ext-highlight-select" />');

                var languages = [
                    'sql', // popular lang
                    // Puedes agregar más lenguajes aquí
                ];

                for (var i = 0; i < languages.length; i++) {
                    $select.append('<option value="' + languages[i] + '">' + languages[i] + '</option>');
                }

                var $label = $('<label />');
                $label.html('Seleccionar lenguaje');
                $selectGroup.append($label);
                $selectGroup.append($select);
                $box.append($selectGroup);

                var $labelCode = $('<label />');
                $labelCode.html('Ingresa el fragmento de código');
                var $textarea = $('<textarea class="ext-highlight-code form-control" rows="10" />');

                $textGroup.append($labelCode);
                $textGroup.append($textarea);
                $box.append($textGroup);

                return $box.html();
            };

            this.createCodeNode = function (code, select) {
                var $code = $('<code>');
                $code.text(code);
                $code.addClass('language-' + select);

                var $pre = $('<pre>');
                $pre.append($code);
                $pre.addClass('prettyprint linenums');

                return $pre[0];
            };

            this.showHighlightDialog = function (codeInfo) {
                return $.Deferred(function (deferred) {
                    var $extHighlightCode = self.$dialog.find('.ext-highlight-code');
                    var $extHighlightBtn = self.$dialog.find('.ext-highlight-btn');
                    var $extHighlightSelect = self.$dialog.find('.ext-highlight-select');

                    ui.onDialogShown(self.$dialog, function () {
                        context.triggerEvent('dialog.shown');

                        $extHighlightCode.val(codeInfo);

                        $extHighlightCode.on('input', function () {
                            ui.toggleBtn($extHighlightBtn, $extHighlightCode.val() !== '');

                            codeInfo = $extHighlightCode.val();
                        });

                        $extHighlightBtn.one('click', function (event) {
                            event.preventDefault();
                            context.invoke('editor.insertNode', self.createCodeNode(codeInfo, $extHighlightSelect.val()));

                            self.$dialog.modal('hide');
                        });
                    });

                    ui.onDialogHidden(self.$dialog, function () {
                        context.triggerEvent('dialog.hidden');
                        deferred.resolve();
                    });

                    // Evitar que el evento de cierre afecte a otros modales
                    self.$dialog.on('hide.bs.modal', function (e) {
                        e.stopPropagation();
                    });

                    self.$dialog.on('hidden.bs.modal', function (e) {
                        e.stopPropagation();
                    });

                    ui.showDialog(self.$dialog);
                }).promise();
            };

            this.getCodeInfo = function () {
                var text = context.invoke('editor.getSelectedText');
                return text || '';
            };

            this.show = function () {
                var codeInfo = self.getCodeInfo();

                context.invoke('editor.saveRange');
                this.showHighlightDialog(codeInfo).then(function () {
                    context.invoke('editor.restoreRange');
                });
            };

            // Inicialización del plugin
            this.initialize = function () {
                var $container = options.dialogsInBody ? $(document.body) : $editor;

                var footer = [
                    '<button href="#" class="btn btn-primary ext-highlight-btn disabled" disabled>',
                    'Insertar código',
                    '</button>'
                ].join('');

                this.$dialog = ui.dialog({
                    className: 'ext-highlight',
                    title: 'Insertar Código',
                    body: this.createDialog(),
                    footer: footer,
                    // Configuraciones para evitar cierre involuntario
                    backdrop: 'static',    // Evita cierre al hacer clic fuera
                    keyboard: false        // Desactiva cierre con ESC
                }).render().appendTo($container);
            };

            // Destrucción del plugin
            this.destroy = function () {
                ui.hideDialog(this.$dialog);
                this.$dialog.remove();
            };
        }
    });
}));
