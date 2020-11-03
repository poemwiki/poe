import AppForm from '../app-components/Form/AppForm';

Vue.component('nation-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                describe_lang:  this.getLocalizedFormDefaults() ,
                f_id:  '' ,
                name:  '' ,
                name_lang:  this.getLocalizedFormDefaults() ,
                wikidata_id:  '' ,
                
            }
        }
    }

});