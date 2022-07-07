import AppForm from '../app-components/Form/AppForm';

Vue.component('user-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                avatar:  '' ,
                email:  '' ,
                email_verified_at:  '' ,
                invite_code:  '' ,
                invite_max:  false ,
                invited_by:  '' ,
                is_active:  false ,
                is_admin:  false ,
                name:  '' ,
                password:  '' ,
                
            }
        }
    }

});