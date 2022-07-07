import AppForm from '../app-components/Form/AppForm';

Vue.component('author-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                describe_lang:  this.getLocalizedFormDefaults() ,
                name_lang:  this.getLocalizedFormDefaults() ,
                pic_url:  '' ,
                user_id:  '' ,
                wikidata_id:  '' ,
                wikipedia_url:  this.getLocalizedFormDefaults() ,
                
            }
        }
    }

});