import AppForm from '../app-components/Form/AppForm';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.min.css';

Vue.component('tag-form', {
  mixins: [AppForm],
  components: { Multiselect },
  props: ['categories'],


  data: function () {
    return {

      form: {
        category_id: '',
        describe_lang: this.getLocalizedFormDefaults(),
        name: '',
        name_lang: this.getLocalizedFormDefaults(),
        wikidata_id: '',

      },

      selected: null,
      categoryList: this.categories,
    }
  },

  mounted() {
    this.selected = this.categories.filter(c => c.id === this.form.category_id);
  },

  methods: {
    onSelect: function (option, id) {
      this.form.category_id = option.id;
    }
  }

});