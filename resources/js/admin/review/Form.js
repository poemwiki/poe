import AppForm from '../app-components/Form/AppForm';

Vue.component('review-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                content:  '' ,
                content_id:  '' ,
                like:  '' ,
                poem_id:  '' ,
                title:  '' ,
                user_id:  '' ,
                
            }
        }
    }

});