
var ModelGdoc = {
    modelName : 'gdoc',
    search: {
        modelName : 'gdoc',
        // fields: [
		// 	'nome',
        //
        // ],
        fieldsConfig: {
			'nome' : {
                type : "w-input",
			},

        },
        // groups: {
        //     'g0': {
        //         fields: [
        //
        //         ],
        //     },
        //     'g1': {
        //         fields: [
        //
        //         ],
        //     }
        // },

    },
    list: {
        modelName : 'gdoc',
        actions : [
            'action-insert',
            'action-edit',
            //'action-delete',
            //'action-delete-selected',
        ],
        fields: [
			'nome',
			'descrizione',
            'tipo',
            'link'
        ],
        fieldsConfig: {
            link : {
                type : 'w-custom',
                mounted() {
                    this.value = '<a href="https://docs.google.com/document/d/' + this.modelData.gdoc_id + '/edit" target="_blank">Vai al documento</a>';
                }
            }
        },
        orderFields : {
			'nome' : 'nome',

        },
        mounted : function () {
            var that = this;

            that.$crud.EventBus.$on('swap-change',function (json) {

                console.log('SWAPCHANGE: ',json);

                if (json && json.result['anno-sistema']) {
                    jQuery('.anno-visualizzato').html(json.result['anno-visualizzato'].descrizione);
                    jQuery('.anno-sistema').html(json.result['anno-sistema'].descrizione);
                }
                that.reload();

            });


        },
        methods : {
            completed : function() {
                jQuery('[data-toggle="popover"]').popover();

            }
        }
    },
    edit: {
        modelName : 'gdoc',
        actions : ['action-save','action-back'],
        fields: [
			'id',
			'nome',
            'html'
        ],
        fieldsConfig: {
            'id' : {
                type : "w-text",
                template: {
                    name:'tpl-record-view',
                    labelType:'top',
                }
            },
			'nome' : {
                type : "w-input",
			},
			'logo' : {
                type : "w-input",
			},
			'html' : {
                type : "w-texthtml",
                htmlAttributes: {},
                template: {
                    name : 'tpl-record',
                    layoutType:'full',
                    labelType:'none'
                }
			},
			'descrizione' : {
                type : "w-texthtml",
                htmlAttributes: {},
			},

        }

    },
    insert: {
        modelName : 'anno',
        actions : ['action-save','action-back'],
        fields: [
            'id',
            'nome',

        ],
        fieldsConfig: {
            'id' : {
                type : "w-select",
            },
            'nome' : {
                type : "w-input",
            },

        }

    },
}
