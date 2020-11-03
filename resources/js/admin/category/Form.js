import AppForm from '../app-components/Form/AppForm';

Vue.component('category-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                describe_lang:  this.getLocalizedFormDefaults() ,
                name:  '' ,
                name_lang:  this.getLocalizedFormDefaults() ,
                wikidata_id:  '' ,
                
            }
        }
    }

});