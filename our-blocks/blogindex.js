
wp.blocks.registerBlockType("ourblocktheme/blogindex", {
  title: "Fictional Univeristy Blog Index",
  edit: function() {
    return wp.element.createElement("div", {className: "our-placeholder-block"}, "Blog Index Placeholder")
  },
  save: function() {
    return null
  }
})