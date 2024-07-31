const {Component, Mixin} = Shopware;

Component.override('sw-file-input', {
  methods: {
    setSelectedFile(newFile) {
      this.selectedFile = newFile;

      this.$emit('update:value', this.selectedFile);
      this.$emit('change', this.selectedFile);
    },
  }
});
