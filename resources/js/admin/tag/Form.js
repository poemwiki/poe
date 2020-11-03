import AppForm from '../app-components/Form/AppForm';

Vue.component('tag-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                category_id:  '' ,
                describe_lang:  this.getLocalizedFormDefaults() ,
                name:  '' ,
                name_lang:  this.getLocalizedFormDefaults() ,
                wikidata_id:  '' ,
                
            }
        }
    }

});