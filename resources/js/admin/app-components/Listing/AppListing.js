import { BaseListing } from 'craftable';

export default {
	mixins: [BaseListing],
  methods: {
	  mergeToItem: function(url) {
      this.$modal.show('dialog', {
        title: '请谨慎操作！',
        text: '请填入合并到的诗歌 ID<input id="merge-to-id" type="number" />',
        buttons: [{ title: '取消' }, {
          title: '<span class="btn-dialog btn-danger">确认合并<span>',
          handler: () => {
            this.$modal.hide('dialog');
            axios.post(url + '/merge/' + $('#merge-to-id').val()).then((response) => {
              this.loadData();
              this.$notify({ type: 'success', title: 'Success!', text: response.data.message ? response.data.message : '删除合并。' });
            }, (error) => {
              this.$notify({ type: 'error', title: 'Error!', text: error.response.data.message ? error.response.data.message : '出错啦😭' });
            });
          }
        }]
      });
    },

    deleteItem: function deleteItem(url) {
      this.$modal.show('dialog', {
        title: '请谨慎操作！',
        text: '确实要删除此项？',
        buttons: [{ title: '取消' }, {
          title: '<span class="btn-dialog btn-danger">确认删除<span>',
          handler: () => {
            this.$modal.hide('dialog');
            axios.delete(url).then((response) => {
              this.loadData();
              this.$notify({ type: 'success', title: 'Success!', text: response.data.message ? response.data.message : '删除成功。' });
            }, (error) => {
              this.$notify({ type: 'error', title: 'Error!', text: error.response.data.message ? error.response.data.message : '出错啦😭' });
            });
          }
        }]
      });
    },
  }
};