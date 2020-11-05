import AppForm from '../app-components/Form/AppForm';

Vue.component('genre-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                describe_lang:  this.getLocalizedFormDefaults() ,
                f_id:  0 ,
                name:  '' ,
                name_lang:  this.getLocalizedFormDefaults() ,
                wikidata_id:  '' ,

            }
        }
    }

});