import AppForm from '../app-components/Form/AppForm';

Vue.component('poem-form', {
    mixins: [AppForm],
    data: function() {
        return {
            form: {
                title:  '' ,
                language:  false ,
                is_original:  false ,
                poet:  '' ,
                poet_cn:  '' ,
                // bedtime_post_id:  '' ,
                // bedtime_post_title:  '' ,
                poem:  '' ,
                length:  '' ,
                translator:  '' ,
                // from:  '' ,
                // year:  '' ,
                // month:  '' ,
                // date:  '' ,
                dynasty:  '' ,
                nation:  '' ,
                // need_confirm:  false ,
                // is_lock:  false ,
                // content_id:  '' ,

            }
        }
    }

});
